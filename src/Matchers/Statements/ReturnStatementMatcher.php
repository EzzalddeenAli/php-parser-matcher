<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ReturnStatementMatcher extends Matcher
{
    private $argument;

    public function __construct($argument = null)
    {
        $this->argument = $argument;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isReturnStatement($node)) {
            return false;
        }
        if ($this->argument !== null&& !$this->argument->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        return true;
    }
}
