<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Calls;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Matchers\Concerns\MatchesArgs;
use Fleet\AstMatcher\Matchers\Generic\PredicateMatcher;
use Fleet\AstMatcher\Matchers\Names\IdentifierMatcher;
use Fleet\AstMatcher\MethodChain\ChainCall;
use Fleet\AstMatcher\MethodChain\ChainFlattener;
use Fleet\AstMatcher\MethodChain\ChainNode;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;

/**
 * Matches any method/static/function call chain as a whole unit.
 *
 * Unlike StaticCallMatcher or MethodCallMatcher (which match a SINGLE node),
 * ChainCallMatcher flattens the entire chain and lets you assert conditions
 * on the root, on individual chain calls, and on their args — all in one matcher.
 *
 * Conditions are ANDed: every constraint you add must pass for the matcher to
 * return true.
 *
 * Examples:
 *
 *   // Any Nova Text field with ->sortable()
 *   Ast::chain()
 *       ->rootClass('Text')
 *       ->rootMethod('make')
 *       ->hasCall('sortable');
 *
 *   // Any Nova field (Text or ID) with specific label arg
 *   Ast::chain()
 *       ->rootClassIn(['Text', 'ID'])
 *       ->rootMethod('make')
 *       ->rootArgs([Ast::arg(Ast::string('Name'))]);
 *
 *   // Eloquent query ending with get() or paginate()
 *   Ast::chain()
 *       ->rootIsStaticCall()
 *       ->hasAnyCall(['get', 'paginate', 'first', 'count']);
 *
 *   // Field that has rules() with a specific rule, but no hideFromIndex()
 *   Ast::chain()
 *       ->rootClass('Text')
 *       ->callArgs('rules', [Ast::arg(Ast::string('required'))])
 *       ->lacksCall('hideFromIndex');
 *
 * The matched node is the outermost call (head of the chain), so capture() works:
 *
 *   $cap = Ast::capture(Ast::chain()->rootClass('Text')->hasCall('sortable'));
 *   // $cap->value = the outermost MethodCall node
 */
class ChainCallMatcher extends Matcher
{
    use MatchesArgs;

    // ─── Root conditions ──────────────────────────────────────────────────────

    private ?Matcher $rootClassMatcher  = null;
    private ?Matcher $rootMethodMatcher = null;
    private mixed    $rootArgsMatcher   = null;  // array|Matcher|null
    private bool     $staticOnly        = false;

    // ─── Chain call conditions ────────────────────────────────────────────────

    /** @var string[] each must appear somewhere in the chain */
    private array $requiredCalls   = [];

    /** @var string[] none of these may appear in the chain */
    private array $forbiddenCalls  = [];

    /** @var array[] each sub-array: at least one name must appear */
    private array $anyCallGroups   = [];

    /** @var array[] [name, argsMatcherOrArray] */
    private array $callArgMatchers = [];

    private ?int  $minCalls        = null;
    private ?int  $maxCalls        = null;

    private readonly ChainFlattener $flattener;

    public function __construct()
    {
        $this->flattener = new ChainFlattener();
    }

    // ─── Root condition fluent API ────────────────────────────────────────────

    /**
     * Root class name must match.
     * String: exact name (e.g. 'Text'). Matcher: any fleet/ast-matcher Matcher.
     */
    public function rootClass(string|Matcher|null $class): static
    {
        $this->rootClassMatcher = is_string($class) ? new IdentifierMatcher($class) : $class;
        return $this;
    }

    /**
     * Root class must be one of the given strings.
     * Shorthand for rootClass(Ast::or(Ast::name('A'), Ast::name('B'), ...)).
     */
    public function rootClassIn(array $classes): static
    {
        $this->rootClassMatcher = new PredicateMatcher(
            fn($node) => in_array(Matcher::getShortName($node), $classes, true)
        );
        return $this;
    }

    /**
     * Root method name must match.
     * String: exact name (e.g. 'make'). Matcher: any fleet/ast-matcher Matcher.
     */
    public function rootMethod(string|Matcher|null $method): static
    {
        $this->rootMethodMatcher = is_string($method) ? new IdentifierMatcher($method) : $method;
        return $this;
    }

    /**
     * Root call's args must match.
     * Array:   per-arg Matchers (each auto-wrapped in ArgMatcher if needed).
     * Matcher: applied to the full args array directly.
     */
    public function rootArgs(mixed $argsMatcher): static
    {
        $this->rootArgsMatcher = $argsMatcher;
        return $this;
    }

    /** Root must be a StaticCall (Class::method()). */
    public function rootIsStaticCall(): static
    {
        $this->staticOnly = true;
        return $this;
    }

