<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Functions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ArrowFunctionMatcher extends Matcher
{
    private $params;
    private $expr;
    private $static;

    public function __construct($params = null, $expr = null, $static = null)
    {
        $this->params = $params;
        $this->expr = $expr;
        $this->static = $static;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isArrowFunction($node)) {
            return false;
        }
        if ($this->params !== null) {
            if (is_array($this->params)) {
                $tuple = new \Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher(...$this->params);
                if (!$tuple->matchValue($node->params, array_merge($keys, ['params']))) {
                    return false;
                }
            } elseif (!$this->params->matchValue($node->params, array_merge($keys, ['params']))) {
                return false;
            }
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        if ($this->static !== null && is_bool($this->static) && $this->static !== $node->static) {
            return false;
        }
        return true;
    }
}
