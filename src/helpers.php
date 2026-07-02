<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Matchers\Captures\CapturedMatcher;
use Fleet\AstMatcher\Matchers\Collections\SliceMatcher;

if (!function_exists('anything')) {
    function anything() { return Ast::any(); }
}
if (!function_exists('anyNode')) {
    function anyNode() { return Ast::anyNode(); }
}
if (!function_exists('anyStatement')) {
    function anyStatement() { return Ast::anyStatement(); }
}
if (!function_exists('anyString')) {
    function anyString() { return Ast::anyString(); }
}
if (!function_exists('anyNumber')) {
    function anyNumber() { return Ast::anyNumber(); }
}
if (!function_exists('identifier')) {
    function identifier(?string $name = null) { return Ast::identifier($name); }
}
if (!function_exists('name')) {
    function name(?string $name = null) { return Ast::name($name); }
}
if (!function_exists('variable')) {
    function variable(?string $name = null) { return Ast::variable($name); }
}
if (!function_exists('var_')) {
    function var_(?string $name = null) { return Ast::var($name); }
}
if (!function_exists('stringLiteral')) {
    function stringLiteral(?string $value = null) { return Ast::stringLiteral($value); }
}
if (!function_exists('numberLiteral')) {
    function numberLiteral(int|float|null $value = null) { return Ast::numberLiteral($value); }
}
if (!function_exists('callExpression')) {
    function callExpression($callee = null, $args = null) { return Ast::callExpression($callee, $args); }
}
if (!function_exists('methodCall')) {
    function methodCall($object = null, $property = null, $args = null) { return Ast::methodCall($object, $property, $args); }
}
if (!function_exists('staticCall')) {
    function staticCall($class = null, $name = null, $args = null) { return Ast::staticCall($class, $name, $args); }
}
if (!function_exists('propertyFetch')) {
    function propertyFetch($object = null, $property = null) { return Ast::propertyFetch($object, $property); }
}
if (!function_exists('memberExpression')) {
    function memberExpression($object = null, $property = null) { return Ast::memberExpression($object, $property); }
}
if (!function_exists('classConstFetch')) {
    function classConstFetch($class = null, $name = null) { return Ast::classConstFetch($class, $name); }
}
if (!function_exists('arrayExpression')) {
    function arrayExpression(?array $elements = null) { return Ast::arrayExpression($elements); }
}
if (!function_exists('returnStatement')) {
    function returnStatement($argument = null) { return Ast::return_($argument); }
}
if (!function_exists('functionDeclaration')) {
    function functionDeclaration($name = null, $params = null, $body = null) { return Ast::functionDeclaration($name, $params, $body); }
}
if (!function_exists('classDeclaration')) {
    function classDeclaration($name = null, $extends = null, $body = null) { return Ast::classDeclaration($name, $extends, $body); }
}
if (!function_exists('classMethod')) {
    function classMethod($name = null, $params = null, $body = null, $static = null) { return Ast::classMethod($name, $params, $body, $static); }
}
if (!function_exists('classProperty')) {
    function classProperty($name = null, $default = null, $static = null) { return Ast::classProperty($name, $default, $static); }
}
if (!function_exists('arg')) {
    function arg($value = null, $name = null) { return Ast::arg($value, $name); }
}
if (!function_exists('param')) {
    function param($name = null, $type = null) { return Ast::param($name, $type); }
}
if (!function_exists('arrayItem')) {
    function arrayItem($value = null, $key = null) { return Ast::arrayItem($value, $key); }
}
if (!function_exists('anyList')) {
    function anyList(...$matchers) { return Ast::anyList(...$matchers); }
}
if (!function_exists('body')) {
    function body(...$matchers) { return Ast::body(...$matchers); }
}
if (!function_exists('tupleOf')) {
    function tupleOf(...$matchers) { return Ast::tupleOf(...$matchers); }
}
if (!function_exists('arrayOf')) {
    function arrayOf($elementMatcher) { return Ast::arrayOf($elementMatcher); }
}
if (!function_exists('slice')) {
    function slice(int|array $options, $matcher = null) { return Ast::slice($options, $matcher); }
}
if (!function_exists('zeroOrMore')) {
    function zeroOrMore($matcher = null) { return Ast::zeroOrMore($matcher); }
}
if (!function_exists('oneOrMore')) {
    function oneOrMore($matcher = null) { return Ast::oneOrMore($matcher); }
}
if (!function_exists('spacer')) {
    function spacer(int $min = 1, ?int $max = null) { return Ast::spacer($min, $max); }
}
if (!function_exists('capture')) {
    function capture($matcher = null) { return Ast::capture($matcher); }
}
if (!function_exists('captureCollector')) {
    function captureCollector($matcher = null) { return Ast::captureCollector($matcher); }
}
if (!function_exists('containerOf')) {
    function containerOf($containedMatcher) { return Ast::containerOf($containedMatcher); }
}
if (!function_exists('fromCapture')) {
    function fromCapture(CapturedMatcher $capturedMatcher) { return Ast::fromCapture($capturedMatcher); }
}
if (!function_exists('predicate')) {
    function predicate(callable $fn) { return Ast::predicate($fn); }
}

