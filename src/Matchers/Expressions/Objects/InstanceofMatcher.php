<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Objects;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Instanceof_;

class InstanceofMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $expr  = null,
        private readonly ?Matcher $class = null,
    ) {}

    protected function nodeClass(): string { return Instanceof_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->expr,  $node->expr,  $keys, 'expr')
            && $this->matchField($this->class, $node->class, $keys, 'class');
    }
}
