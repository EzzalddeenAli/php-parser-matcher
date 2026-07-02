<?php

/**
 * PrinterBuildExamples.php
 *
 * Demonstrates MatcherPrinter (PHP code → matcher code) and
 * Build facade (builder API → PHP Nodes → PHP code).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Facade\Build;
use Fleet\AstMatcher\Printer\MatcherPrinter;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function section(string $title): void
{
    echo "\n\033[1;34m── $title ──\033[0m\n";
}

function demo(string $label, string $code): void
{
    echo "\033[0;33m» $label\033[0m\n";
    echo $code . "\n\n";
}

// ─── Phase 1: MatcherPrinter ─────────────────────────────────────────────────

section('MatcherPrinter: PHP expression → Ast matcher code');

$printer = new MatcherPrinter();

// 1. Static call with string arguments
demo(
    'Text::make(\'Name\', \'name\')',
    $printer->printCode("Text::make('Name', 'name')")
);

// 2. Method call on $this
demo(
    '$this->hasMany(User::class)',
    $printer->printCode('$this->hasMany(User::class)')
);

// 3. Chained method calls
demo(
    '$query->where(\'active\', true)->orderBy(\'name\')',
    $printer->printCode("\$query->where('active', true)->orderBy('name')")
);

// 4. Property access
demo(
    '$user->name',
    $printer->printCode('$user->name')
);

// 5. Binary operation
demo(
    '$a + $b',
    $printer->printCode('$a + $b')
);

// 6. Ternary
demo(
    '$a ? $b : $c',
    $printer->printCode('$a ? $b : $c')
);

// 7. new expression
demo(
    "new User(['name' => 'Alice'])",
    $printer->printCode("new User(['name' => 'Alice'])")
);

// 8. Arrow function
demo(
    'fn($x) => $x * 2',
    $printer->printCode('fn($x) => $x * 2')
);

// 9. If statement
demo(
    'if ($user->isAdmin()) { return true; }',
    $printer->printCode("if (\$user->isAdmin()) { return true; }")
);

// 10. Function mode
section('MatcherPrinter: function facade mode');
$fnPrinter = new MatcherPrinter(facade: 'function');
demo(
    "Text::make('Name') — function style",
    $fnPrinter->printCode("Text::make('Name')")
);

// ─── Phase 2: Build facade ────────────────────────────────────────────────────

section('Build: builder API → PHP source');

// 1. Static call
$node = Build::staticCall('Text', 'make', [
    Build::arg(Build::string('Name')),
    Build::arg(Build::string('name')),
]);
demo('Build::staticCall', Build::print($node));

// 2. Method chain
$query = Build::methodCall(
    Build::methodCall(
        Build::variable('query'),
        'where',
        [Build::arg(Build::string('active')), Build::arg(Build::true())]
    ),
    'orderBy',
    [Build::arg(Build::string('name'))]
);
demo('Build: chained method calls', Build::print($query));

// 3. Binary operation
$sum = Build::binaryOp('+', Build::variable('a'), Build::variable('b'));
demo('Build::binaryOp', Build::print($sum));

// 4. Arrow function
$fn = Build::arrowFn(
    [Build::param('x')],
    Build::binaryOp('*', Build::variable('x'), Build::int(2))
);
demo('Build::arrowFn', Build::print($fn));

// 5. Return statement
$ret = Build::return(Build::staticCall('Response', 'json', [
    Build::arg(Build::variable('data')),
]));
demo('Build::return', Build::printStatement($ret));

// 6. New expression
$new = Build::new('User', [
    Build::arg(Build::array([
        Build::arrayItem(Build::string('Alice'), Build::string('name')),
    ])),
]);
demo('Build::new', Build::print($new));

// 7. Class declaration with a method
$class = Build::classDeclaration('MyController', 'Controller', [
    Build::classMethod('index', [], [
        Build::return(Build::staticCall('View', 'make', [
            Build::arg(Build::string('home')),
        ])),
    ]),
]);
demo('Build: class declaration', Build::print($class));

// ─── Round-trip: PHP → matcher → verify ──────────────────────────────────────

section('Round-trip: PHP → matcher code → verify match');

$phpCode   = "Text::make('Name', 'name')";
$matcherCode = $printer->printCode($phpCode);

echo "\033[0;33m» Input PHP:\033[0m  $phpCode\n";
echo "\033[0;33m» Matcher:\033[0m\n$matcherCode\n\n";

// Execute the generated matcher code and verify it matches the original
$matcher = eval('use Fleet\\AstMatcher\\Facade\\Ast; return ' . $matcherCode . ';');

$testNode = Fleet\AstMatcher\Testing\AstTestRunner::parseExpression($phpCode);

$matched = $matcher->matchValue($testNode, []);
echo ($matched ? "\033[0;32m✔ Matcher matches the original PHP!\033[0m" : "\033[0;31m✘ No match\033[0m") . "\n";
