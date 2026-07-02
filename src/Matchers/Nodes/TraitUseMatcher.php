<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class TraitUseMatcher extends Matcher
{
    private $traits;

    public function __construct($traits = null)
    {
        $this->traits = $traits;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isTraitUse($node)) {
            return false;
        }
        if ($this->traits !== null && !$this->traits->matchValue($node->traits, array_merge($keys, ['traits']))) {
            return false;
        }
        return true;
    }
}
