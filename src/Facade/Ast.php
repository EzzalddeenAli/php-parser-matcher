<?php

namespace Fleet\AstMatcher\Facade;

use Fleet\AstMatcher\Matchers\Captures\CapturedMatcher;
use Fleet\AstMatcher\Matchers\Captures\CapturesCollectorMatcher;
use Fleet\AstMatcher\Matchers\Captures\ContainerOfMatcher;
use Fleet\AstMatcher\Matchers\Captures\FromCaptureMatcher;
use Fleet\AstMatcher\Matchers\Collections\AnyListMatcher;
use Fleet\AstMatcher\Matchers\Collections\ArrayOfMatcher;
use Fleet\AstMatcher\Matchers\Collections\SliceMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;
use Fleet\AstMatcher\Matchers\Declarations\ClassDeclarationMatcher;
use Fleet\AstMatcher\Matchers\Declarations\ClassMethodMatcher;
use Fleet\AstMatcher\Matchers\Declarations\ClassPropertyMatcher;
use Fleet\AstMatcher\Matchers\Declarations\EnumCaseMatcher;
use Fleet\AstMatcher\Matchers\Declarations\EnumMatcher;
use Fleet\AstMatcher\Matchers\Declarations\FunctionDeclarationMatcher;
use Fleet\AstMatcher\Matchers\Declarations\InterfaceMatcher;
use Fleet\AstMatcher\Matchers\Declarations\NamespaceMatcher;
use Fleet\AstMatcher\Matchers\Declarations\TraitMatcher;
use Fleet\AstMatcher\Matchers\Declarations\UseStatementMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Access\ArrayDimFetchMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Access\ClassConstFetchMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Access\ConstFetchMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Access\NullsafePropertyFetchMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Access\PropertyFetchMatcher;
use Fleet\AstMatcher\Matchers\Expressions\ArrayExpressionMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Assignment\AssignMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Assignment\AssignOpMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Calls\CallExpressionMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Calls\MethodCallMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Calls\NullsafeMethodCallMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Calls\StaticCallMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Functions\ArrowFunctionMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Functions\ClosureMatcher;
use Fleet\AstMatcher\Matchers\Expressions\MatchExprMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Objects\InstanceofMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Objects\NewMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Operations\BinaryOpMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Operations\CastMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Operations\TernaryMatcher;
use Fleet\AstMatcher\Matchers\Expressions\Operations\UnaryOpMatcher;
use Fleet\AstMatcher\Matchers\Expressions\ThrowExprMatcher;
use Fleet\AstMatcher\Matchers\Generic\AnythingMatcher;
use Fleet\AstMatcher\Matchers\Generic\AnyNodeMatcher;
use Fleet\AstMatcher\Matchers\Generic\AnyStatementMatcher;
use Fleet\AstMatcher\Matchers\Generic\OneOfMatcher;
use Fleet\AstMatcher\Matchers\Generic\OrMatcher;
use Fleet\AstMatcher\Matchers\Generic\PredicateMatcher;
use Fleet\AstMatcher\Matchers\Names\IdentifierMatcher;
use Fleet\AstMatcher\Matchers\Names\VariableMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ArrayItemMatcher;
use Fleet\AstMatcher\Matchers\Nodes\AttributeMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ParamMatcher;
use Fleet\AstMatcher\Matchers\Nodes\TraitUseMatcher;
use Fleet\AstMatcher\Matchers\Scalars\AnyNumberMatcher;
use Fleet\AstMatcher\Matchers\Scalars\AnyStringMatcher;
use Fleet\AstMatcher\Matchers\Scalars\NumberLiteralMatcher;
use Fleet\AstMatcher\Matchers\Scalars\StringLiteralMatcher;
use Fleet\AstMatcher\Matchers\Nodes\CaseMatcher;
use Fleet\AstMatcher\Matchers\Nodes\CatchMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ElseIfMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ElseMatcher;
use Fleet\AstMatcher\Matchers\Nodes\FinallyMatcher;
use Fleet\AstMatcher\Matchers\Statements\BreakMatcher;
use Fleet\AstMatcher\Matchers\Statements\ContinueMatcher;
use Fleet\AstMatcher\Matchers\Statements\DoWhileMatcher;
use Fleet\AstMatcher\Matchers\Statements\EchoMatcher;
use Fleet\AstMatcher\Matchers\Statements\ExpressionStatementMatcher;
use Fleet\AstMatcher\Matchers\Statements\ForMatcher;
use Fleet\AstMatcher\Matchers\Statements\ForeachMatcher;
use Fleet\AstMatcher\Matchers\Statements\IfMatcher;
use Fleet\AstMatcher\Matchers\Statements\ReturnStatementMatcher;
use Fleet\AstMatcher\Matchers\Statements\SwitchMatcher;
use Fleet\AstMatcher\Matchers\Statements\TryCatchMatcher;
use Fleet\AstMatcher\Matchers\Statements\WhileMatcher;

