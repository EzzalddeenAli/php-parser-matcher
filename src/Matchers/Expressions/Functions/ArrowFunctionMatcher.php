<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Functions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\ArrowFunction;

class ArrowFunctionMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly mixed    $params = null,
        private readonly ?Matcher $expr   = null,
        private readonly ?bool    $static = null,
    ) {}

    protected function nodeClass(): string { return ArrowFunction::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->static !== null && $this->static !== $node->static) return false;

        return $this->matchArrayField($this->params, $node->params, $keys, 'params')
            && $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
