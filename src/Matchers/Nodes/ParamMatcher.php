<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ParamMatcher extends Matcher
{
    private $name;
    private $type;

    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isParam($node)) {
            return false;
        }
        if ($this->type !== null && !$this->type->matchValue($node->type, array_merge($keys, ['type']))) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->var, array_merge($keys, ['var']))) {
            return false;
        }
        return true;
    }
}