class Ast
{
    // ─── Generic ────────────────────────────────────────────────────────────

    public static function any(): AnythingMatcher
    {
        return new AnythingMatcher();
    }

    public static function anyNode(): AnyNodeMatcher
    {
        return new AnyNodeMatcher();
    }

    public static function anyStatement(): AnyStatementMatcher
    {
        return new AnyStatementMatcher();
    }

    public static function or(...$matchersOrValues): OrMatcher
    {
        return new OrMatcher(...$matchersOrValues);
    }

    public static function oneOf(?AnythingMatcher $matcher = null): OneOfMatcher
    {
        return new OneOfMatcher($matcher);
    }

    public static function predicate(callable $fn): PredicateMatcher
    {
        return new PredicateMatcher($fn);
    }

    // ─── Scalars ─────────────────────────────────────────────────────────────

    public static function stringLiteral(?string $value = null): StringLiteralMatcher
    {
        return new StringLiteralMatcher($value);
    }

    /** Alias for stringLiteral() */
    public static function string(?string $value = null): StringLiteralMatcher
    {
        return new StringLiteralMatcher($value);
    }

    public static function numberLiteral(int|float|null $value = null): NumberLiteralMatcher
    {
        return new NumberLiteralMatcher($value);
    }

    /** Alias for numberLiteral() */
    public static function number(int|float|null $value = null): NumberLiteralMatcher
    {
        return new NumberLiteralMatcher($value);
    }

    public static function anyString(): AnyStringMatcher
    {
        return new AnyStringMatcher();
    }

    public static function anyNumber(): AnyNumberMatcher
    {
        return new AnyNumberMatcher();
    }

    // ─── Names ───────────────────────────────────────────────────────────────

    public static function identifier(?string $name = null): IdentifierMatcher
    {
        return new IdentifierMatcher($name);
    }

    /** Alias for identifier() */
    public static function name(?string $name = null): IdentifierMatcher
    {
        return new IdentifierMatcher($name);
    }

    public static function variable(?string $name = null): VariableMatcher
    {
        return new VariableMatcher($name);
    }

    /** Alias for variable() */
    public static function var(?string $name = null): VariableMatcher
    {
        return new VariableMatcher($name);
    }

    // ─── Calls ───────────────────────────────────────────────────────────────

    public static function callExpression($callee = null, $args = null): CallExpressionMatcher
    {
        return new CallExpressionMatcher($callee, $args);
    }

    /** Alias for callExpression() */
    public static function call($callee = null, $args = null): CallExpressionMatcher
    {
        return new CallExpressionMatcher($callee, $args);
    }

    public static function methodCall($object = null, $property = null, $args = null): MethodCallMatcher
    {
        return new MethodCallMatcher($object, $property, $args);
    }

    public static function staticCall($class = null, $name = null, $args = null): StaticCallMatcher
    {
        return new StaticCallMatcher($class, $name, $args);
    }

    public static function nullsafeCall($object = null, $name = null, $args = null): NullsafeMethodCallMatcher
    {
        return new NullsafeMethodCallMatcher($object, $name, $args);
    }

