<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class TryCatchMatcher extends Matcher
{
    private $body;
    private $catches;
    private $finally;

    public function __construct($body = null, $catches = null, $finally = null)
    {
        $this->body    = $body;
        $this->catches = $catches;
        $this->finally = $finally;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isTryCatch($node)) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        if ($this->catches !== null && !$this->catches->matchValue($node->catches, array_merge($keys, ['catches']))) {
            return false;
        }
        if ($this->finally !== null && !$this->finally->matchValue($node->finally, array_merge($keys, ['finally']))) {
            return false;
        }
        return true;
    }
}
