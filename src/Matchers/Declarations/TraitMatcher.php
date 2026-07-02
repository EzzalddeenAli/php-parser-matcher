<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Collections\AnyListMatcher;

class TraitMatcher extends Matcher
{
    private $name;
    private $body;

    public function __construct($name = null, $body = null)
    {
        $this->name = $name;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isTrait($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
