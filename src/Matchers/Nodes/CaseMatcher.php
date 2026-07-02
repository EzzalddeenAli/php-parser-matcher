<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class CaseMatcher extends Matcher
{
    private $cond;
    private $body;

    /**
     * @param $cond null = matches any case (including default); use explicit null matcher for `default:` only
     * @param $body matcher for the case stmts array
     */
    public function __construct($cond = null, $body = null)
    {
        $this->cond = $cond;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isCase($node)) {
            return false;
        }
        // $node->cond is null for `default:` case
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
