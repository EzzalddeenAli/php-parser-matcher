<?php

namespace Fleet\AstMatcher\Facade;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

/**
 * Code builder: same API shape as Ast, but accepts concrete values and returns
 * PhpParser Nodes that can be pretty-printed back to PHP source.
 *
 * String shortcuts:
 *   - $class / $callee params: string → new Name($s)
 *   - $name / $method params:  string → new Identifier($s)
 *
 * Usage:
 *   $node = Build::staticCall('Text', 'make', [
 *       Build::arg(Build::string('Name')),
 *   ]);
 *   echo Build::print($node);  // Text::make('Name')
 */
class Build
{
    private static array $binaryOpMap = [
        '+'   => BinaryOp\Plus::class,
        '-'   => BinaryOp\Minus::class,
        '*'   => BinaryOp\Mul::class,
        '/'   => BinaryOp\Div::class,
        '%'   => BinaryOp\Mod::class,
        '**'  => BinaryOp\Pow::class,
        '.'   => BinaryOp\Concat::class,
        '=='  => BinaryOp\Equal::class,
        '!='  => BinaryOp\NotEqual::class,
        '===' => BinaryOp\Identical::class,
        '!==' => BinaryOp\NotIdentical::class,
        '<'   => BinaryOp\Smaller::class,
        '<='  => BinaryOp\SmallerOrEqual::class,
        '>'   => BinaryOp\Greater::class,
        '>='  => BinaryOp\GreaterOrEqual::class,
        '&&'  => BinaryOp\BooleanAnd::class,
        '||'  => BinaryOp\BooleanOr::class,
        'and' => BinaryOp\LogicalAnd::class,
        'or'  => BinaryOp\LogicalOr::class,
        'xor' => BinaryOp\LogicalXor::class,
        '&'   => BinaryOp\BitwiseAnd::class,
        '|'   => BinaryOp\BitwiseOr::class,
        '^'   => BinaryOp\BitwiseXor::class,
        '<<'  => BinaryOp\ShiftLeft::class,
        '>>'  => BinaryOp\ShiftRight::class,
        '??'  => BinaryOp\Coalesce::class,
        '<=>' => BinaryOp\Spaceship::class,
    ];

    private static array $assignOpMap = [
        '+='  => AssignOp\Plus::class,
        '-='  => AssignOp\Minus::class,
        '*='  => AssignOp\Mul::class,
        '/='  => AssignOp\Div::class,
        '%='  => AssignOp\Mod::class,
        '**=' => AssignOp\Pow::class,
        '.='  => AssignOp\Concat::class,
        '&='  => AssignOp\BitwiseAnd::class,
        '|='  => AssignOp\BitwiseOr::class,
        '^='  => AssignOp\BitwiseXor::class,
        '<<=' => AssignOp\ShiftLeft::class,
        '>>=' => AssignOp\ShiftRight::class,
        '??=' => AssignOp\Coalesce::class,
    ];

    private static array $castMap = [
        'int'    => Cast\Int_::class,
        'float'  => Cast\Double::class,
        'string' => Cast\String_::class,
        'bool'   => Cast\Bool_::class,
        'array'  => Cast\Array_::class,
        'object' => Cast\Object_::class,
        'unset'  => Cast\Unset_::class,
    ];

    private static array $unaryMap = [
        '!'    => Expr\BooleanNot::class,
        '~'    => Expr\BitwiseNot::class,
        '-'    => Expr\UnaryMinus::class,
        '+'    => Expr\UnaryPlus::class,
        '++'   => Expr\PreInc::class,
        '--'   => Expr\PreDec::class,
        '++$'  => Expr\PostInc::class,
        '--$'  => Expr\PostDec::class,
    ];

    // ─── Coercion helpers ─────────────────────────────────────────────────────

    private static function toName(mixed $v): Name|Expr
    {
        return is_string($v) ? new Name($v) : $v;
    }

    private static function toIdentifier(mixed $v): Identifier|Expr
    {
        return is_string($v) ? new Identifier($v) : $v;
    }

    // ─── Scalars ─────────────────────────────────────────────────────────────

    public static function string(string $value): Scalar\String_
    {
        return new Scalar\String_($value);
    }

    public static function int(int $value): Scalar\LNumber
    {
        return new Scalar\LNumber($value);
    }

    public static function float(float $value): Scalar\DNumber
    {
        return new Scalar\DNumber($value);
    }

    public static function true(): Expr\ConstFetch
    {
        return new Expr\ConstFetch(new Name('true'));
    }

    public static function false(): Expr\ConstFetch
    {
        return new Expr\ConstFetch(new Name('false'));
    }

