<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

class FunctionDeclarationMatcher extends Matcher
{
    private $name;
    private $params;
    private $body;

    public function __construct($name = null, $params = null, $body = null)
    {
        $this->name = $name;
        $this->params = $params;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isFunctionDeclaration($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->params !== null) {
            if (is_array($this->params)) {
                $tuple = new TupleOfMatcher(...$this->params);
                if (!$tuple->matchValue($node->params, array_merge($keys, ['params']))) {
                    return false;
                }
            } elseif (!$this->params->matchValue($node->params, array_merge($keys, ['params']))) {
                return false;
            }
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
