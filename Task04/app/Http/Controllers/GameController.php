<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Step;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(): View
    {
        return view('game');
    }

    public function listGames(): JsonResponse
    {
        $games = Game::query()
            ->with('steps')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Game $game): array => $this->gamePayload($game))
            ->all();

        return response()->json($games);
    }

    public function showGame(int $id): JsonResponse
    {
        $game = Game::query()
            ->with('steps')
            ->find($id);

        if ($game === null) {
            return $this->errorResponse('Game not found.', 404);
        }

        return response()->json($this->gamePayload($game));
    }

    public function createGame(Request $request): JsonResponse
    {
        $playerName = trim((string) $request->input('player_name', ''));

        if ($playerName === '') {
            return $this->errorResponse('Field "player_name" is required.', 400);
        }

        [$expression, $correctAnswer] = $this->generateExpression();

        $game = Game::query()->create([
            'player_name' => $playerName,
            'expression' => $expression,
            'correct_answer' => $correctAnswer,
            'started_at' => now(),
        ]);

        return response()->json($this->gamePayload($game->load('steps')), 201);
    }

    public function createStep(Request $request, int $id): JsonResponse
    {
        $userAnswer = $this->parseInteger($request->input('user_answer'));

        if ($userAnswer === null) {
            return $this->errorResponse('Field "user_answer" must be an integer.', 400);
        }

        $game = Game::query()->find($id);

        if ($game === null) {
            return $this->errorResponse('Game not found.', 404);
        }

        $correctAnswer = (int) $game->correct_answer;
        $isCorrect = $userAnswer === $correctAnswer;

        $step = $game->steps()->create([
            'user_answer' => $userAnswer,
            'is_correct' => $isCorrect,
            'created_at' => now(),
        ]);

        return response()->json([
            'game_id' => (int) $game->id,
            'step' => $this->stepPayload($step),
            'correct_answer' => $correctAnswer,
        ], 201);
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }

    private function gamePayload(Game $game): array
    {
        $steps = $game->relationLoaded('steps') ? $game->steps : $game->steps()->get();
        $formattedSteps = $steps
            ->map(fn (Step $step): array => $this->stepPayload($step))
            ->values()
            ->all();

        return [
            'id' => (int) $game->id,
            'player_name' => $game->player_name,
            'expression' => $game->expression,
            'correct_answer' => (int) $game->correct_answer,
            'started_at' => $this->formatDateTime($game->started_at),
            'status' => $formattedSteps === [] ? 'in_progress' : 'finished',
            'steps' => $formattedSteps,
        ];
    }

    private function stepPayload(Step $step): array
    {
        return [
            'id' => (int) $step->id,
            'user_answer' => (int) $step->user_answer,
            'is_correct' => (bool) $step->is_correct,
            'created_at' => $this->formatDateTime($step->created_at),
        ];
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    private function parseInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/^-?\d+$/', $value) !== 1) {
            return null;
        }

        return (int) $value;
    }

    private function generateExpression(): array
    {
        $operands = [];
        for ($index = 0; $index < 4; $index++) {
            $operands[] = random_int(1, 50);
        }

        $availableOperators = ['+', '-', '*'];
        $operators = [];
        for ($index = 0; $index < 3; $index++) {
            $operators[] = $availableOperators[random_int(0, 2)];
        }

        $expressionParts = [(string) $operands[0]];
        for ($index = 0; $index < 3; $index++) {
            $expressionParts[] = $operators[$index];
            $expressionParts[] = (string) $operands[$index + 1];
        }

        return [
            implode('', $expressionParts),
            $this->evaluateExpression($operands, $operators),
        ];
    }

    private function evaluateExpression(array $operands, array $operators): int
    {
        $reducedOperands = [$operands[0]];
        $reducedOperators = [];

        for ($index = 0; $index < 3; $index++) {
            $operator = $operators[$index];
            $nextOperand = $operands[$index + 1];

            if ($operator === '*') {
                $lastIndex = count($reducedOperands) - 1;
                $reducedOperands[$lastIndex] *= $nextOperand;
                continue;
            }

            $reducedOperators[] = $operator;
            $reducedOperands[] = $nextOperand;
        }

        $result = $reducedOperands[0];

        foreach ($reducedOperators as $index => $operator) {
            $nextOperand = $reducedOperands[$index + 1];

            if ($operator === '+') {
                $result += $nextOperand;
                continue;
            }

            $result -= $nextOperand;
        }

        return $result;
    }
}
