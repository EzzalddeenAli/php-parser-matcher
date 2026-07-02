<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use PhpParser\Node\Expr;

class UnaryOpMatcher extends Matcher
{
    private ?string $operator;
    private $expr;

    private static array $opMap = [
        '!'  => Expr\BooleanNot::class,
        '~'  => Expr\BitwiseNot::class,
        '-'  => Expr\UnaryMinus::class,
        '+'  => Expr\UnaryPlus::class,
        '++' => Expr\PreInc::class,
        '--' => Expr\PreDec::class,
        '++$' => Expr\PostInc::class,
        '--$' => Expr\PostDec::class,
    ];

    public function __construct(?string $operator = null, $expr = null)
    {
        $this->operator = $operator;
        $this->expr = $expr;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isUnaryOp($node)) {
            return false;
        }
        if ($this->operator !== null) {
            $expectedClass = self::$opMap[$this->operator] ?? null;
            if ($expectedClass && !($node instanceof $expectedClass)) {
                return false;
            }
        }
        if ($this->expr !== null) {
            $sub = property_exists($node, 'expr') ? $node->expr : ($node->var ?? null);
            if (!$this->expr->matchValue($sub, array_merge($keys, ['expr']))) {
                return false;
            }
        }
        return true;
    }
}