    public static function null(): Expr\ConstFetch
    {
        return new Expr\ConstFetch(new Name('null'));
    }

    public static function constFetch(string $name): Expr\ConstFetch
    {
        return new Expr\ConstFetch(new Name($name));
    }

    // ─── Names ───────────────────────────────────────────────────────────────

    public static function name(string $name): Name
    {
        return new Name($name);
    }

    public static function identifier(string $name): Identifier
    {
        return new Identifier($name);
    }

    public static function variable(string $name): Expr\Variable
    {
        return new Expr\Variable($name);
    }

    // ─── Calls ───────────────────────────────────────────────────────────────

    /** @param Node\Arg[] $args */
    public static function callExpression(mixed $callee, array $args = []): Expr\FuncCall
    {
        return new Expr\FuncCall(static::toName($callee), $args);
    }

    /** @param Node\Arg[] $args */
    public static function methodCall(Expr $object, mixed $name, array $args = []): Expr\MethodCall
    {
        return new Expr\MethodCall($object, static::toIdentifier($name), $args);
    }

    /** @param Node\Arg[] $args */
    public static function nullsafeCall(Expr $object, mixed $name, array $args = []): Expr\NullsafeMethodCall
    {
        return new Expr\NullsafeMethodCall($object, static::toIdentifier($name), $args);
    }

    /** @param Node\Arg[] $args */
    public static function staticCall(mixed $class, mixed $name, array $args = []): Expr\StaticCall
    {
        return new Expr\StaticCall(static::toName($class), static::toIdentifier($name), $args);
    }

    // ─── Access ──────────────────────────────────────────────────────────────

    public static function propertyFetch(Expr $object, mixed $name): Expr\PropertyFetch
    {
        return new Expr\PropertyFetch($object, static::toIdentifier($name));
    }

    public static function nullsafeProp(Expr $object, mixed $name): Expr\NullsafePropertyFetch
    {
        return new Expr\NullsafePropertyFetch($object, static::toIdentifier($name));
    }

    public static function classConstFetch(mixed $class, mixed $name): Expr\ClassConstFetch
    {
        return new Expr\ClassConstFetch(static::toName($class), static::toIdentifier($name));
    }

    public static function arrayAccess(Expr $var, ?Expr $dim = null): Expr\ArrayDimFetch
    {
        return new Expr\ArrayDimFetch($var, $dim);
    }

    // ─── Assignment ──────────────────────────────────────────────────────────

    public static function assign(Expr $var, Expr $expr): Expr\Assign
    {
        return new Expr\Assign($var, $expr);
    }

    public static function assignOp(string $operator, Expr $var, Expr $expr): Expr\AssignOp
    {
        $class = self::$assignOpMap[$operator]
            ?? throw new \InvalidArgumentException("Unknown assign operator: $operator");
        return new $class($var, $expr);
    }

    // ─── Operations ──────────────────────────────────────────────────────────

    public static function binaryOp(string $operator, Expr $left, Expr $right): Expr\BinaryOp
    {
        $class = self::$binaryOpMap[$operator]
            ?? throw new \InvalidArgumentException("Unknown binary operator: $operator");
        return new $class($left, $right);
    }

    public static function logicalExpression(string $operator, Expr $left, Expr $right): Expr\BinaryOp
    {
        return static::binaryOp($operator, $left, $right);
    }

    public static function ternary(Expr $cond, ?Expr $if, Expr $else): Expr\Ternary
    {
        return new Expr\Ternary($cond, $if, $else);
    }

    public static function cast(string $type, Expr $expr): Expr\Cast
    {
        $class = self::$castMap[strtolower($type)]
            ?? throw new \InvalidArgumentException("Unknown cast type: $type");
        return new $class($expr);
    }

    public static function unaryOp(string $operator, Expr $expr): Expr
    {
        $class = self::$unaryMap[$operator]
            ?? throw new \InvalidArgumentException("Unknown unary operator: $operator");
        return new $class($expr);
    }

    // ─── Objects ─────────────────────────────────────────────────────────────

    /** @param Node\Arg[] $args */
    public static function new(mixed $class, array $args = []): Expr\New_
    {
        return new Expr\New_(static::toName($class), $args);
    }

    public static function instanceof(Expr $expr, mixed $class): Expr\Instanceof_
    {
        return new Expr\Instanceof_($expr, static::toName($class));
    }

    // ─── Functions ───────────────────────────────────────────────────────────

    /** @param Node\Param[] $params  @param Stmt[] $stmts */
    public static function closure(array $params = [], array $stmts = [], bool $static = false): Expr\Closure
    {
        return new Expr\Closure(['params' => $params, 'stmts' => $stmts, 'static' => $static]);
    }

