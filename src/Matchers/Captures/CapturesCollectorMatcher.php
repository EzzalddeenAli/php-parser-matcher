<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Matchers\Generic\AnythingMatcher;

class CapturesCollectorMatcher extends Matcher
{
    private Matcher $matcher;
    private mixed $current = null;
    private ?array $currentKeys = null;
    private array $captures = [];

    public function __construct(?Matcher $matcher = null)
    {
        $this->matcher = $matcher ?? new AnythingMatcher();
    }

    public function match($value, $keys = []): bool
    {
        return $this->matchValue($value, $keys);
    }

    public function matchValue($value, $keys = []): bool
    {
        if ($this->matcher instanceof CapturedMatcher) {
            $this->matcher->reset();
        }
        if ($this->matcher->matchValue($value, $keys)) {
            if ($this->matcher instanceof CapturedMatcher) {
                $this->capture($this->matcher->getCaptures(), $keys);
            }
            return true;
        }
        if ($this->matcher instanceof CapturedMatcher) {
            $this->matcher->reset();
        }
        return false;
    }

    public function reset(): static
    {
        $this->captures = [];
        $this->current = null;
        $this->currentKeys = null;
        return $this;
    }

    public function capture(mixed $value, array $keys): void
    {
        $this->current = $value;
        $this->currentKeys = $keys;
        $this->captures[] = $value;
    }

    public function getCurrent(): mixed
    {
        return $this->current;
    }

    public function getCurrentKeys(): ?array
    {
        return $this->currentKeys;
    }

    public function getCapturesCollect(): array
    {
        return $this->captures;
    }
}
