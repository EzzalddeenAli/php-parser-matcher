<?php

namespace Fleet\AstMatcher\Matchers\Scalars;

use Fleet\AstMatcher\Core\Matcher;

class AnyNumberMatcher extends Matcher
{
    public function matchValue($value, $keys = []): bool
    {
        return is_numeric($value);
    }
}
