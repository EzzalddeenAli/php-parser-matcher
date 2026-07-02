<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Use_;

class UseStatementMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name  = null,
        private readonly ?Matcher $alias = null,
    ) {}

    protected function nodeClass(): string { return Use_::class; }

    protected function matchNode($node, array $keys): bool
    {
        $use = $node->uses[0] ?? null;
        if ($use === null) return false;

        return $this->matchField($this->name,  $use->name,  $keys, 'name')
            && $this->matchField($this->alias, $use->alias, $keys, 'alias');
    }
}
