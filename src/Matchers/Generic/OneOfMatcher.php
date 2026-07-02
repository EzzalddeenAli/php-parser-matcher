<?php

namespace Fleet\AstMatcher\Matchers\Generic;

use Fleet\AstMatcher\Core\Matcher;

class OneOfMatcher extends Matcher
{
    private $matcher;

    public function __construct($matcher)
    {
        $this->matcher = $matcher;
    }

    public function matchValue($value, $keys = []): bool
    {
        if (!is_array($value)) {
            return false;
        }
        if (count($value) !== 1) {
            return false;
        }
        return $this->matcher->matchValue($value[0], array_merge($keys, [0]));
    }
}
