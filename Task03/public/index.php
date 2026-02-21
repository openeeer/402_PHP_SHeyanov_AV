<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

function appRootPath(): string
{
    return dirname(__DIR__);
}

function databasePath(): string
{
    return appRootPath() . '/db/calculator.sqlite';
}

function getConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $databaseDir = dirname(databasePath());
    if (!is_dir($databaseDir)) {
        mkdir($databaseDir, 0777, true);
    }

    $connection = new PDO('sqlite:' . databasePath());
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $connection->exec('PRAGMA foreign_keys = ON');

    initializeDatabase($connection);

    return $connection;
}

function initializeDatabase(PDO $connection): void
{
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            expression TEXT NOT NULL,
            correct_answer INTEGER NOT NULL,
            started_at TEXT NOT NULL
        )'
    );

    $connection->exec(
        'CREATE TABLE IF NOT EXISTS steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            user_answer INTEGER NOT NULL,
            is_correct INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
        )'
    );
}

function generateExpression(): array
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

    $expression = implode('', $expressionParts);
    $correctAnswer = evaluateExpression($operands, $operators);

    return [$expression, $correctAnswer];
}

function evaluateExpression(array $operands, array $operators): int
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
    $operatorCount = count($reducedOperators);

    for ($index = 0; $index < $operatorCount; $index++) {
        $operator = $reducedOperators[$index];
        $nextOperand = $reducedOperands[$index + 1];

        if ($operator === '+') {
            $result += $nextOperand;
            continue;
        }

        $result -= $nextOperand;
    }

    return $result;
}

function parseInteger(mixed $value): ?int
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

function hydrateGames(array $rows): array
{
    $gamesById = [];

    foreach ($rows as $row) {
        $gameId = (int) $row['id'];

        if (!isset($gamesById[$gameId])) {
            $gamesById[$gameId] = [
                'id' => $gameId,
                'player_name' => $row['player_name'],
                'expression' => $row['expression'],
                'correct_answer' => (int) $row['correct_answer'],
                'started_at' => $row['started_at'],
                'status' => 'in_progress',
                'steps' => [],
            ];
        }

        if ($row['step_id'] === null) {
            continue;
        }

        $gamesById[$gameId]['status'] = 'finished';
        $gamesById[$gameId]['steps'][] = [
            'id' => (int) $row['step_id'],
            'user_answer' => (int) $row['user_answer'],
            'is_correct' => ((int) $row['is_correct']) === 1,
            'created_at' => $row['created_at'],
        ];
    }

    return array_values($gamesById);
}

function jsonResponse(Response $response, mixed $data, int $statusCode = 200): Response
{
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Failed to encode JSON response.');
    }

    $response->getBody()->write($encoded);

    return $response
        ->withStatus($statusCode)
        ->withHeader('Content-Type', 'application/json; charset=utf-8');
}

function errorResponse(Response $response, int $statusCode, string $error): Response
{
    return jsonResponse($response, ['error' => $error], $statusCode);
}

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$homeHandler = function (Request $request, Response $response): Response {
    $indexPath = __DIR__ . '/index.html';
    if (!is_file($indexPath)) {
        return errorResponse($response, 500, 'Cannot open index.html.');
    }

    $response->getBody()->write((string) file_get_contents($indexPath));

    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
};

$getGamesHandler = function (Request $request, Response $response): Response {
    $connection = getConnection();
    $statement = $connection->query(
        'SELECT
            g.id,
            g.player_name,
            g.expression,
            g.correct_answer,
            g.started_at,
            s.id AS step_id,
            s.user_answer,
            s.is_correct,
            s.created_at
        FROM games g
        LEFT JOIN steps s ON s.game_id = g.id
        ORDER BY g.id DESC, s.id ASC'
    );

    $rows = $statement->fetchAll();

    return jsonResponse($response, hydrateGames($rows));
};

