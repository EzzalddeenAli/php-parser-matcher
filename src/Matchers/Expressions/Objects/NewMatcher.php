<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Objects;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\MatchesArgs;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\New_;

class NewMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;
    use MatchesArgs;

    public function __construct(
        private readonly ?Matcher $class = null,
        private readonly mixed    $args  = null,
    ) {}

    protected function nodeClass(): string { return New_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->class, $node->class, $keys, 'class')
            && $this->matchArgs($this->args, $node->args, $keys);
    }
}
