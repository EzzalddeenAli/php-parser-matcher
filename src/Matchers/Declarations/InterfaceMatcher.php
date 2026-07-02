<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class InterfaceMatcher extends Matcher
{
    private $name;
    private $extends;
    private $body;

    public function __construct($name = null, $extends = null, $body = null)
    {
        $this->name = $name;
        $this->extends = $extends;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isInterface($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->extends !== null && !$this->extends->matchValue($node->extends, array_merge($keys, ['extends']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
