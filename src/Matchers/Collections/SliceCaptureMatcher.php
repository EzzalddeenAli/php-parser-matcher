<?php

namespace Fleet\AstMatcher\Matchers\Collections;

use Fleet\AstMatcher\Matchers\Captures\CapturedMatcher;

/**
 * A SliceMatcher that captures all matched elements as a single array value
 * once the full AnyListMatcher distribution succeeds.
 *
 * Unlike capture() which records each element individually as it is visited,
 * this waits until the whole slice is confirmed (no backtrack) and then stores
 * the complete element array as one capture entry.
 *
 * Create via SliceMatcher::captureList():
 *
 *   $m = Ast::callExpression(
 *       Ast::name('fields'),
 *       Ast::anyList(
 *           Ast::zeroOrMore(Ast::arg(Ast::chain()))->captureList('fields')
 *       )
 *   );
 *   Ast::match($m, $node);
 *   $fields = Ast::globalCaptures()->get('fields'); // → [Arg, Arg, Arg]
 */
class SliceCaptureMatcher extends SliceMatcher
{
    private CapturedMatcher $capturedMatcher;

    public function __construct(SliceMatcher $slice, CapturedMatcher $capturedMatcher)
    {
        parent::__construct($slice->min, $slice->max, $slice->matcher);
        $this->capturedMatcher = $capturedMatcher;
    }

    /**
     * Called by AnyListMatcher ONLY after the entire distribution succeeds —
     * so this is never triggered for failed backtrack attempts.
     */
    public function commitBatch(array $elements): void
    {
        $this->capturedMatcher->captureArray($elements);
    }

    public function getCaptured(): CapturedMatcher
    {
        return $this->capturedMatcher;
    }
}