    // ─── Access ──────────────────────────────────────────────────────────────

    public static function propertyFetch($object = null, $property = null): PropertyFetchMatcher
    {
        return new PropertyFetchMatcher($object, $property);
    }

    /** Alias for propertyFetch() */
    public static function memberExpression($object = null, $property = null): PropertyFetchMatcher
    {
        return new PropertyFetchMatcher($object, $property);
    }

    public static function nullsafeProp($object = null, $property = null): NullsafePropertyFetchMatcher
    {
        return new NullsafePropertyFetchMatcher($object, $property);
    }

    public static function classConstFetch($class = null, $name = null): ClassConstFetchMatcher
    {
        return new ClassConstFetchMatcher($class, $name);
    }

    public static function constFetch($name = null): ConstFetchMatcher
    {
        return new ConstFetchMatcher($name);
    }

    public static function true_(): ConstFetchMatcher
    {
        return new ConstFetchMatcher('true');
    }

    public static function false_(): ConstFetchMatcher
    {
        return new ConstFetchMatcher('false');
    }

    public static function null_(): ConstFetchMatcher
    {
        return new ConstFetchMatcher('null');
    }

    public static function arrayAccess($var = null, $dim = null): ArrayDimFetchMatcher
    {
        return new ArrayDimFetchMatcher($var, $dim);
    }

    /** Alias for arrayAccess() */
    public static function arrayDimFetch($var = null, $dim = null): ArrayDimFetchMatcher
    {
        return new ArrayDimFetchMatcher($var, $dim);
    }

    // ─── Assignment ──────────────────────────────────────────────────────────

    public static function assign($var = null, $expr = null): AssignMatcher
    {
        return new AssignMatcher($var, $expr);
    }

    public static function assignOp(?string $operator = null, $var = null, $expr = null): AssignOpMatcher
    {
        return new AssignOpMatcher($operator, $var, $expr);
    }

    // ─── Operations ──────────────────────────────────────────────────────────

    public static function binaryOp($operator = null, $left = null, $right = null): BinaryOpMatcher
    {
        return new BinaryOpMatcher($operator, $left, $right);
    }

    /** Alias for binaryOp() */
    public static function logicalExpression($operator = null, $left = null, $right = null): BinaryOpMatcher
    {
        return new BinaryOpMatcher($operator, $left, $right);
    }

    public static function ternary($cond = null, $if = null, $else = null): TernaryMatcher
    {
        return new TernaryMatcher($cond, $if, $else);
    }

    public static function cast(?string $type = null, $expr = null): CastMatcher
    {
        return new CastMatcher($type, $expr);
    }

    public static function unaryOp(?string $operator = null, $expr = null): UnaryOpMatcher
    {
        return new UnaryOpMatcher($operator, $expr);
    }

    // ─── Objects ─────────────────────────────────────────────────────────────

    public static function new_($class = null, $args = null): NewMatcher
    {
        return new NewMatcher($class, $args);
    }

    public static function instanceof_($expr = null, $class = null): InstanceofMatcher
    {
        return new InstanceofMatcher($expr, $class);
    }

    // ─── Functions ───────────────────────────────────────────────────────────

    public static function closure($params = null, $body = null, $static = null): ClosureMatcher
    {
        return new ClosureMatcher($params, $body, $static);
    }

    public static function arrowFn($params = null, $expr = null, $static = null): ArrowFunctionMatcher
    {
        return new ArrowFunctionMatcher($params, $expr, $static);
    }

    // ─── Expressions ─────────────────────────────────────────────────────────

    public static function arrayExpression(?array $elements = null): ArrayExpressionMatcher
    {
        return new ArrayExpressionMatcher($elements);
    }

    /** Alias for arrayExpression() */
    public static function array_(?array $elements = null): ArrayExpressionMatcher
    {
        return new ArrayExpressionMatcher($elements);
    }

