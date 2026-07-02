<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Assignment;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use PhpParser\Node\Expr\AssignOp;

class AssignOpMatcher extends Matcher
{
    private ?string $operator;
    private $var;
    private $expr;

    private static array $opMap = [
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

    public function __construct(?string $operator = null, $var = null, $expr = null)
    {
        $this->operator = $operator;
        $this->var = $var;
        $this->expr = $expr;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isAssignOp($node)) {
            return false;
        }
        if ($this->operator !== null) {
            $expectedClass = self::$opMap[$this->operator] ?? null;
            if ($expectedClass && !($node instanceof $expectedClass)) {
                return false;
            }
        }
        if ($this->var !== null && !$this->var->matchValue($node->var, array_merge($keys, ['var']))) {
            return false;
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        return true;
    }
}
