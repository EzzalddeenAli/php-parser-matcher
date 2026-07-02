<?php

namespace Fleet\AstMatcher\Matchers\Generic;

use Fleet\AstMatcher\Core\Matcher;

class PredicateMatcher extends Matcher
{
    private $predicate;

    public function __construct(callable $predicate)
    {
        $this->predicate = $predicate;
    }

    public function matchValue($value, $keys = []): bool
    {
        return (bool) call_user_func($this->predicate, $value);
    }
}
