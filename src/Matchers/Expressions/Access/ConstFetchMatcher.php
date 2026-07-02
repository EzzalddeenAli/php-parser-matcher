<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ConstFetchMatcher extends Matcher
{
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isConstFetch($node)) {
            return false;
        }
        if ($this->name === null) {
            return true;
        }
        if (is_string($this->name)) {
            return strtolower($node->name->toString()) === strtolower($this->name);
        }
        return $this->name->matchValue($node->name, array_merge($keys, ['name']));
    }
}