    public static function throw_($expr = null): ThrowExprMatcher
    {
        return new ThrowExprMatcher($expr);
    }

    public static function matchExpr($subject = null, $arms = null): MatchExprMatcher
    {
        return new MatchExprMatcher($subject, $arms);
    }

    // ─── Statements ──────────────────────────────────────────────────────────

    public static function expressionStatement($expr = null): ExpressionStatementMatcher
    {
        return new ExpressionStatementMatcher($expr);
    }

    /** Alias for expressionStatement() */
    public static function statement($expr = null): ExpressionStatementMatcher
    {
        return new ExpressionStatementMatcher($expr);
    }

    public static function return_($argument = null): ReturnStatementMatcher
    {
        return new ReturnStatementMatcher($argument);
    }

    /** Alias for return_() */
    public static function returnStatement($argument = null): ReturnStatementMatcher
    {
        return new ReturnStatementMatcher($argument);
    }

    // ─── Declarations ────────────────────────────────────────────────────────

    public static function functionDeclaration($name = null, $params = null, $body = null): FunctionDeclarationMatcher
    {
        return new FunctionDeclarationMatcher($name, $params, $body);
    }

    public static function classDeclaration($name = null, $extends = null, $body = null): ClassDeclarationMatcher
    {
        return new ClassDeclarationMatcher($name, $extends, $body);
    }

    public static function classMethod($name = null, $params = null, $body = null, $static = null): ClassMethodMatcher
    {
        return new ClassMethodMatcher($name, $params, $body, $static);
    }

    public static function classProperty($name = null, $default = null, $static = null): ClassPropertyMatcher
    {
        return new ClassPropertyMatcher($name, $default, $static);
    }

    public static function trait_($name = null, $body = null): TraitMatcher
    {
        return new TraitMatcher($name, $body);
    }

    public static function interface_($name = null, $extends = null, $body = null): InterfaceMatcher
    {
        return new InterfaceMatcher($name, $extends, $body);
    }

    public static function enum_($name = null, $scalarType = null, $body = null): EnumMatcher
    {
        return new EnumMatcher($name, $scalarType, $body);
    }

    public static function enumCase($name = null, $expr = null): EnumCaseMatcher
    {
        return new EnumCaseMatcher($name, $expr);
    }

    public static function namespace_($name = null, $stmts = null): NamespaceMatcher
    {
        return new NamespaceMatcher($name, $stmts);
    }

    public static function use_($name = null, $alias = null): UseStatementMatcher
    {
        return new UseStatementMatcher($name, $alias);
    }

    // ─── Control Flow ────────────────────────────────────────────────────────

    public static function if_($cond = null, $then = null, $elseifs = null, $else = null): IfMatcher
    {
        return new IfMatcher($cond, $then, $elseifs, $else);
    }

    public static function elseIf_($cond = null, $body = null): ElseIfMatcher
    {
        return new ElseIfMatcher($cond, $body);
    }

    public static function else_($body = null): ElseMatcher
    {
        return new ElseMatcher($body);
    }

    public static function foreach_($expr = null, $valueVar = null, $keyVar = null, $body = null): ForeachMatcher
    {
        return new ForeachMatcher($expr, $valueVar, $keyVar, $body);
    }

    public static function while_($cond = null, $body = null): WhileMatcher
    {
        return new WhileMatcher($cond, $body);
    }

    public static function doWhile($body = null, $cond = null): DoWhileMatcher
    {
        return new DoWhileMatcher($body, $cond);
    }

    public static function for_($init = null, $cond = null, $loop = null, $body = null): ForMatcher
    {
        return new ForMatcher($init, $cond, $loop, $body);
    }

    public static function tryCatch($body = null, $catches = null, $finally = null): TryCatchMatcher
    {
        return new TryCatchMatcher($body, $catches, $finally);
    }

    public static function catch_($types = null, $var = null, $body = null): CatchMatcher
    {
        return new CatchMatcher($types, $var, $body);
    }

    public static function finally_($body = null): FinallyMatcher
    {
        return new FinallyMatcher($body);
    }

