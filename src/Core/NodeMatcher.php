<?php

namespace Fleet\AstMatcher\Core;

use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

abstract class NodeMatcher extends Matcher
{
    // ─── Type check ──────────────────────────────────────────────────────────

    protected function nodeClass(): string
    {
        throw new \LogicException(static::class . ' must implement nodeClass() or matchesNodeType()');
    }

    protected function matchesNodeType(mixed $node): bool
    {
        return is_a($node, $this->nodeClass());
    }

    // Hook for expression matchers to unwrap Stmt\Expression wrappers
    protected function unwrapNode(mixed $node): mixed
    {
        return $node;
    }

    // ─── Field helpers ───────────────────────────────────────────────────────

    // Match an optional ?Matcher field. Returns true when matcher is null (wildcard).
    final protected function matchField(?Matcher $matcher, mixed $value, array $keys, string $key): bool
    {
        return $matcher === null || $matcher->matchValue($value, [...$keys, $key]);
    }

    // Match a field that accepts either an array (→ TupleOf) or a Matcher directly.
    final protected function matchArrayField(mixed $matcher, array $nodeArray, array $keys, string $key): bool
    {
        if ($matcher === null) return true;
        if (is_array($matcher)) {
            return (new TupleOfMatcher(...$matcher))->matchValue($nodeArray, [...$keys, $key]);
        }
        return $matcher->matchValue($nodeArray, [...$keys, $key]);
    }

    // ─── Template method ─────────────────────────────────────────────────────

    final public function matchValue($node, $keys = []): bool
    {
        $node = $this->unwrapNode($node);
        if (!$this->matchesNodeType($node)) {
            return false;
        }
        return $this->matchNode($node, $keys);
    }

    abstract protected function matchNode($node, array $keys): bool;
}
