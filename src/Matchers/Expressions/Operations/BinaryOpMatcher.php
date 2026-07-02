<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\BinaryOp;

class BinaryOpMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly mixed    $operator = null,
        private readonly ?Matcher $left     = null,
        private readonly ?Matcher $right    = null,
    ) {}

    // Matches any BinaryOp subclass (BinaryOp is the parent of all binary ops)
    protected function nodeClass(): string { return BinaryOp::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->operator !== null) {
            if (is_string($this->operator)) {
                if ($this->operator !== $node->getOperatorSigil()) return false;
            } else {
                if (!$this->operator->matchValue($node->getOperatorSigil(), [...$keys, 'operator'])) return false;
            }
        }
        return $this->matchField($this->left,  $node->left,  $keys, 'left')
            && $this->matchField($this->right, $node->right, $keys, 'right');
    }
}
