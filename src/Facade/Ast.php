<?php

namespace Fleet\AstMatcher\Facade;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Matchers\Captures\CapturedMatcher;
use Fleet\AstMatcher\Matchers\Captures\CaptureGroup;
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
use Fleet\AstMatcher\Matchers\Expressions\Calls\ChainCallMatcher;
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
use Fleet\AstMatcher\Matchers\Generic\AnyNodeMatcher;
use Fleet\AstMatcher\Matchers\Generic\AnyStatementMatcher;
use Fleet\AstMatcher\Matchers\Generic\AnythingMatcher;
use Fleet\AstMatcher\Matchers\Generic\OneOfMatcher;
use Fleet\AstMatcher\Matchers\Generic\OrMatcher;
use Fleet\AstMatcher\Matchers\Generic\PredicateMatcher;
use Fleet\AstMatcher\Matchers\Names\IdentifierMatcher;
use Fleet\AstMatcher\Matchers\Names\VariableMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ArrayItemMatcher;
use Fleet\AstMatcher\Matchers\Nodes\AttributeMatcher;
use Fleet\AstMatcher\Matchers\Nodes\CaseMatcher;
use Fleet\AstMatcher\Matchers\Nodes\CatchMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ElseIfMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ElseMatcher;
use Fleet\AstMatcher\Matchers\Nodes\FinallyMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ParamMatcher;
use Fleet\AstMatcher\Matchers\Nodes\TraitUseMatcher;
use Fleet\AstMatcher\Matchers\Scalars\AnyNumberMatcher;
use Fleet\AstMatcher\Matchers\Scalars\AnyStringMatcher;
use Fleet\AstMatcher\Matchers\Scalars\NumberLiteralMatcher;
use Fleet\AstMatcher\Matchers\Scalars\StringLiteralMatcher;
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

/**
 * Static facade for building AST matchers.
 *
 * All parameters are optional — null means "wildcard" (matches anything).
 * Reserved-word methods (if, else, for, …) are valid PHP class method names.
 */
class Ast
{
    // ─── Generic ─────────────────────────────────────────────────────────────

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

