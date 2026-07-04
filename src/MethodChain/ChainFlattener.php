<?php

namespace Fleet\AstMatcher\MethodChain;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;

/**
 * Converts a nested AST method-chain into a flat ChainNode.
 *
 * PHP-Parser stores chains inside-out (outermost call is the root of the AST
 * subtree). This class reverses that so the root of the chain is in
 * ChainNode::$root and subsequent ->calls are listed left-to-right.
 *
 * UUID stamping: each MethodCall node gets a 'uuid' attribute on first flatten.
 * Re-flattening the same node tree returns the same UUIDs, making it safe to
 * hold a UUID reference across in-place mutations.

 */
class ChainFlattener
{
    public function flatten(Node $node): ChainNode
    {
        $calls   = [];
        $current = $node;

        while ($current instanceof MethodCall || $current instanceof NullsafeMethodCall) {
            $call = new ChainCall(
                name: $current->name->toString(),
                args:  $current->args,
                node:  $current,
                // ChainCall constructor reads 'uuid' attribute if already set,
                // otherwise generates a new one
            );
            // Persist UUID on the original AST node so re-flattening is stable
            $current->setAttribute('uuid', $call->uuid);
            array_unshift($calls, $call);
            $current = $current->var;
        }

        return new ChainNode(root: $current, calls: $calls);
    }

    /** True when the node is a chain we can flatten (has at least one ->method()). */
    public function canFlatten(Node $node): bool
    {
        return $node instanceof MethodCall || $node instanceof NullsafeMethodCall;
    }

}
