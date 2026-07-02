<?php

namespace Fleet\AstMatcher\Matchers\Generic;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class AnyStatementMatcher extends Matcher
{
    public function matchValue($value, $keys = []): bool
    {
        return NodeTypes::isNode($value) && NodeTypes::isStatement($value);
    }
}
