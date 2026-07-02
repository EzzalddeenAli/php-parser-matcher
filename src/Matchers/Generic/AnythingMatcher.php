<?php

namespace Fleet\AstMatcher\Matchers\Generic;

use Fleet\AstMatcher\Core\Matcher;

class AnythingMatcher extends Matcher
{
    public function matchValue($value, $keys = []): bool
    {
        return true;
    }
}
