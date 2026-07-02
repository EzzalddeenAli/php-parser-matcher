<?php

namespace Fleet\AstMatcher\Matchers\Scalars;

use Fleet\AstMatcher\Core\Matcher;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;

class NumberLiteralMatcher extends Matcher
{
    private $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!($node instanceof LNumber || $node instanceof DNumber)) {
            return false;
        }
        if ($this->value === null) {
            return true;
        }
        return $this->value == $node->value;
    }
}
