<?php

namespace Fleet\AstMatcher\Matchers\Collections;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Matchers\Captures\CapturedMatcher;
use Fleet\AstMatcher\Matchers\Captures\CaptureGroup;

class SliceMatcher extends Matcher
{
    public int $min;
    public int $max;
    public Matcher $matcher;

    public function __construct(int $min, int $max, Matcher $matcher)
    {
        $this->min = $min;
        $this->max = $max;
        $this->matcher = $matcher;
    }

    public function matchValue($value, $keys = []): bool
    {
        return $this->matcher->matchValue($value, $keys);
    }

    /**
     * Capture all elements matched by this slice as a single array value.
     *
     *   $m = Ast::anyList(
     *       Ast::zeroOrMore(Ast::chain())->captureList('fields'),
     *   );
     *   Ast::match($m, $nodes);
     *   $fields = Ast::globalCaptures()->get('fields'); // → [chain1, chain2, ...]
     *
     * Use captureList() when you need the whole slice as an array.
     * Use capture() (on element matchers) when you need to collect items one-by-one.
     */
    public function captureList(?string $name = null, ?CaptureGroup $group = null): SliceCaptureMatcher
    {
        $captured = new CapturedMatcher();
        if ($name !== null) {
            ($group ?? CaptureGroup::global())->register($name, $captured);
        }
        return new SliceCaptureMatcher($this, $captured);
    }
}
