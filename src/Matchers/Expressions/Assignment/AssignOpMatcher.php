<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Assignment;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\AssignOp;

class AssignOpMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

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

    public function __construct(
        private readonly ?string  $operator = null,
        private readonly ?Matcher $var      = null,
        private readonly ?Matcher $expr     = null,
    ) {}

    protected function nodeClass(): string { return AssignOp::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->operator !== null) {
            $expectedClass = self::$opMap[$this->operator] ?? null;
            if ($expectedClass !== null && !($node instanceof $expectedClass)) return false;
        }
        return $this->matchField($this->var,  $node->var,  $keys, 'var')
            && $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
