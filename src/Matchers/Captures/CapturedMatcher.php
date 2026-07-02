<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Matchers\Generic\AnythingMatcher;

class CapturedMatcher extends Matcher
{
    public Matcher $matcher;
    public mixed $current = null;
    public ?array $currentKeys = null;
    public bool $multiple = false;

    private array $requiredCaptures = [];
    private array $captures = [];

    public function __construct(?Matcher $matcher = null)
    {
        $this->matcher = $matcher ?? new AnythingMatcher();
    }

    public function multiple(): static
    {
        $this->multiple = true;
        return $this;
    }

    public function required(array $captures): static
    {
        $this->requiredCaptures = $captures;
        return $this;
    }

    public function reset(): static
    {
        $this->captures = [];
        $this->current = null;
        $this->currentKeys = null;
        foreach ($this->requiredCaptures as $capture) {
            $capture->reset();
        }
        return $this;
    }

    public function getCurrent(): mixed
    {
        return $this->current;
    }

    public function getCurrentKeys(): ?array
    {
        return $this->currentKeys;
    }

    public function getCaptures(): array
    {
        return $this->captures;
    }

    public function isMatched(): bool
    {
        return $this->current !== null;
    }

    public function match($value, $keys = []): bool
    {
        return $this->isMatchValue($value, $keys);
    }

    public function matchValue($value, $keys = []): bool
    {
        $this->resetRequiredCaptures();
        return $this->isMatchValue($value, $keys);
    }

    public function isMatchValue($value, $keys = []): bool
    {
        if ($this->matcher->matchValue($value, $keys) && $this->allRequiredCapturesMet()) {
            $this->capture($value, $keys);
            return true;
        }
        return false;
    }

    private function allRequiredCapturesMet(): bool
    {
        foreach ($this->requiredCaptures as $capture) {
            if (!$capture->isMatched()) {
                return false;
            }
        }
        return true;
    }

    private function resetRequiredCaptures(): void
    {
        foreach ($this->requiredCaptures as $capture) {
            $capture->reset();
        }
    }

    public function capture(mixed $value, array $keys): void
    {
        $this->current = $value;
        $this->currentKeys = $keys;
        $this->captures[] = $value;
    }

    public function subMatch($value, $keys = []): bool
    {
        return $this->isMatchValue($value, $keys);
    }
}