    // ─── Chain call condition fluent API ─────────────────────────────────────

    /**
     * Every given name must appear somewhere in the chain calls (not the root).
     * Multiple calls to hasCall() are ANDed together.
     */
    public function hasCall(string ...$names): static
    {
        array_push($this->requiredCalls, ...$names);
        return $this;
    }

    /** None of the given names may appear in the chain calls. */
    public function lacksCall(string ...$names): static
    {
        array_push($this->forbiddenCalls, ...$names);
        return $this;
    }

    /** At least one of the given names must appear in the chain calls. */
    public function hasAnyCall(array $names): static
    {
        $this->anyCallGroups[] = $names;
        return $this;
    }

    /**
     * A specific chain call's args must match $argsMatcher.
     * Implies the call must exist (returns false if missing).
     *
     * Array:   per-arg Matchers wrapped in ArgMatcher as needed.
     * Matcher: applied to the full call->args array directly.
     *
     * Example — first arg of rules() must be 'required':
     *   ->callArgs('rules', [Ast::arg(Ast::string('required'))])
     */
    public function callArgs(string $name, mixed $argsMatcher): static
    {
        $this->callArgMatchers[] = [$name, $argsMatcher];
        return $this;
    }

    /**
     * Constrain the number of chain calls (excluding the root).
     *
     *   chainLength(0)    = bare call only — no chain (same as bare StaticCall)
     *   chainLength(1)    = at least one chain call
     *   chainLength(2, 5) = between 2 and 5 chain calls
     *   chainLength(3, 3) = exactly 3 chain calls
     */
    public function chainLength(int $min, ?int $max = null): static
    {
        $this->minCalls = $min;
        $this->maxCalls = $max;
        return $this;
    }

    // ─── Matcher implementation ───────────────────────────────────────────────

    public function matchValue($node, $keys = []): bool
    {
        // Unwrap Stmt\Expression so this matcher works on both statements and exprs
        if ($node instanceof Expression) {
            $node = $node->expr;
        }

        $isCall = $node instanceof MethodCall
            || $node instanceof NullsafeMethodCall
            || $node instanceof StaticCall
            || $node instanceof FuncCall;

        if (!$isCall) return false;

        $chain = $this->flattener->flatten($node);

        return $this->checkRoot($chain, $keys)
            && $this->checkCalls($chain, $keys);
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    private function checkRoot(ChainNode $chain, array $keys): bool
    {
        if ($this->staticOnly && !($chain->root instanceof StaticCall)) return false;

        if ($this->rootClassMatcher !== null) {
            if (!($chain->root instanceof StaticCall)) return false;
            if (!$this->rootClassMatcher->matchValue(
                $chain->root->class, [...$keys, 'root', 'class']
            )) return false;
        }

        if ($this->rootMethodMatcher !== null) {
            $nameNode = match (true) {
                $chain->root instanceof StaticCall => $chain->root->name,
                $chain->root instanceof FuncCall   => $chain->root->name,
                default                            => null,
            };
            if ($nameNode === null) return false;
            if (!$this->rootMethodMatcher->matchValue(
                $nameNode, [...$keys, 'root', 'name']
            )) return false;
        }

        if ($this->rootArgsMatcher !== null) {
            $rootArgs = $chain->getRootArgs();
            if (!$this->matchArgs($this->rootArgsMatcher, $rootArgs, [...$keys, 'root'])) {
                return false;
            }
        }

        return true;
    }

    private function checkCalls(ChainNode $chain, array $keys): bool
    {
        $count = count($chain->calls);

        if ($this->minCalls !== null && $count < $this->minCalls) return false;
        if ($this->maxCalls !== null && $count > $this->maxCalls) return false;

        foreach ($this->requiredCalls as $name) {
            if (!$chain->hasCall($name)) return false;
        }

        foreach ($this->forbiddenCalls as $name) {
            if ($chain->hasCall($name)) return false;
        }

        foreach ($this->anyCallGroups as $group) {
            $found = false;
            foreach ($group as $name) {
                if ($chain->hasCall($name)) { $found = true; break; }
            }
            if (!$found) return false;
        }

        foreach ($this->callArgMatchers as [$name, $argsMatcher]) {
            $call = $chain->getCall($name);
            if ($call === null) return false;
            // Read live node args (may have been mutated by ChainPatch::updateArgs)
            $callArgs = $call->node?->args ?? $call->args;
            if (!$this->matchArgs($argsMatcher, $callArgs, [...$keys, $name])) {
                return false;
            }
        }

        return true;
    }
}
