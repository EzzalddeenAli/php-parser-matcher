<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Functions;

use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Closure;

class ClosureMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly mixed $params = null,
        private readonly mixed $body   = null,
        private readonly ?bool $static = null,
    ) {}

    protected function nodeClass(): string { return Closure::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->static !== null && $this->static !== $node->static) return false;

        return $this->matchArrayField($this->params, $node->params, $keys, 'params')
            && $this->matchArrayField($this->body, $node->stmts, $keys, 'stmts');
    }
}
