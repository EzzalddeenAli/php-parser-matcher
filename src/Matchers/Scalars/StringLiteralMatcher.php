<?php

namespace Fleet\AstMatcher\Matchers\Scalars;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class StringLiteralMatcher extends Matcher
{
    private $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isStringLiteral($node)) {
            return false;
        }
        if ($this->value === null) {
            return true;
        }
        if (is_string($this->value)) {
            return $this->value === $node->value;
        }
        return $this->value->matchValue($node->value, array_merge($keys, ['value']));
    }
}
