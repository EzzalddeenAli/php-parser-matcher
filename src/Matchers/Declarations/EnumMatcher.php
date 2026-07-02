<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class EnumMatcher extends Matcher
{
    private $name;
    private $scalarType;
    private $body;

    public function __construct($name = null, $scalarType = null, $body = null)
    {
        $this->name = $name;
        $this->scalarType = $scalarType;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isEnum($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->scalarType !== null && !$this->scalarType->matchValue($node->scalarType, array_merge($keys, ['scalarType']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
