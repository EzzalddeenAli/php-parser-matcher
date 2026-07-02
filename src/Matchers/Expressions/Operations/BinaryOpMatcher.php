<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class BinaryOpMatcher extends Matcher
{
    private $operator;
    private $left;
    private $right;

    public function __construct($operator = null, $left = null, $right = null)
    {
        $this->operator = $operator;
        $this->left = $left;
        $this->right = $right;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isBinaryOpExpression($node)) {
            return false;
        }
        if ($this->operator !== null) {
            if (is_string($this->operator)) {
                if ($this->operator !== $node->getOperatorSigil()) {
                    return false;
                }
            } elseif (!$this->operator->matchValue($node->getOperatorSigil(), array_merge($keys, ['operator']))) {
                return false;
            }
        }
        if ($this->left !== null && !$this->left->matchValue($node->left, array_merge($keys, ['left']))) {
            return false;
        }
        if ($this->right !== null && !$this->right->matchValue($node->right, array_merge($keys, ['right']))) {
            return false;
        }
        return true;
    }
}
