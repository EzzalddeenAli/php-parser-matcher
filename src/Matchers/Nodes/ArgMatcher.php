<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ArgMatcher extends Matcher
{
    private $value;
    private $name;

    public function __construct($value = null, $name = null)
    {
        $this->value = $value;
        $this->name = $name;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isArg($node)) {
            return false;
        }
        if ($this->value !== null && !$this->value->matchValue($node->value, array_merge($keys, ['value']))) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        return true;
    }
}
