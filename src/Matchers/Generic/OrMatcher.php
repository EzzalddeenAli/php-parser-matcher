<?php

namespace Fleet\AstMatcher\Matchers\Generic;

use Fleet\AstMatcher\Core\Matcher;

class OrMatcher extends Matcher
{
    private array $matchersOrValues;

    public function __construct(...$matchersOrValues)
    {
        $this->matchersOrValues = $matchersOrValues;
    }

    public function matchValue($value, $keys = []): bool
    {
        foreach ($this->matchersOrValues as $matcherOrValue) {
            if ($matcherOrValue instanceof Matcher) {
                if ($matcherOrValue->matchValue($value, $keys)) {
                    return true;
                }
            } elseif ($matcherOrValue === $value) {
                return true;
            }
        }
        return false;
    }
}