    public static function or(mixed ...$matchers): OrMatcher
    {
        return new OrMatcher(...$matchers);
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

    /** @param Matcher|string|null $value */
    public static function stringLiteral(mixed $value = null): StringLiteralMatcher
    {
        return new StringLiteralMatcher($value);
    }

    /** Alias for stringLiteral() */
    public static function string(mixed $value = null): StringLiteralMatcher
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

    /** @param Matcher|string|null $name */
    public static function identifier(mixed $name = null): IdentifierMatcher
    {
        return new IdentifierMatcher($name);
    }

    /** Alias for identifier() */
    public static function name(mixed $name = null): IdentifierMatcher
    {
        return new IdentifierMatcher($name);
    }

    /** Alias for identifier() */
    public static function oneNameOf(array $names = null): OrMatcher
    {
        $matchers=array_map(fn($name) => new IdentifierMatcher($name),$names);
        return new OrMatcher(...$matchers);
    }

    /** @param Matcher|string|null $name */
    public static function variable(mixed $name = null): VariableMatcher
    {
        return new VariableMatcher($name);
    }

    /** Alias for variable() */
    public static function var(mixed $name = null): VariableMatcher
    {
        return new VariableMatcher($name);
    }

    // ─── Calls ───────────────────────────────────────────────────────────────

    /** @param array|Matcher|null $args */
    public static function callExpression(?Matcher $callee = null, mixed $args = null): CallExpressionMatcher
    {
        return new CallExpressionMatcher($callee, $args);
    }

    /** Alias for callExpression() */
    public static function call(?Matcher $callee = null, mixed $args = null): CallExpressionMatcher
    {
        return new CallExpressionMatcher($callee, $args);
    }

    /** @param array|Matcher|null $args */
    public static function methodCall(?Matcher $object = null, ?Matcher $name = null, mixed $args = null): MethodCallMatcher
    {
        return new MethodCallMatcher($object, $name, $args);
    }

    /** @param array|Matcher|null $args */
    public static function staticCall(?Matcher $class = null, ?Matcher $name = null, mixed $args = null): StaticCallMatcher
    {
        return new StaticCallMatcher($class, $name, $args);
    }

    /** @param array|Matcher|null $args */
    public static function nullsafeCall(?Matcher $object = null, ?Matcher $name = null, mixed $args = null): NullsafeMethodCallMatcher
    {
        return new NullsafeMethodCallMatcher($object, $name, $args);
    }

    /**
     * Match a method/static/function call chain as a whole unit.
     *
     * Unlike staticCall() / methodCall() (which match a single node),
     * chain() flattens the full chain and lets you assert conditions across
     * the root AND individual chain calls in one fluent expression.
     *
     * All conditions are ANDed together.
     *
     *   Ast::chain()
     *       ->rootClass('Text')        // StaticCall root class
     *       ->rootMethod('make')       // root method name
     *       ->hasCall('sortable')      // chain must contain ->sortable()
     *       ->lacksCall('hideFromIndex')
     *       ->callArgs('rules', [Ast::arg(Ast::string('required'))])
     */
    public static function chain(): ChainCallMatcher
    {
        return new ChainCallMatcher();
    }

    /** Alias for chain() */
    public static function chainCall(): ChainCallMatcher
    {
        return new ChainCallMatcher();
    }

    // ─── Access ──────────────────────────────────────────────────────────────

    public static function propertyFetch(?Matcher $object = null, ?Matcher $property = null): PropertyFetchMatcher
    {
        return new PropertyFetchMatcher($object, $property);
    }

    /** Alias for propertyFetch() */
    public static function memberExpression(?Matcher $object = null, ?Matcher $property = null): PropertyFetchMatcher
    {
        return new PropertyFetchMatcher($object, $property);
    }

    public static function nullsafeProp(?Matcher $object = null, ?Matcher $property = null): NullsafePropertyFetchMatcher
    {
        return new NullsafePropertyFetchMatcher($object, $property);
    }

    public static function classConstFetch(?Matcher $class = null, ?Matcher $name = null): ClassConstFetchMatcher
    {
        return new ClassConstFetchMatcher($class, $name);
    }

    /** @param Matcher|string|null $name */
    public static function constFetch(mixed $name = null): ConstFetchMatcher
    {
        return new ConstFetchMatcher($name);
    }

    public static function true(): ConstFetchMatcher
    {
        return new ConstFetchMatcher('true');
    }

    public static function false(): ConstFetchMatcher
    {
        return new ConstFetchMatcher('false');
    }

    public static function null(): ConstFetchMatcher
    {
        return new ConstFetchMatcher('null');
    }

    public static function arrayAccess(?Matcher $var = null, ?Matcher $dim = null): ArrayDimFetchMatcher
    {
        return new ArrayDimFetchMatcher($var, $dim);
    }

    /** Alias for arrayAccess() */
    public static function arrayDimFetch(?Matcher $var = null, ?Matcher $dim = null): ArrayDimFetchMatcher
    {
        return new ArrayDimFetchMatcher($var, $dim);
    }

    // ─── Assignment ──────────────────────────────────────────────────────────

    public static function assign(?Matcher $var = null, ?Matcher $expr = null): AssignMatcher
    {
        return new AssignMatcher($var, $expr);
    }

    public static function assignOp(?string $operator = null, ?Matcher $var = null, ?Matcher $expr = null): AssignOpMatcher
    {
        return new AssignOpMatcher($operator, $var, $expr);
    }

    // ─── Operations ──────────────────────────────────────────────────────────

    /** @param Matcher|string|null $operator */
    public static function binaryOp(mixed $operator = null, ?Matcher $left = null, ?Matcher $right = null): BinaryOpMatcher
    {
        return new BinaryOpMatcher($operator, $left, $right);
    }

    /** Alias for binaryOp() */
    public static function logicalExpression(mixed $operator = null, ?Matcher $left = null, ?Matcher $right = null): BinaryOpMatcher
    {
        return new BinaryOpMatcher($operator, $left, $right);
    }

    public static function ternary(?Matcher $cond = null, ?Matcher $if = null, ?Matcher $else = null): TernaryMatcher
    {
        return new TernaryMatcher($cond, $if, $else);
    }

    public static function cast(?string $type = null, ?Matcher $expr = null): CastMatcher
    {
        return new CastMatcher($type, $expr);
    }

    public static function unaryOp(?string $operator = null, ?Matcher $expr = null): UnaryOpMatcher
    {
        return new UnaryOpMatcher($operator, $expr);
    }

    // ─── Objects ─────────────────────────────────────────────────────────────

    /** @param array|Matcher|null $args */
    public static function new(?Matcher $class = null, mixed $args = null): NewMatcher
    {
        return new NewMatcher($class, $args);
    }

    public static function instanceof(?Matcher $expr = null, ?Matcher $class = null): InstanceofMatcher
    {
        return new InstanceofMatcher($expr, $class);
    }

    // ─── Functions ───────────────────────────────────────────────────────────

    /** @param array|Matcher|null $params   @param array|Matcher|null $body */
    public static function closure(mixed $params = null, mixed $body = null, ?bool $static = null): ClosureMatcher
    {
        return new ClosureMatcher($params, $body, $static);
    }

    /** @param array|Matcher|null $params */
    public static function arrowFn(mixed $params = null, ?Matcher $expr = null, ?bool $static = null): ArrowFunctionMatcher
    {
        return new ArrowFunctionMatcher($params, $expr, $static);
    }

    // ─── Expressions ─────────────────────────────────────────────────────────

    /** @param array|Matcher|null $elements array of per-item matchers, or a single Matcher that receives element values directly */
    public static function arrayExpression(array|Matcher|null $elements = null): ArrayExpressionMatcher
    {
        return new ArrayExpressionMatcher($elements);
    }

    /** Alias for arrayExpression() */
    public static function array(array|Matcher|null $elements = null): ArrayExpressionMatcher
    {
        return new ArrayExpressionMatcher($elements);
    }

    public static function throw(?Matcher $expr = null): ThrowExprMatcher
    {
        return new ThrowExprMatcher($expr);
    }

    public static function matchExpr(?Matcher $subject = null, ?Matcher $arms = null): MatchExprMatcher
    {
        return new MatchExprMatcher($subject, $arms);
    }

    // ─── Statements ──────────────────────────────────────────────────────────

    public static function expressionStatement(?Matcher $expr = null): ExpressionStatementMatcher
    {
        return new ExpressionStatementMatcher($expr);
    }

    /** Alias for expressionStatement() */
    public static function statement(?Matcher $expr = null): ExpressionStatementMatcher
    {
        return new ExpressionStatementMatcher($expr);
    }

    public static function return(?Matcher $argument = null): ReturnStatementMatcher
    {
        return new ReturnStatementMatcher($argument);
    }

    /** Alias for return() */
    public static function returnStatement(?Matcher $argument = null): ReturnStatementMatcher
    {
        return new ReturnStatementMatcher($argument);
    }

    // ─── Declarations ────────────────────────────────────────────────────────

    /** @param array|Matcher|null $params   @param array|Matcher|null $body */
    public static function functionDeclaration(?Matcher $name = null, mixed $params = null, mixed $body = null): FunctionDeclarationMatcher
    {
        return new FunctionDeclarationMatcher($name, $params, $body);
    }

    /** @param array|Matcher|null $body */
    public static function classDeclaration(?Matcher $name = null, ?Matcher $extends = null, mixed $body = null): ClassDeclarationMatcher
    {
        return new ClassDeclarationMatcher($name, $extends, $body);
    }

    /** @param array|Matcher|null $params   @param array|Matcher|null $body */
    public static function classMethod(?Matcher $name = null, mixed $params = null, mixed $body = null, ?bool $static = null): ClassMethodMatcher
    {
        return new ClassMethodMatcher($name, $params, $body, $static);
    }

    public static function classProperty(?Matcher $name = null, ?Matcher $default = null, ?bool $static = null): ClassPropertyMatcher
    {
        return new ClassPropertyMatcher($name, $default, $static);
    }

    /** @param array|Matcher|null $body */
    public static function trait(?Matcher $name = null, mixed $body = null): TraitMatcher
    {
        return new TraitMatcher($name, $body);
    }

    /** @param array|Matcher|null $body */
    public static function interface(?Matcher $name = null, ?Matcher $extends = null, mixed $body = null): InterfaceMatcher
    {
        return new InterfaceMatcher($name, $extends, $body);
    }

    /** @param array|Matcher|null $body */
    public static function enum(?Matcher $name = null, ?Matcher $scalarType = null, mixed $body = null): EnumMatcher
    {
        return new EnumMatcher($name, $scalarType, $body);
    }

    public static function enumCase(?Matcher $name = null, ?Matcher $expr = null): EnumCaseMatcher
    {
        return new EnumCaseMatcher($name, $expr);
    }

    public static function namespace(?Matcher $name = null, array|Matcher|null $stmts = null): NamespaceMatcher
    {
        return new NamespaceMatcher($name, $stmts);
    }

    public static function use(?Matcher $name = null, ?Matcher $alias = null): UseStatementMatcher
    {
        return new UseStatementMatcher($name, $alias);
    }

    // ─── Control Flow ────────────────────────────────────────────────────────

    public static function if(?Matcher $cond = null, ?Matcher $then = null, ?Matcher $elseifs = null, ?Matcher $else = null): IfMatcher
    {
        return new IfMatcher($cond, $then, $elseifs, $else);
    }

    public static function elseIf(?Matcher $cond = null, ?Matcher $body = null): ElseIfMatcher
    {
        return new ElseIfMatcher($cond, $body);
    }

    public static function else(?Matcher $body = null): ElseMatcher
    {
        return new ElseMatcher($body);
    }

    public static function foreach(?Matcher $expr = null, ?Matcher $valueVar = null, ?Matcher $keyVar = null, ?Matcher $body = null): ForeachMatcher
    {
        return new ForeachMatcher($expr, $valueVar, $keyVar, $body);
    }

    public static function while(?Matcher $cond = null, ?Matcher $body = null): WhileMatcher
    {
        return new WhileMatcher($cond, $body);
    }

    public static function doWhile(?Matcher $body = null, ?Matcher $cond = null): DoWhileMatcher
    {
        return new DoWhileMatcher($body, $cond);
    }

    public static function for(?Matcher $init = null, ?Matcher $cond = null, ?Matcher $loop = null, ?Matcher $body = null): ForMatcher
    {
        return new ForMatcher($init, $cond, $loop, $body);
    }

    public static function tryCatch(?Matcher $body = null, ?Matcher $catches = null, ?Matcher $finally = null): TryCatchMatcher
    {
        return new TryCatchMatcher($body, $catches, $finally);
    }

    public static function catch(?Matcher $types = null, ?Matcher $var = null, ?Matcher $body = null): CatchMatcher
    {
        return new CatchMatcher($types, $var, $body);
    }

    public static function finally(?Matcher $body = null): FinallyMatcher
    {
        return new FinallyMatcher($body);
    }

    public static function switch(?Matcher $cond = null, ?Matcher $cases = null): SwitchMatcher
    {
        return new SwitchMatcher($cond, $cases);
    }

    public static function case(?Matcher $cond = null, ?Matcher $body = null): CaseMatcher
    {
        return new CaseMatcher($cond, $body);
    }

    public static function echo(?Matcher $exprs = null): EchoMatcher
    {
        return new EchoMatcher($exprs);
    }

    public static function break(?Matcher $num = null): BreakMatcher
    {
        return new BreakMatcher($num);
    }

    public static function continue(?Matcher $num = null): ContinueMatcher
    {
        return new ContinueMatcher($num);
    }

    // ─── Nodes ───────────────────────────────────────────────────────────────

    public static function arg(?Matcher $value = null, ?Matcher $name = null): ArgMatcher
    {
        return new ArgMatcher($value, $name);
    }

    /** Accepts string shorthand: param('userId', 'int') */
    public static function param(Matcher|string|null $name = null, Matcher|string|null $type = null): ParamMatcher
    {
        if (is_string($name)) {
            $name = new VariableMatcher($name);
        }
        if (is_string($type)) {
            $type = new IdentifierMatcher($type);
        }
        return new ParamMatcher($name, $type);
    }

    public static function arrayItem(?Matcher $value = null, ?Matcher $key = null): ArrayItemMatcher
    {
        return new ArrayItemMatcher($value, $key);
    }

    /** @param array|Matcher|null $args */
    public static function attribute(?Matcher $name = null, mixed $args = null): AttributeMatcher
    {
        return new AttributeMatcher($name, $args);
    }

    public static function traitUse(?Matcher $traits = null): TraitUseMatcher
    {
        return new TraitUseMatcher($traits);
    }

    // ─── Collections ─────────────────────────────────────────────────────────

    public static function anyList(mixed ...$matchers): AnyListMatcher
    {
        return new AnyListMatcher($matchers);
    }

    /** Alias for anyList() */
    public static function body(mixed ...$matchers): AnyListMatcher
    {
        return new AnyListMatcher($matchers);
    }

    /** Alias for anyList() */
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

    public static function arrayOf(Matcher $elementMatcher): ArrayOfMatcher
    {
        return new ArrayOfMatcher($elementMatcher);
    }

    public static function slice(int|array $options, ?Matcher $matcher = null): SliceMatcher
    {
        if (is_int($options)) {
            return new SliceMatcher($options, $options, $matcher ?? new AnythingMatcher());
        }
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? PHP_INT_MAX;
        $m   = $options['matcher'] ?? $matcher ?? new AnythingMatcher();
        return new SliceMatcher($min, $max, $m);
    }

    public static function zeroOrMore(?Matcher $matcher = null): SliceMatcher
    {
        return new SliceMatcher(0, PHP_INT_MAX, $matcher ?? new AnythingMatcher());
    }

    public static function oneOrMore(?Matcher $matcher = null): SliceMatcher
    {
        return new SliceMatcher(1, PHP_INT_MAX, $matcher ?? new AnythingMatcher());
    }

    public static function spacer(int $min = 1, ?int $max = null): SliceMatcher
    {
        return new SliceMatcher($min, $max ?? $min, new AnythingMatcher());
    }

    // ─── Captures ────────────────────────────────────────────────────────────

    /**
     * Capture the matched node for later reading.
     *
     *   $cap = Ast::capture(Ast::string());
     *   if ($m->match($node)) { $value = $cap->first(); }
     *
     * For multiple named captures in one pattern use Ast::captures() instead.
     */
    public static function capture(?Matcher $matcher = null): CapturedMatcher
    {
        return new CapturedMatcher($matcher);
    }

    /**
     * Named-capture bag — create once, embed slots by name, read all results
     * from a single object after the match.
     *
     *   $caps = Ast::captures();
     *   $m = Ast::callExpression(
     *       $caps->capture('fn',  Ast::name()),
     *       Ast::anyList(Ast::arg($caps->capture('first', Ast::string())), Ast::zeroOrMore())
     *   );
     *   if ($m->match($node)) {
     *       $fn    = $caps->get('fn');
     *       $first = $caps->get('first');
     *   }
     */
    public static function captures(): CaptureGroup
    {
        return new CaptureGroup();
    }

    /**
     * The process-wide default CaptureGroup.
     *
     * Populated automatically by $matcher->capture('name') calls that do not
     * specify an explicit group.  Reset it with Ast::resetCaptures() — or use
     * Ast::match() which resets it for you — before each new independent match.
     *
     *   $m = Ast::callExpression(Ast::name()->capture('fn'), ...);
     *   Ast::match($m, $node);
     *   Ast::globalCaptures()->get('fn');   // Name node
     */
    public static function globalCaptures(): CaptureGroup
    {
        return CaptureGroup::global();
    }

    /**
     * Clear captured data in the global group so it is ready for the next match.
     * Slot registrations are kept — the same matcher tree can be reused.
     *
     * Usually you do not need to call this directly; use Ast::match() instead.
     */
    public static function resetCaptures(): void
    {
        CaptureGroup::resetGlobal();
    }

    /**
     * Reset global captures, run the match, and return the result.
     *
     * The idiomatic way to run a match when using inline ->capture('name') slots:
     *
     *   $m = Ast::callExpression(
     *       Ast::name()->capture('fn'),
     *       Ast::anyList(Ast::arg(Ast::string()->capture('arg')), Ast::zeroOrMore())
     *   );
     *
     *   if (Ast::match($m, $node)) {
     *       $fnName = Ast::globalCaptures()->get('fn');
     *       $arg    = Ast::globalCaptures()->get('arg');
     *   }
     */
    public static function match(Matcher $matcher, mixed $node): bool
    {
        CaptureGroup::resetGlobal();
        return $matcher->match($node);
    }

    public static function containerOf(Matcher $containedMatcher): ContainerOfMatcher
    {
        return new ContainerOfMatcher($containedMatcher);
    }

    public static function fromCapture(CapturedMatcher $capturedMatcher): FromCaptureMatcher
    {
        return new FromCaptureMatcher($capturedMatcher);
    }
}
