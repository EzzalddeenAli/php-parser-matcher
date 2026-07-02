<?php

namespace Fleet\AstMatcher\Matchers\Collections;

use Fleet\AstMatcher\Core\Matcher;

class SliceMatcher extends Matcher
{
    public int $min;
    public int $max;
    public Matcher $matcher;

    public function __construct(int $min, int $max, Matcher $matcher)
    {
        $this->min = $min;
        $this->max = $max;
        $this->matcher = $matcher;
    }

    public function matchValue($value, $keys = []): bool
    {
        return $this->matcher->matchValue($value, $keys);
    }
}
