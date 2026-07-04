<?php

namespace Fleet\AstMatcher\MethodChain;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * Flat representation of a PHP method chain.
 *
 * Any chain like:
 *   StaticCall / Variable / FuncCall       ← root
 *     ->method1(...)                        ← calls[0]
 *     ->method2(...)                        ← calls[1]
 *
 * is flattened into:  ChainNode(root, [ChainCall, ChainCall, ...])
 *
 * Examples:
 *   Text::make('Name','name')->rules('required')->placeholder('Name')
 *     root  = StaticCall(Text, make, ['Name','name'])
 *     calls = [rules(['required']), placeholder(['Name'])]
 *
 *   User::where('status','active')->where('votes','>',100)->get()
 *     root  = StaticCall(User, where, ['status','active'])
 *     calls = [where(['votes','>',100]), get([])]
 */
class ChainNode
{
    /** @param ChainCall[] $calls */
    public function __construct(
        public readonly Node  $root,
        public readonly array $calls,
    ) {}

    // ─── Root helpers ─────────────────────────────────────────────────────────

    public function getRootClass(): ?string
    {
        if ($this->root instanceof StaticCall && $this->root->class instanceof Name) {
            return $this->root->class->toString();
        }
        return null;
    }

    public function getRootMethod(): ?string
    {
        if ($this->root instanceof StaticCall) {
            $n = $this->root->name;
            return $n instanceof Identifier ? $n->name : null;
        }
        return null;
    }

    /** Args of the root call (StaticCall or FuncCall). */
    public function getRootArgs(): array
    {
        return $this->root instanceof StaticCall
            ? $this->root->args
            : [];
    }

    // ─── Call helpers ─────────────────────────────────────────────────────────

    public function hasCall(string $name): bool
    {
        return $this->getCall($name) !== null;
    }

    public function getCall(string $name): ?ChainCall
    {
        foreach ($this->calls as $call) {
            if ($call->name === $name) return $call;
        }
        return null;
    }

    /** All calls with this name (e.g. multiple ->where() calls). */
    public function getCalls(string $name): array
    {
        return array_values(array_filter($this->calls, fn(ChainCall $c) => $c->name === $name));
    }

    public function getFirstCall(): ?ChainCall
    {
        return $this->calls[0] ?? null;
    }

    public function getLastCall(): ?ChainCall
    {
        return empty($this->calls) ? null : $this->calls[array_key_last($this->calls)];
    }

    public function countCalls(string $name): int
    {
        return count($this->getCalls($name));
    }

    /** Ordered list of all call names. */
    public function allCallNames(): array
    {
        return array_map(fn(ChainCall $c) => $c->name, $this->calls);
    }

    /**
     * All calls including the root's own method (when root is a StaticCall).
     *
     * Use this when the root method is semantically equivalent to the chain
     * calls — e.g. in a query chain where User::where(...) and ->where(...)
     * are both just conditions:
     *
     *   User::where('a','1')->where('b','2')->get()
     *   → [where('a','1'), where('b','2'), get()]   ← all three, uniform
     *
     * For Nova fields where make() is a factory, prefer getRootArgs() directly.
     */
    public function getAllCalls(): array
    {
        if (!($this->root instanceof StaticCall)) {
            return $this->calls;
        }

        $rootCall = new ChainCall(
            name: $this->getRootMethod() ?? '',
            args: $this->root->args,
            node: $this->root,
        );

        return array_merge([$rootCall], $this->calls);
    }

    /**
     * All calls with this name, across root AND chain.
     * Useful for getCalls('where') on a query that starts with Model::where().
     */
    public function getAllCallsNamed(string $name): array
    {
        return array_values(
            array_filter($this->getAllCalls(), fn(ChainCall $c) => $c->name === $name)
        );
    }
}
