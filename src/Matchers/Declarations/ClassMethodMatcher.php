<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\ClassMethod;

class ClassMethodMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name   = null,
        private readonly mixed    $params = null,
        private readonly ?Matcher $body   = null,
        private readonly ?bool    $static = null,
    ) {}

    protected function nodeClass(): string { return ClassMethod::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->static !== null && $this->static !== $node->isStatic()) {
            return false;
        }
        return $this->matchField($this->name, $node->name, $keys, 'name')
            && $this->matchArrayField($this->params, $node->params, $keys, 'params')
            && $this->matchField($this->body, $node->stmts, $keys, 'stmts');
    }
}
