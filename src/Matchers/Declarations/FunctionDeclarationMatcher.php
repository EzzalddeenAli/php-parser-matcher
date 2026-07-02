<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Function_;

class FunctionDeclarationMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name   = null,
        private readonly mixed    $params = null,
        private readonly ?Matcher $body   = null,
    ) {}

    protected function nodeClass(): string { return Function_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->name, $node->name, $keys, 'name')
            && $this->matchArrayField($this->params, $node->params, $keys, 'params')
            && $this->matchField($this->body, $node->stmts, $keys, 'stmts');
    }
}
