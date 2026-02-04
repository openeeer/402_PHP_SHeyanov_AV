<?php

declare(strict_types=1);

namespace SHeyanov_AV\Calculator\View;

function prepareConsole(): void
{

    ini_set('default_charset', 'UTF-8');
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }

    if (PHP_OS_FAMILY === 'Windows') {
        if (function_exists('sapi_windows_cp_set')) {
            sapi_windows_cp_set(866);
        }

        if (function_exists('sapi_windows_vt100_support')) {
            sapi_windows_vt100_support(STDOUT, true);
        }
    }

    setlocale(LC_ALL, 'ru_RU.UTF-8', 'ru_RU.utf8', 'Russian_Russia.65001', 'ru_RU', 'Russian');
}

function showWelcome(): void
{
    writeLine(colorize('Игра "Калькулятор"', 'cyan'));
    writeLine('Вычислите значение выражения из 4 операндов (+, -, *).');
}

function askName(): string
{
    while (true) {
        $name = readLine(colorize('Ваше имя', 'yellow'));
        if ($name !== '') {
            return $name;
        }
    }
}

function showGreeting(string $name): void
{
    writeLine("Привет, %s!", $name);
}

function showExpression(string $expression): void
{
    writeLine('Выражение: %s', colorize($expression, 'blue'));
}

function askAnswer(): string
{
    return readLine(colorize('Ваш ответ', 'yellow'));
}

function showResult(bool $isCorrect, string $userAnswer, int $correctAnswer): void
{

    if ($isCorrect) {
        writeLine(colorize('Верно! :-)', 'green'));
        return;
    }

    writeLine(colorize('Неверно. :-(', 'red'));
    writeLine('Ваш ответ: %s', $userAnswer);
    writeLine('Правильный ответ: %s', $correctAnswer);
}

function showGoodbye(string $name, bool $isCorrect): void
{
    writeLine('Спасибо за игру, %s!', $name);
}

function showInvalidAnswer(): void
{
    writeLine(colorize('Введите целое число.', 'red'));
}

function readLine(string $label): string
{
    echo toConsoleEncoding($label . ': ');

    $input = fgets(STDIN);

    if ($input === false) {
        return '';
    }

    return normalizeInput($input);
}

function normalizeInput(string $input): string
{

    $input = fromConsoleEncoding($input);
    return trim($input);
}

function fromConsoleEncoding(string $input): string
{
    $encoding = getConsoleEncoding();
    if ($encoding === 'UTF-8') {
        return $input;
    }

    return (string) mb_convert_encoding($input, 'UTF-8', $encoding);
}

function toConsoleEncoding(string $text): string
{
    $encoding = getConsoleEncoding();

    if ($encoding === 'UTF-8') {
        return $text;
    }

    return (string) mb_convert_encoding($text, $encoding, 'UTF-8');
}

function getConsoleEncoding(): string
{
    if (PHP_OS_FAMILY !== 'Windows') {
        return 'UTF-8';
    }

    if (function_exists('sapi_windows_cp_get')) {
        $cp = sapi_windows_cp_get();
        return $cp === 65001 ? 'UTF-8' : 'CP' . $cp;
    }

    return 'CP866';
}

function writeLine(string $format, ...$args): void
{
    $text = $args === [] ? $format : vsprintf($format, $args);
    echo toConsoleEncoding($text) . PHP_EOL;
}

function colorize(string $text, string $color): string
{
    $palette = [
        'red' => '0;31',
        'green' => '0;32',
        'yellow' => '0;33',
        'blue' => '0;34',
        'cyan' => '0;36',
        'default' => '0',
    ];

    $code = $palette[$color] ?? $palette['default'];
    return "\033[" . $code . "m" . $text . "\033[0m";
}
