<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class UseStatementMatcher extends Matcher
{
    private $name;
    private $alias;

    public function __construct($name = null, $alias = null)
    {
        $this->name = $name;
        $this->alias = $alias;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isUseStatement($node)) {
            return false;
        }
        $use = $node->uses[0] ?? null;
        if ($use === null) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($use->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->alias !== null && !$this->alias->matchValue($use->alias, array_merge($keys, ['alias']))) {
            return false;
        }
        return true;
    }
}