    /** @param Node\Param[] $params */
    public static function arrowFn(array $params, Expr $expr, bool $static = false): Expr\ArrowFunction
    {
        return new Expr\ArrowFunction(['params' => $params, 'expr' => $expr, 'static' => $static]);
    }

    // ─── Expressions ─────────────────────────────────────────────────────────

    /** @param Node\ArrayItem[] $items */
    public static function array(array $items = []): Expr\Array_
    {
        return new Expr\Array_($items);
    }

    public static function throw(Expr $expr): Expr\Throw_
    {
        return new Expr\Throw_($expr);
    }

    /** @param Node\MatchArm[] $arms */
    public static function matchExpr(Expr $subject, array $arms = []): Expr\Match_
    {
        return new Expr\Match_($subject, $arms);
    }

    // ─── Sub-nodes ───────────────────────────────────────────────────────────

    public static function arg(Expr $value, ?Identifier $name = null): Node\Arg
    {
        return new Node\Arg($value, false, false, [], $name);
    }

    public static function namedArg(string $key, Expr $value): Node\Arg
    {
        return new Node\Arg($value, false, false, [], new Identifier($key));
    }

    public static function arrayItem(Expr $value, ?Expr $key = null, bool $byRef = false): Node\ArrayItem
    {
        return new Node\ArrayItem($value, $key, $byRef);
    }

    /**
     * @param string|Identifier $name  Variable name (without $)
     * @param mixed             $type  Type hint node or null
     */
    public static function param(mixed $name, mixed $type = null, ?Expr $default = null): Node\Param
    {
        $var = is_string($name) ? new Expr\Variable($name) : $name;
        return new Node\Param($var, $default, $type);
    }

    // ─── Statements ──────────────────────────────────────────────────────────

    public static function return(?Expr $value = null): Stmt\Return_
    {
        return new Stmt\Return_($value);
    }

    public static function if(Expr $cond, array $stmts = [], array $elseifs = [], ?Stmt\Else_ $else = null): Stmt\If_
    {
        return new Stmt\If_($cond, ['stmts' => $stmts, 'elseifs' => $elseifs, 'else' => $else]);
    }

    public static function elseIf(Expr $cond, array $stmts = []): Stmt\ElseIf_
    {
        return new Stmt\ElseIf_($cond, $stmts);
    }

    public static function else(array $stmts = []): Stmt\Else_
    {
        return new Stmt\Else_($stmts);
    }

    public static function foreach(Expr $expr, Expr $valueVar, ?Expr $keyVar = null, array $stmts = []): Stmt\Foreach_
    {
        return new Stmt\Foreach_($expr, $valueVar, ['keyVar' => $keyVar, 'stmts' => $stmts]);
    }

    public static function while(Expr $cond, array $stmts = []): Stmt\While_
    {
        return new Stmt\While_($cond, $stmts);
    }

    public static function doWhile(array $stmts, Expr $cond): Stmt\Do_
    {
        return new Stmt\Do_($cond, $stmts);
    }

    public static function for(array $init = [], array $cond = [], array $loop = [], array $stmts = []): Stmt\For_
    {
        return new Stmt\For_(['init' => $init, 'cond' => $cond, 'loop' => $loop, 'stmts' => $stmts]);
    }

    public static function echo(Expr ...$exprs): Stmt\Echo_
    {
        return new Stmt\Echo_($exprs);
    }

    public static function break(?Expr $num = null): Stmt\Break_
    {
        return new Stmt\Break_($num);
    }

    public static function continue(?Expr $num = null): Stmt\Continue_
    {
        return new Stmt\Continue_($num);
    }

    /** @param Stmt\Catch_[]  $catches */
    public static function tryCatch(array $stmts, array $catches = [], ?Stmt\Finally_ $finally = null): Stmt\TryCatch
    {
        return new Stmt\TryCatch($stmts, $catches, $finally);
    }

    /** @param Name[] $types */
    public static function catch(array $types, ?Expr\Variable $var, array $stmts = []): Stmt\Catch_
    {
        return new Stmt\Catch_($types, $var, $stmts);
    }

    public static function finally(array $stmts): Stmt\Finally_
    {
        return new Stmt\Finally_($stmts);
    }

    /** @param Stmt\Case_[] $cases */
    public static function switch(Expr $cond, array $cases): Stmt\Switch_
    {
        return new Stmt\Switch_($cond, $cases);
    }

    public static function case(?Expr $cond, array $stmts = []): Stmt\Case_
    {
        return new Stmt\Case_($cond, $stmts);
    }

    // ─── Declarations ────────────────────────────────────────────────────────

