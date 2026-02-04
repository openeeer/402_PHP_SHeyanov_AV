<?php

declare(strict_types=1);

namespace SHeyanov_AV\Calculator\Controller;

use function SHeyanov_AV\Calculator\View\askAnswer;
use function SHeyanov_AV\Calculator\View\askName;
use function SHeyanov_AV\Calculator\View\prepareConsole;
use function SHeyanov_AV\Calculator\View\showExpression;
use function SHeyanov_AV\Calculator\View\showGreeting;
use function SHeyanov_AV\Calculator\View\showGoodbye;
use function SHeyanov_AV\Calculator\View\showInvalidAnswer;
use function SHeyanov_AV\Calculator\View\showResult;
use function SHeyanov_AV\Calculator\View\showWelcome;

function startGame(): void
{
    prepareConsole();
    showWelcome();
    $name = askName();
    showGreeting($name);

    [$expression, $correctAnswer] = generateExpression();

    showExpression($expression);
    $normalizedAnswer = '';
    $userAnswerInt = 0;

    while (true) {
        $rawAnswer = askAnswer();
        $normalizedAnswer = trim($rawAnswer);
        $validated = filter_var($normalizedAnswer, FILTER_VALIDATE_INT);
        if ($validated === false) {
            showInvalidAnswer();
            continue;
        }

        $userAnswerInt = (int) $validated;
        break;
    }

    $isCorrect = $userAnswerInt === $correctAnswer;

    showResult($isCorrect, $normalizedAnswer, $correctAnswer);
    showGoodbye($name, $isCorrect);
}

function generateExpression(): array
{
    $operands = [];
    for ($i = 0; $i < 4; $i++) {
        $operands[] = random_int(1, 50);
    }

    $operators = [];
    $availableOperators = ['+', '-', '*'];
    for ($i = 0; $i < 3; $i++) {
        $operators[] = $availableOperators[random_int(0, 2)];
    }

    $expressionParts = [(string) $operands[0]];
    for ($i = 0; $i < 3; $i++) {
        $expressionParts[] = $operators[$i];
        $expressionParts[] = (string) $operands[$i + 1];
    }
    $expression = implode('', $expressionParts);

    $value = evaluateExpression($operands, $operators);

    return [$expression, $value];
}

function evaluateExpression(array $operands, array $operators): int
{
    $reducedOperands = [$operands[0]];
    $reducedOperators = [];

    for ($i = 0; $i < 3; $i++) {
        $operator = $operators[$i];
        $nextOperand = $operands[$i + 1];

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

    for ($i = 0; $i < $operatorCount; $i++) {
        $operator = $reducedOperators[$i];
        $nextOperand = $reducedOperands[$i + 1];

        if ($operator === '+') {
            $result += $nextOperand;
        } else {
            $result -= $nextOperand;
        }
    }

    return $result;
}
