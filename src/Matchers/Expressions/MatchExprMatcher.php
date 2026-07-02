<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class MatchExprMatcher extends Matcher
{
    private $subject;
    private $arms;

    public function __construct($subject = null, $arms = null)
    {
        $this->subject = $subject;
        $this->arms = $arms;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isMatch($node)) {
            return false;
        }
        if ($this->subject !== null && !$this->subject->matchValue($node->subject, array_merge($keys, ['subject']))) {
            return false;
        }
        if ($this->arms !== null && !$this->arms->matchValue($node->arms, array_merge($keys, ['arms']))) {
            return false;
        }
        return true;
    }
}
