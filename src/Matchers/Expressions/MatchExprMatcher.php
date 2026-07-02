<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Match_;

class MatchExprMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $subject = null,
        private readonly ?Matcher $arms    = null,
    ) {}

    protected function nodeClass(): string { return Match_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->subject, $node->subject, $keys, 'subject')
            && $this->matchField($this->arms,    $node->arms,    $keys, 'arms');
    }
}
