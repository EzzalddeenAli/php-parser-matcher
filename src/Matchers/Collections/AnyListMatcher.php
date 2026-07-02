<?php

namespace Fleet\AstMatcher\Matchers\Collections;

use Fleet\AstMatcher\Core\Matcher;

class AnyListMatcher extends Matcher
{
    private array $matchers;
    private array $sliceMatchers;

    public function __construct(array $matchers)
    {
        $this->matchers = $matchers;
        $this->sliceMatchers = array_values(array_filter($matchers, fn($m) => $m instanceof SliceMatcher));
    }

    public function matchValue($array, $keys = []): bool
    {
        if (!is_array($array)) {
            return false;
        }
        if (count($this->matchers) === 0 && count($array) === 0) {
            return true;
        }
        $available = count($array) - count($this->matchers) + count($this->sliceMatchers);
        foreach (self::distributeAcrossSlices($this->sliceMatchers, $available) as $allocations) {
            $remaining = $array;
            $matched = true;
            $key = 0;
            foreach ($this->matchers as $matcher) {
                if ($matcher instanceof SliceMatcher) {
                    $count = array_shift($allocations) ?: 0;
                    while ($count-- > 0) {
                        $element = array_shift($remaining);
                        if (!$matcher->matchValue($element, array_merge($keys, [$key]))) {
                            $matched = false;
                            break 2;
                        }
                        $key++;
                    }
                } else {
                    $element = array_shift($remaining);
                    if (!$matcher->matchValue($element, array_merge($keys, [$key]))) {
                        $matched = false;
                        break;
                    }
                    $key++;
                }
            }
            if ($matched && count($remaining) === 0) {
                return true;
            }
        }
        return false;
    }

    private static function distributeAcrossSlices(array $slices, int $available): \Generator
    {
        if (count($slices) === 0) {
            yield [];
        } elseif (count($slices) === 1) {
            $s = $slices[0];
            if ($s->min <= $available && $available <= $s->max) {
                yield [$available];
            }
        } else {
            $last = $slices[count($slices) - 1];
            $allButLast = array_slice($slices, 0, -1);
            for ($n = $last->min; $n <= $last->max && $n <= $available; $n++) {
                foreach (self::distributeAcrossSlices($allButLast, $available - $n) as $prior) {
                    yield array_merge($prior, [$n]);
                }
            }
        }
    }
}
