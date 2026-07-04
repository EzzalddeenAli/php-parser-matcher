<?php

namespace Fleet\AstMatcher\MethodChain;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

class ChainCall
{
    public readonly string $uuid;

    public function __construct(
        public readonly string $name,
        public readonly array  $args,         // Arg[]
        public readonly ?Node  $node = null,  // original MethodCall node; null for synthetic calls
        ?string                $uuid = null,
    ) {
        // Priority: explicit $uuid arg > node attribute > freshly generated
        $this->uuid = $uuid
            ?? $node?->getAttribute('uuid')
            ?? self::generateUuid();
    }

    public function getArg(int $index): ?Arg
    {
        return $this->args[$index] ?? null;
    }

    public function getStringArg(int $index): ?string
    {
        $arg = $this->getArg($index);
        return $arg?->value instanceof Scalar\String_ ? $arg->value->value : null;
    }

    public function countArgs(): int
    {
        return count($this->args);
    }

    /**
     * Returns true when every arg value is a PHP literal — string, int, float,
     * bool/null const-fetch, or a nested array of literals.
     * Returns false for Variable, FuncCall, Closure, MethodCall, etc.
     * Used by upsertCall() to decide whether it is safe to auto-update.
     */
    public function isLiteralArgs(): bool
    {
        foreach ($this->args as $arg) {
            if (!self::isLiteralNode($arg->value)) {
                return false;
            }
        }
        return true;
    }

    public static function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function isLiteralNode(Node $node): bool
    {
        // Scalar literals
        if ($node instanceof Scalar\String_
            || $node instanceof Scalar\LNumber  // also matches Scalar\Int_ (class_alias)
            || $node instanceof Scalar\DNumber  // also matches Scalar\Float_
            || $node instanceof Expr\ConstFetch // true / false / null
        ) {
            return true;
        }

        // Array of literals e.g. ['a', 'b'] or ['key' => 1]
        if ($node instanceof Expr\Array_) {
            foreach ($node->items as $item) {
                if ($item === null) continue; // list() hole
                if ($item instanceof Expr\ArrayItem && !self::isLiteralNode($item->value)) {
                    return false;
                }
            }
            return true;
        }

        // Variable, FuncCall, MethodCall, Closure, Arrow, etc. -> not literal
        return false;
    }
}
