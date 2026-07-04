<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;

/**
 * A named-capture bag — create once, embed CapturedMatchers by name,
 * then read results from a single object after the match.
 *
 * Eliminates the need to track bare capture variables by giving each slot
 * a string key and exposing a unified read API.
 *
 * Usage:
 *
 *   $caps = Ast::captures();
 *
 *   $m = Ast::callExpression(
 *       $caps->capture('fn', Ast::name()),
 *       Ast::anyList(
 *           Ast::arg($caps->capture('first_arg', Ast::string())),
 *           Ast::zeroOrMore()
 *       )
 *   );
 *
 *   if ($m->match($node)) {
 *       $fnName   = $caps->get('fn');         // Name node
 *       $firstArg = $caps->get('first_arg');  // String_ node
 *
 *       $all = $caps->toArray();  // ['fn' => Node, 'first_arg' => Node]
 *   }
 *
 *   // Reuse for the next node:
 *   $caps->reset();
 *   $m->match($node2);
 */
class CaptureGroup
{
    // ─── Global singleton ─────────────────────────────────────────────────────

    private static ?self $global = null;

    /**
     * The process-wide default group — used automatically by
     * $matcher->capture('name') when no explicit group is given.
     */
    public static function global(): self
    {
        return self::$global ??= new self();
    }

    /**
     * Clear captured DATA in the global group so it is ready for the next match.
     * Keeps all slot registrations so the same matcher tree can be reused.
     */
    public static function resetGlobal(): void
    {
        self::$global?->reset();
    }

    // ─── Instance ─────────────────────────────────────────────────────────────

    /** @var CapturedMatcher[] */
    private array $caps = [];

    /**
     * Create a named capture slot and return the CapturedMatcher to embed in a pattern.
     * If the slot name already exists it is replaced.
     */
    public function capture(string $name, ?Matcher $matcher = null): CapturedMatcher
    {
        $cap = new CapturedMatcher($matcher);
        $this->caps[$name] = $cap;
        return $cap;
    }

    /**
     * Register an existing CapturedMatcher under a name.
     * Called by Matcher::capture('name') to bind inline captures to this group.
     * Replaces any existing slot with the same name.
     */
    public function register(string $name, CapturedMatcher $cap): CapturedMatcher
    {
        $this->caps[$name] = $cap;
        return $cap;
    }

    // ─── Reading results ───────────────────────────────────────────────────────

    /** First captured node for $name, or null if not yet matched. */
    public function get(string $name): mixed
    {
        return ($this->caps[$name] ?? null)?->first();
    }

    /** All captured nodes for $name (useful when the slot appears in zeroOrMore / anyList). */
    public function all(string $name): array
    {
        return ($this->caps[$name] ?? null)?->all() ?? [];
    }

    /** True if $name captured at least one node. */
    public function has(string $name): bool
    {
        return ($this->caps[$name] ?? null)?->matched() ?? false;
    }

    /**
     * All named captures as an associative array: [name => first captured value].
     * Missing (unmatched) captures appear as null values.
     */
    public function toArray(): array
    {
        return array_map(fn(CapturedMatcher $c) => $c->first(), $this->caps);
    }

    /**
     * The underlying CapturedMatcher for a slot — use when you need
     * the full result API (all(), count(), etc.) without a string lookup.
     */
    public function matcher(string $name): ?CapturedMatcher
    {
        return $this->caps[$name] ?? null;
    }

    // ─── Control ───────────────────────────────────────────────────────────────

    /** Clear all captures — call before reusing the group for a new, independent node. */
    public function reset(): static
    {
        foreach ($this->caps as $cap) {
            $cap->reset();
        }
        return $this;
    }

    /** Registered slot names in insertion order. */
    public function names(): array
    {
        return array_keys($this->caps);
    }
}
