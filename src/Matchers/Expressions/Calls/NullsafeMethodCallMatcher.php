<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Calls;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\MatchesArgs;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\NullsafeMethodCall;

class NullsafeMethodCallMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;
    use MatchesArgs;

    public function __construct(
        private readonly ?Matcher $object = null,
        private readonly ?Matcher $name   = null,
        private readonly mixed    $args   = null,
    ) {}

    protected function nodeClass(): string { return NullsafeMethodCall::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->object, $node->var,  $keys, 'var')
            && $this->matchField($this->name,   $node->name, $keys, 'name')
            && $this->matchArgs($this->args, $node->args, $keys);
    }
}
