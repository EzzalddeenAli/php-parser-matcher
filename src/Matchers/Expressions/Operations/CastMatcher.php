<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class CastMatcher extends Matcher
{
    private ?string $type;
    private $expr;

    private static array $castMap = [
        'int'    => \PhpParser\Node\Expr\Cast\Int_::class,
        'float'  => \PhpParser\Node\Expr\Cast\Double::class,
        'string' => \PhpParser\Node\Expr\Cast\String_::class,
        'bool'   => \PhpParser\Node\Expr\Cast\Bool_::class,
        'array'  => \PhpParser\Node\Expr\Cast\Array_::class,
        'object' => \PhpParser\Node\Expr\Cast\Object_::class,
        'unset'  => \PhpParser\Node\Expr\Cast\Unset_::class,
    ];

    public function __construct(?string $type = null, $expr = null)
    {
        $this->type = $type;
        $this->expr = $expr;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isCast($node)) {
            return false;
        }
        if ($this->type !== null) {
            $expectedClass = self::$castMap[strtolower($this->type)] ?? null;
            if ($expectedClass && !($node instanceof $expectedClass)) {
                return false;
            }
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        return true;
    }
}
