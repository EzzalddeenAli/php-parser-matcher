<?php

namespace Fleet\AstMatcher\Contracts;

interface MatcherInterface
{
    public function match($value, $keys = []);

    public function matchValue($value, $keys = []);
}
