<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ForMatcher extends Matcher
{
    private $init;
    private $cond;
    private $loop;
    private $body;

    public function __construct($init = null, $cond = null, $loop = null, $body = null)
    {
        $this->init = $init;
        $this->cond = $cond;
        $this->loop = $loop;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isFor($node)) {
            return false;
        }
        // $node->init, $node->cond, $node->loop are all Expr[] arrays
        if ($this->init !== null && !$this->init->matchValue($node->init, array_merge($keys, ['init']))) {
            return false;
        }
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        if ($this->loop !== null && !$this->loop->matchValue($node->loop, array_merge($keys, ['loop']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
