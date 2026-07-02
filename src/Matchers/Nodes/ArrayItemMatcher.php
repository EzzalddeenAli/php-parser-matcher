<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ArrayItemMatcher extends Matcher
{
    private $value;
    private $key;

    public function __construct($value = null, $key = null)
    {
        $this->value = $value;
        $this->key = $key;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isArrayItem($node)) {
            return false;
        }
        if ($this->value !== null && !$this->value->matchValue($node->value, array_merge($keys, ['value']))) {
            return false;
        }
        if ($this->key !== null && !$this->key->matchValue($node->key, array_merge($keys, ['key']))) {
            return false;
        }
        return true;
    }
}