// ─── Control Flow ────────────────────────────────────────────────────────────
if (!function_exists('if_')) {
    function if_($cond = null, $then = null, $elseifs = null, $else = null) { return Ast::if_($cond, $then, $elseifs, $else); }
}
if (!function_exists('elseIf_')) {
    function elseIf_($cond = null, $body = null) { return Ast::elseIf_($cond, $body); }
}
if (!function_exists('else_')) {
    function else_($body = null) { return Ast::else_($body); }
}
if (!function_exists('foreach_')) {
    function foreach_($expr = null, $valueVar = null, $keyVar = null, $body = null) { return Ast::foreach_($expr, $valueVar, $keyVar, $body); }
}
if (!function_exists('while_')) {
    function while_($cond = null, $body = null) { return Ast::while_($cond, $body); }
}
if (!function_exists('doWhile')) {
    function doWhile($body = null, $cond = null) { return Ast::doWhile($body, $cond); }
}
if (!function_exists('for_')) {
    function for_($init = null, $cond = null, $loop = null, $body = null) { return Ast::for_($init, $cond, $loop, $body); }
}
if (!function_exists('tryCatch')) {
    function tryCatch($body = null, $catches = null, $finally = null) { return Ast::tryCatch($body, $catches, $finally); }
}
if (!function_exists('catch_')) {
    function catch_($types = null, $var = null, $body = null) { return Ast::catch_($types, $var, $body); }
}
if (!function_exists('finally_')) {
    function finally_($body = null) { return Ast::finally_($body); }
}
if (!function_exists('switch_')) {
    function switch_($cond = null, $cases = null) { return Ast::switch_($cond, $cases); }
}
if (!function_exists('case_')) {
    function case_($cond = null, $body = null) { return Ast::case_($cond, $body); }
}
if (!function_exists('echo_')) {
    function echo_($exprs = null) { return Ast::echo_($exprs); }
}
if (!function_exists('break_')) {
    function break_($num = null) { return Ast::break_($num); }
}
if (!function_exists('continue_')) {
    function continue_($num = null) { return Ast::continue_($num); }
}

if (!function_exists('distributeAcrossSlices')) {
    function distributeAcrossSlices(array $slices, int $available): Generator
    {
        if (count($slices) === 0) {
            yield [];
        } elseif (count($slices) === 1) {
            $s = $slices[0];
            if ($s->min <= $available && $available <= $s->max) {
                yield [$available];
            }
        } else {
            $last = $slices[count($slices) - 1];
            $allButLast = array_slice($slices, 0, -1);
            for ($n = $last->min; $n <= $last->max && $n <= $available; $n++) {
                foreach (distributeAcrossSlices($allButLast, $available - $n) as $prior) {
                    yield array_merge($prior, [$n]);
                }
            }
        }
    }
}

if (!function_exists('findFirstNode')) {
    function findFirstNode($nodes, Closure $expr)
    {
        return (new \PhpParser\NodeFinder())->findFirst($nodes, $expr);
    }
}
if (!function_exists('findNodes')) {
    function findNodes($nodes, Closure $expr)
    {
        return (new \PhpParser\NodeFinder())->find($nodes, $expr);
    }
}