$getGameByIdHandler = function (Request $request, Response $response, array $args): Response {
    $gameId = (int) $args['id'];
    $connection = getConnection();

    $statement = $connection->prepare(
        'SELECT
            g.id,
            g.player_name,
            g.expression,
            g.correct_answer,
            g.started_at,
            s.id AS step_id,
            s.user_answer,
            s.is_correct,
            s.created_at
        FROM games g
        LEFT JOIN steps s ON s.game_id = g.id
        WHERE g.id = :id
        ORDER BY s.id ASC'
    );
    $statement->execute([':id' => $gameId]);

    $rows = $statement->fetchAll();
    if ($rows === []) {
        return errorResponse($response, 404, 'Game not found.');
    }

    $games = hydrateGames($rows);

    return jsonResponse($response, $games[0]);
};

$createGameHandler = function (Request $request, Response $response): Response {
    $body = $request->getParsedBody();
    if (!is_array($body)) {
        return errorResponse($response, 400, 'Request body must be JSON.');
    }

    $playerName = trim((string) ($body['player_name'] ?? ''));
    if ($playerName === '') {
        return errorResponse($response, 400, 'Field "player_name" is required.');
    }

    [$expression, $correctAnswer] = generateExpression();
    $startedAt = date('Y-m-d H:i:s');

    $connection = getConnection();
    $statement = $connection->prepare(
        'INSERT INTO games (player_name, expression, correct_answer, started_at)
         VALUES (:player_name, :expression, :correct_answer, :started_at)'
    );
    $statement->execute([
        ':player_name' => $playerName,
        ':expression' => $expression,
        ':correct_answer' => $correctAnswer,
        ':started_at' => $startedAt,
    ]);

    $gameId = (int) $connection->lastInsertId();

    return jsonResponse($response, [
        'id' => $gameId,
        'player_name' => $playerName,
        'expression' => $expression,
        'correct_answer' => $correctAnswer,
        'started_at' => $startedAt,
        'status' => 'in_progress',
        'steps' => [],
    ], 201);
};

$createStepHandler = function (Request $request, Response $response, array $args): Response {
    $gameId = (int) $args['id'];
    $body = $request->getParsedBody();

    if (!is_array($body)) {
        return errorResponse($response, 400, 'Request body must be JSON.');
    }

    $userAnswer = parseInteger($body['user_answer'] ?? null);
    if ($userAnswer === null) {
        return errorResponse($response, 400, 'Field "user_answer" must be an integer.');
    }

    $connection = getConnection();
    $gameStatement = $connection->prepare(
        'SELECT id, correct_answer
         FROM games
         WHERE id = :id'
    );
    $gameStatement->execute([':id' => $gameId]);
    $game = $gameStatement->fetch();

    if (!is_array($game)) {
        return errorResponse($response, 404, 'Game not found.');
    }

    $correctAnswer = (int) $game['correct_answer'];
    $isCorrect = $userAnswer === $correctAnswer;
    $createdAt = date('Y-m-d H:i:s');

    $stepStatement = $connection->prepare(
        'INSERT INTO steps (game_id, user_answer, is_correct, created_at)
         VALUES (:game_id, :user_answer, :is_correct, :created_at)'
    );
    $stepStatement->execute([
        ':game_id' => $gameId,
        ':user_answer' => $userAnswer,
        ':is_correct' => $isCorrect ? 1 : 0,
        ':created_at' => $createdAt,
    ]);

    $stepId = (int) $connection->lastInsertId();

    return jsonResponse($response, [
        'game_id' => $gameId,
        'step' => [
            'id' => $stepId,
            'user_answer' => $userAnswer,
            'is_correct' => $isCorrect,
            'created_at' => $createdAt,
        ],
        'correct_answer' => $correctAnswer,
    ], 201);
};

$app->get('/', $homeHandler);
$app->get('/index.php', $homeHandler);

$app->get('/games', $getGamesHandler);
$app->get('/index.php/games', $getGamesHandler);

$app->get('/games/{id:[0-9]+}', $getGameByIdHandler);
$app->get('/index.php/games/{id:[0-9]+}', $getGameByIdHandler);

$app->post('/games', $createGameHandler);
$app->post('/index.php/games', $createGameHandler);

$app->post('/step/{id:[0-9]+}', $createStepHandler);
$app->post('/index.php/step/{id:[0-9]+}', $createStepHandler);

$app->run();
