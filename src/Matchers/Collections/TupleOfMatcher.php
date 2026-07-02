<?php

namespace Fleet\AstMatcher\Matchers\Collections;

use Fleet\AstMatcher\Core\Matcher;

class TupleOfMatcher extends Matcher
{
    private array $matchers;

    public function __construct(Matcher ...$matchers)
    {
        $this->matchers = $matchers;
    }

    public function matchValue($value, $keys = []): bool
    {
        if (!is_array($value)) {
            return false;
        }
        if (count($value) !== count($this->matchers)) {
            return false;
        }
        for ($i = 0; $i < count($this->matchers); $i++) {
            if (!$this->matchers[$i]->matchValue($value[$i], array_merge($keys, [$i]))) {
                return false;
            }
        }
        return true;
    }
}
