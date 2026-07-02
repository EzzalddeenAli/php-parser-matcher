<?php

namespace Fleet\AstMatcher\Core;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;

class NodeTypes
{
    public static function isNode($node): bool
    {
        return $node instanceof Node || is_subclass_of($node, Node::class);
    }

    public static function isStatement($node): bool
    {
        return $node instanceof Stmt || is_subclass_of($node, Stmt::class);
    }

    public static function isExpression($node): bool
    {
        return $node instanceof Node\Expr || is_subclass_of($node, Node\Expr::class);
    }

    public static function isExpressionStatement($node): bool
    {
        return $node instanceof Expression;
    }

    public static function isFunction($node): bool
    {
        return $node instanceof Function_;
    }

    public static function isClassMethod($node): bool
    {
        return $node instanceof Stmt\ClassMethod;
    }

    public static function isArrayExpression($node): bool
    {
        return $node instanceof Array_;
    }

    public static function isClassDeclaration($node): bool
    {
        return $node instanceof Class_;
    }

    public static function isIdentifier($node): bool
    {
        return $node instanceof Identifier || is_subclass_of($node, Identifier::class);
    }

    public static function isArrayItem($node): bool
    {
        return $node instanceof Node\ArrayItem || is_subclass_of($node, Node\ArrayItem::class);
    }

    public static function isName($node): bool
    {
        return $node instanceof Name || is_subclass_of($node, Name::class);
    }

    public static function isVariable($node): bool
    {
        return $node instanceof Variable;
    }

    public static function isFunctionDeclaration($node): bool
    {
        return $node instanceof Function_;
    }

    public static function isCallExpression($node): bool
    {
        return $node instanceof FuncCall;
    }

    public static function isMemberExpression($node): bool
    {
        return $node instanceof PropertyFetch;
    }

    public static function isNullsafePropertyFetch($node): bool
    {
        return $node instanceof NullsafePropertyFetch;
    }

    public static function isClassConstFetch($node): bool
    {
        return $node instanceof Node\Expr\ClassConstFetch;
    }

    public static function isMethodCall($node): bool
    {
        return $node instanceof MethodCall;
    }

    public static function isNullsafeMethodCall($node): bool
    {
        return $node instanceof NullsafeMethodCall;
    }

    public static function isStaticCall($node): bool
    {
        return $node instanceof StaticCall;
    }

    public static function isArg($node): bool
    {
        return $node instanceof Arg;
    }

    public static function isParam($node): bool
    {
        return $node instanceof Node\Param;
    }

    public static function isStringLiteral($node): bool
    {
        return $node instanceof String_;
    }

    public static function isClassProperty($node): bool
    {
        return $node instanceof Stmt\Property;
    }

    public static function isReturnStatement($node): bool
    {
        return $node instanceof Stmt\Return_;
    }

    public static function isBinaryOpExpression($node): bool
    {
        return $node instanceof Node\Expr\BinaryOp;
    }

    public static function isClosure($node): bool
    {
        return $node instanceof Node\Expr\Closure;
    }

    public static function isArrowFunction($node): bool
    {
        return $node instanceof Node\Expr\ArrowFunction;
    }

    public static function isNew($node): bool
    {
        return $node instanceof Node\Expr\New_;
    }

    public static function isInstanceof($node): bool
    {
        return $node instanceof Node\Expr\Instanceof_;
    }

    public static function isAssign($node): bool
    {
        return $node instanceof Node\Expr\Assign;
    }

    public static function isAssignOp($node): bool
    {
        return $node instanceof Node\Expr\AssignOp;
    }

    public static function isTernary($node): bool
    {
        return $node instanceof Node\Expr\Ternary;
    }

    public static function isArrayDimFetch($node): bool
    {
        return $node instanceof Node\Expr\ArrayDimFetch;
    }

    public static function isConstFetch($node): bool
    {
        return $node instanceof Node\Expr\ConstFetch;
    }

    public static function isMatch($node): bool
    {
        return $node instanceof Node\Expr\Match_;
    }

    public static function isThrow($node): bool
    {
        return $node instanceof Node\Expr\Throw_;
    }

    public static function isCast($node): bool
    {
        return $node instanceof Node\Expr\Cast;
    }

    public static function isUnaryOp($node): bool
    {
        return $node instanceof Node\Expr\UnaryMinus
            || $node instanceof Node\Expr\UnaryPlus
            || $node instanceof Node\Expr\BooleanNot
            || $node instanceof Node\Expr\BitwiseNot;
    }

    public static function isTrait($node): bool
    {
        return $node instanceof Stmt\Trait_;
    }

    public static function isInterface($node): bool
    {
        return $node instanceof Stmt\Interface_;
    }

    public static function isEnum($node): bool
    {
        return $node instanceof Stmt\Enum_;
    }

    public static function isEnumCase($node): bool
    {
        return $node instanceof Stmt\EnumCase;
    }

    public static function isNamespace($node): bool
    {
        return $node instanceof Stmt\Namespace_;
    }

    public static function isUseStatement($node): bool
    {
        return $node instanceof Stmt\Use_;
    }

    public static function isTraitUse($node): bool
    {
        return $node instanceof Stmt\TraitUse;
    }

    public static function isAttribute($node): bool
    {
        return $node instanceof Node\Attribute;
    }

    // ─── Control Flow ────────────────────────────────────────────────────────

    public static function isIf($node): bool
    {
        return $node instanceof Stmt\If_;
    }

    public static function isElseIf($node): bool
    {
        return $node instanceof Stmt\ElseIf_;
    }

    public static function isElse($node): bool
    {
        return $node instanceof Stmt\Else_;
    }

    public static function isForeach($node): bool
    {
        return $node instanceof Stmt\Foreach_;
    }

    public static function isWhile($node): bool
    {
        return $node instanceof Stmt\While_;
    }

    public static function isDoWhile($node): bool
    {
        return $node instanceof Stmt\Do_;
    }

    public static function isFor($node): bool
    {
        return $node instanceof Stmt\For_;
    }

    public static function isTryCatch($node): bool
    {
        return $node instanceof Stmt\TryCatch;
    }

    public static function isCatch($node): bool
    {
        return $node instanceof Stmt\Catch_;
    }

    public static function isFinally($node): bool
    {
        return $node instanceof Stmt\Finally_;
    }

    public static function isSwitch($node): bool
    {
        return $node instanceof Stmt\Switch_;
    }

    public static function isCase($node): bool
    {
        return $node instanceof Stmt\Case_;
    }

    public static function isEcho($node): bool
    {
        return $node instanceof Stmt\Echo_;
    }

    public static function isBreak($node): bool
    {
        return $node instanceof Stmt\Break_;
    }

    public static function isContinue($node): bool
    {
        return $node instanceof Stmt\Continue_;
    }
}
