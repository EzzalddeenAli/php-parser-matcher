<?php

namespace Fleet\AstMatcher\Matchers\Concerns;

use PhpParser\Node\Stmt\Expression;

// Used by expression matchers so they can be applied directly to statement nodes.
// When the incoming node is Stmt\Expression (a bare expression-as-statement),
// we unwrap it to obtain the inner Expr\* before type-checking.
trait UnwrapsExpressionStatement
{
    protected function unwrapNode(mixed $node): mixed
    {
        if ($node instanceof Expression) {
            return $node->expr;
        }
        return $node;
    }
}
