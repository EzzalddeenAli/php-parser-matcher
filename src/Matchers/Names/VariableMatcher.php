<?php

namespace Fleet\AstMatcher\Matchers\Names;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class VariableMatcher extends Matcher
{
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isVariable($node)) {
            return false;
        }
        if ($this->name === null) {
            return true;
        }
        if (is_string($this->name)) {
            return $this->name === $node->name;
        }
        return $this->name->matchValue($node->name, array_merge($keys, ['name']));
    }
}
