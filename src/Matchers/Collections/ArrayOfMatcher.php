<?php

namespace Fleet\AstMatcher\Matchers\Collections;

use Fleet\AstMatcher\Core\Matcher;

class ArrayOfMatcher extends Matcher
{
    private Matcher $elementMatcher;

    public function __construct(Matcher $elementMatcher)
    {
        $this->elementMatcher = $elementMatcher;
    }

    public function matchValue($value, $keys = []): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $i => $element) {
            if (!$this->elementMatcher->matchValue($element, array_merge($keys, [$i]))) {
                return false;
            }
        }
        return true;
    }
}
