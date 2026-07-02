<?php

namespace Fleet\AstMatcher\Matchers\Scalars;

use Fleet\AstMatcher\Core\Matcher;
use Stringable;

class AnyStringMatcher extends Matcher
{
    public function matchValue($value, $keys = []): bool
    {
        return is_string($value) || $value instanceof Stringable;
    }
}