    /** @param Node\Param[] $params  @param Stmt[] $stmts */
    public static function functionDeclaration(mixed $name, array $params = [], array $stmts = []): Stmt\Function_
    {
        $id = is_string($name) ? new Identifier($name) : $name;
        return new Stmt\Function_($id, ['params' => $params, 'stmts' => $stmts]);
    }

    /** @param Stmt[] $stmts */
    public static function classDeclaration(mixed $name, mixed $extends = null, array $stmts = []): Stmt\Class_
    {
        $id  = is_string($name) ? new Identifier($name) : $name;
        $ext = $extends !== null ? static::toName($extends) : null;
        return new Stmt\Class_($id, ['extends' => $ext, 'stmts' => $stmts]);
    }

    /** @param Node\Param[] $params  @param Stmt[]|null $stmts */
    public static function classMethod(mixed $name, array $params = [], ?array $stmts = [], bool $static = false): Stmt\ClassMethod
    {
        $id    = is_string($name) ? new Identifier($name) : $name;
        $flags = $static ? Stmt\Class_::MODIFIER_PUBLIC | Stmt\Class_::MODIFIER_STATIC
                         : Stmt\Class_::MODIFIER_PUBLIC;
        return new Stmt\ClassMethod($id, ['flags' => $flags, 'params' => $params, 'stmts' => $stmts]);
    }

    public static function classProperty(mixed $name, ?Expr $default = null, bool $static = false): Stmt\Property
    {
        $id    = is_string($name) ? new Node\VarLikeIdentifier($name) : $name;
        $flags = Stmt\Class_::MODIFIER_PUBLIC | ($static ? Stmt\Class_::MODIFIER_STATIC : 0);
        return new Stmt\Property($flags, [new Node\PropertyItem($id, $default)]);
    }

    /** @param Stmt[] $stmts */
    public static function trait(mixed $name, array $stmts = []): Stmt\Trait_
    {
        $id = is_string($name) ? new Identifier($name) : $name;
        return new Stmt\Trait_($id, ['stmts' => $stmts]);
    }

    /** @param Name[] $extends  @param Stmt[] $stmts */
    public static function interface(mixed $name, array $extends = [], array $stmts = []): Stmt\Interface_
    {
        $id = is_string($name) ? new Identifier($name) : $name;
        return new Stmt\Interface_($id, ['extends' => $extends, 'stmts' => $stmts]);
    }

    /** @param Stmt[] $stmts */
    public static function enum(mixed $name, mixed $scalarType = null, array $stmts = []): Stmt\Enum_
    {
        $id   = is_string($name) ? new Identifier($name) : $name;
        $type = is_string($scalarType) ? new Identifier($scalarType) : $scalarType;
        return new Stmt\Enum_($id, ['scalarType' => $type, 'stmts' => $stmts]);
    }

    public static function enumCase(mixed $name, ?Expr $expr = null): Stmt\EnumCase
    {
        $id = is_string($name) ? new Identifier($name) : $name;
        return new Stmt\EnumCase($id, $expr);
    }

    /** @param Stmt[]|null $stmts */
    public static function namespace(mixed $name, ?array $stmts = null): Stmt\Namespace_
    {
        $n = is_string($name) ? new Name($name) : $name;
        return new Stmt\Namespace_($n, $stmts);
    }

    public static function use(string $name, ?string $alias = null): Stmt\Use_
    {
        $aliaId = $alias !== null ? new Identifier($alias) : null;
        return new Stmt\Use_([new Node\UseItem(new Name($name), $aliaId)]);
    }

    /** @param Name[] $traits */
    public static function traitUse(array $traits): Stmt\TraitUse
    {
        return new Stmt\TraitUse($traits);
    }

    /** @param Node\Arg[] $args */
    public static function attribute(mixed $name, array $args = []): Node\Attribute
    {
        return new Node\Attribute(static::toName($name), $args);
    }

    // ─── Printing ─────────────────────────────────────────────────────────────

    /**
     * Pretty-print a node to PHP source code.
     * Expressions are returned without trailing semicolon.
     */
    public static function print(Node $node): string
    {
        $pp = new Standard();
        if ($node instanceof Expr) {
            return $pp->prettyPrintExpr($node);
        }
        return $pp->prettyPrint([$node]);
    }

    public static function printExpr(Expr $expr): string
    {
        return (new Standard())->prettyPrintExpr($expr);
    }

    public static function printStatement(Stmt $stmt): string
    {
        return (new Standard())->prettyPrint([$stmt]);
    }

    /**
     * Wrap expression in Stmt\Expression and print as statement (with semicolon).
     */
    public static function printExprAsStatement(Expr $expr): string
    {
        return static::printStatement(new Stmt\Expression($expr));
    }
}
