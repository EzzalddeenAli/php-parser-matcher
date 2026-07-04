<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Matchers\Generic\AnythingMatcher;

/**
 * Wraps any matcher and records every node it matched.
 *
 * Captures accumulate until reset() is called — intentionally, so a single
 * match() call can collect multiple nodes (e.g. inside anyList / zeroOrMore).
 * Call reset() before reusing the same instance for a new, independent match.
 *
 * Usage:
 *
 *   $cap = Ast::capture(Ast::string());
 *   $m   = Ast::callExpression(Ast::name('route'), Ast::anyList(Ast::arg($cap)));
 *
 *   if ($m->match($node)) {
 *       $routeName = $cap->first();   // Scalar\String_ node
 *   }
 *
 * Multiple captures inside the same call:
 *
 *   $caps = Ast::captures();
 *   $m = Ast::callExpression(
 *       $caps->capture('fn',  Ast::name()),
 *       Ast::anyList(Ast::arg($caps->capture('first', Ast::string())), Ast::zeroOrMore())
 *   );
 *   if ($m->match($node)) {
 *       $fnName   = $caps->get('fn');
 *       $firstArg = $caps->get('first');
 *   }
 */
class CapturedMatcher extends Matcher
{
    private Matcher $matcher;
    private array $captures = [];

    public function __construct(?Matcher $matcher = null)
    {
        $this->matcher = $matcher ?? new AnythingMatcher();
    }

    public function matchValue($value, $keys = []): bool
    {
        if ($this->matcher->matchValue($value, $keys)) {
            $this->captures[] = $value;
            return true;
        }
        return false;
    }

    // ─── Result API ────────────────────────────────────────────────────────────

    /** First captured node, or null if nothing matched yet. */
    public function first(): mixed
    {
        return $this->captures[0] ?? null;
    }

    /** Last captured node, or null if nothing matched yet. */
    public function last(): mixed
    {
        return !empty($this->captures) ? end($this->captures) : null;
    }

    /** All captured nodes in match order. */
    public function all(): array
    {
        return $this->captures;
    }

    /** Number of nodes captured since the last reset(). */
    public function count(): int
    {
        return count($this->captures);
    }

    /** True if at least one node was captured. */
    public function matched(): bool
    {
        return !empty($this->captures);
    }

    /** Clear captured results to reuse this instance for a fresh match. */
    public function reset(): static
    {
        $this->captures = [];
        return $this;
    }

    /**
     * Store an entire array of elements as one capture entry.
     * Used by SliceCaptureMatcher after a successful AnyListMatcher distribution.
     * first() will return the array itself; all() returns an array of arrays.
     */
    public function captureArray(array $elements): void
    {
        $this->captures[] = $elements;
    }

    // ─── Backward-compat aliases ───────────────────────────────────────────────

    /** @deprecated Use first() */
    public function getCurrent(): mixed { return $this->first(); }

    /** @deprecated Use all() */
    public function getCaptures(): array { return $this->all(); }

    /** @deprecated Use matched() */
    public function isMatched(): bool { return $this->matched(); }

    // ─── Internal hook for subclasses ─────────────────────────────────────────

    protected function addCapture(mixed $value): void
    {
        $this->captures[] = $value;
    }
}
