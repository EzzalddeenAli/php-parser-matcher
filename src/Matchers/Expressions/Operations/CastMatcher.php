<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Cast;

class CastMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    private static array $castMap = [
        'int'    => Cast\Int_::class,
        'float'  => Cast\Double::class,
        'string' => Cast\String_::class,
        'bool'   => Cast\Bool_::class,
        'array'  => Cast\Array_::class,
        'object' => Cast\Object_::class,
        'unset'  => Cast\Unset_::class,
    ];

    public function __construct(
        private readonly ?string  $type = null,
        private readonly ?Matcher $expr = null,
    ) {}

    // Matches any Cast subclass (Cast is the parent of all casts)
    protected function nodeClass(): string { return Cast::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->type !== null) {
            $expectedClass = self::$castMap[strtolower($this->type)] ?? null;
            if ($expectedClass !== null && !($node instanceof $expectedClass)) return false;
        }
        return $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
