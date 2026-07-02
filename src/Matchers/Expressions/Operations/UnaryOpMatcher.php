<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr;

class UnaryOpMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    private static array $opMap = [
        '!'   => Expr\BooleanNot::class,
        '~'   => Expr\BitwiseNot::class,
        '-'   => Expr\UnaryMinus::class,
        '+'   => Expr\UnaryPlus::class,
        '++'  => Expr\PreInc::class,
        '--'  => Expr\PreDec::class,
        '++$' => Expr\PostInc::class,
        '--$' => Expr\PostDec::class,
    ];

    public function __construct(
        private readonly ?string  $operator = null,
        private readonly ?Matcher $expr     = null,
    ) {}

    protected function matchesNodeType(mixed $node): bool
    {
        return $node instanceof Expr\BooleanNot
            || $node instanceof Expr\BitwiseNot
            || $node instanceof Expr\UnaryMinus
            || $node instanceof Expr\UnaryPlus
            || $node instanceof Expr\PreInc
            || $node instanceof Expr\PreDec
            || $node instanceof Expr\PostInc
            || $node instanceof Expr\PostDec;
    }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->operator !== null) {
            $expectedClass = self::$opMap[$this->operator] ?? null;
            if ($expectedClass !== null && !($node instanceof $expectedClass)) return false;
        }
        $sub = property_exists($node, 'expr') ? $node->expr : ($node->var ?? null);
        return $this->matchField($this->expr, $sub, $keys, 'expr');
    }
}