    public static function switch_($cond = null, $cases = null): SwitchMatcher
    {
        return new SwitchMatcher($cond, $cases);
    }

    public static function case_($cond = null, $body = null): CaseMatcher
    {
        return new CaseMatcher($cond, $body);
    }

    public static function echo_($exprs = null): EchoMatcher
    {
        return new EchoMatcher($exprs);
    }

    public static function break_($num = null): BreakMatcher
    {
        return new BreakMatcher($num);
    }

    public static function continue_($num = null): ContinueMatcher
    {
        return new ContinueMatcher($num);
    }

    // ─── Nodes ───────────────────────────────────────────────────────────────

    public static function arg($value = null, $name = null): ArgMatcher
    {
        return new ArgMatcher($value, $name);
    }

    public static function param($name = null, $type = null): ParamMatcher
    {
        if (is_string($name)) {
            $name = new VariableMatcher($name);
        }
        if (is_string($type)) {
            $type = new IdentifierMatcher($type);
        }
        return new ParamMatcher($name, $type);
    }

    public static function arrayItem($value = null, $key = null): ArrayItemMatcher
    {
        return new ArrayItemMatcher($value, $key);
    }

    public static function attribute($name = null, $args = null): AttributeMatcher
    {
        return new AttributeMatcher($name, $args);
    }

    public static function traitUse($traits = null): TraitUseMatcher
    {
        return new TraitUseMatcher($traits);
    }

    // ─── Collections ─────────────────────────────────────────────────────────

    public static function anyList(mixed ...$matchers): AnyListMatcher
    {
        return new AnyListMatcher($matchers);
    }

    /** Alias for anyList() — matches a block body */
    public static function body(mixed ...$matchers): AnyListMatcher
    {
        return new AnyListMatcher($matchers);
    }

    /** Alias for anyList() — matches a statement block (same as anyList/body) */
    public static function blockStatement(mixed ...$matchers): AnyListMatcher
    {
        if (count($matchers) === 1 && is_array($matchers[0])) {
            return new AnyListMatcher($matchers[0]);
        }
        return new AnyListMatcher($matchers);
    }

    public static function tupleOf(mixed ...$matchers): TupleOfMatcher
    {
        return new TupleOfMatcher(...$matchers);
    }

    public static function arrayOf($elementMatcher): ArrayOfMatcher
    {
        return new ArrayOfMatcher($elementMatcher);
    }

    public static function slice(int|array $options, $matcher = null): SliceMatcher
    {
        if (is_int($options)) {
            return new SliceMatcher($options, $options, $matcher ?? new AnythingMatcher());
        }
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? PHP_INT_MAX;
        $m = $options['matcher'] ?? $matcher ?? new AnythingMatcher();
        return new SliceMatcher($min, $max, $m);
    }

    public static function zeroOrMore($matcher = null): SliceMatcher
    {
        return new SliceMatcher(0, PHP_INT_MAX, $matcher ?? new AnythingMatcher());
    }

    public static function oneOrMore($matcher = null): SliceMatcher
    {
        return new SliceMatcher(1, PHP_INT_MAX, $matcher ?? new AnythingMatcher());
    }

    public static function spacer(int $min = 1, ?int $max = null): SliceMatcher
    {
        return new SliceMatcher($min, $max ?? $min, new AnythingMatcher());
    }

    // ─── Captures ────────────────────────────────────────────────────────────

    public static function capture($matcher = null): CapturedMatcher
    {
        return new CapturedMatcher($matcher);
    }

    public static function captureCollector($matcher = null): CapturesCollectorMatcher
    {
        return new CapturesCollectorMatcher($matcher);
    }

    public static function containerOf($containedMatcher): ContainerOfMatcher
    {
        return new ContainerOfMatcher($containedMatcher);
    }

    public static function fromCapture(CapturedMatcher $capturedMatcher): FromCaptureMatcher
    {
        return new FromCaptureMatcher($capturedMatcher);
    }
}
