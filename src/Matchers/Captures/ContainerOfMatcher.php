<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ContainerOfMatcher extends CapturedMatcher
{
    private Matcher $containedMatcher;
    private ?Matcher $whenCond = null;

    public function __construct(Matcher $containedMatcher)
    {
        parent::__construct();
        $this->containedMatcher = $containedMatcher;
    }

    public function whenChild(Matcher $cond): static
    {
        $this->whenCond = $cond;
        return $this;
    }

    public function matchValue($value, $keys = []): bool
    {
        if (is_array($value)) {
            foreach ($value as $i => $element) {
                if ($this->matchValue($element, array_merge($keys, [$i]))) {
                    return true;
                }
            }
        }
        if (!NodeTypes::isNode($value)) {
            return false;
        }
        if ($this->containedMatcher->matchValue($value, $keys)) {
            $this->capture($value, $keys);
            return true;
        }
        foreach ($value->getSubNodeNames() as $key) {
            $sub = $value->{$key};
            if (is_array($sub)) {
                foreach ($sub as $i => $element) {
                    if ($this->checkSubNode($element)) {
                        if ($this->matchValue($element, array_merge($keys, [$key, $i]))) {
                            return true;
                        }
                    }
                }
            } else {
                if ($this->checkSubNode($sub)) {
                    if ($this->matchValue($sub, array_merge($keys, [$key]))) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function checkSubNode(mixed $node): bool
    {
        if ($this->whenCond !== null) {
            return $this->whenCond->match($node) || $this->containedMatcher->match($node);
        }
        return true;
    }
}
