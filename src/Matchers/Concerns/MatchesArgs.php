<?php

namespace Fleet\AstMatcher\Matchers\Concerns;

use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;

trait MatchesArgs
{
    // Accepts an array of matchers (each auto-wrapped in ArgMatcher if needed),
    // or a single Matcher that receives the raw args array directly.
    protected function matchArgs(mixed $argsMatcher, array $nodeArgs, array $keys): bool
    {
        if ($argsMatcher === null) return true;
        if (is_array($argsMatcher)) {
            $wrapped = array_map(
                static fn($a) => $a instanceof ArgMatcher ? $a : new ArgMatcher($a),
                $argsMatcher
            );
            return (new TupleOfMatcher(...$wrapped))->matchValue($nodeArgs, [...$keys, 'args']);
        }
        return $argsMatcher->matchValue($nodeArgs, [...$keys, 'args']);
    }
}
