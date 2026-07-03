<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class CollectionTests extends AstTestRunner
{
    // ─── tupleOf ─────────────────────────────────────────────────────────────

    #[Example('tupleOf() — matches array with exact count and types')]
    public function testTupleOf(): void
    {
        $m = Ast::callExpression(
            Ast::name('foo'),
            Ast::tupleOf(Ast::arg(Ast::stringLiteral()), Ast::arg(Ast::numberLiteral()))
        );
        $this->assertMatches($m, ['foo("bar", 42)', 'foo("x", 0)']);
        $this->assertNotMatches($m, [
            'foo("bar")',          // too few args
            'foo("bar", 42, [])', // too many args
            'foo(42, "bar")',     // wrong order
        ]);
    }

    // ─── anyList ─────────────────────────────────────────────────────────────

    #[Example('anyList() — matches list containing an element anywhere')]
    public function testAnyListContains(): void
    {
        $m = Ast::callExpression(
            Ast::name('foo'),
            Ast::anyList(Ast::zeroOrMore(), Ast::arg(Ast::stringLiteral('special')), Ast::zeroOrMore())
        );
        $this->assertMatches($m, [
            'foo("special")',
            'foo(1, "special")',
            'foo("special", 2)',
            'foo(1, "special", 2)',
        ]);
        $this->assertNotMatches($m, ['foo()', 'foo(1, 2)']);
    }

    #[Example('anyList starts with — matches arg list starting with a string')]
    public function testAnyListStartsWith(): void
    {
        $m = Ast::callExpression(
            null,
            Ast::anyList(Ast::arg(Ast::stringLiteral()), Ast::zeroOrMore())
        );
        $this->assertMatches($m, ['foo("first", 2, 3)', 'bar("key")']);
        $this->assertNotMatches($m, ['foo()', 'foo(1, "second")']);
    }

    // ─── arrayOf ─────────────────────────────────────────────────────────────

    #[Example('arrayOf(matcher) — matches list where every element satisfies matcher')]
    public function testArrayOf(): void
    {
        $m = Ast::callExpression(
            null,
            Ast::arrayOf(Ast::arg(Ast::stringLiteral()))
        );
        $this->assertMatches($m, ['foo("a", "b", "c")', 'bar("x")', 'baz()']);
        $this->assertNotMatches($m, ['foo("a", 42)', 'foo(true, "b")']);
    }

    // ─── zeroOrMore ──────────────────────────────────────────────────────────

    #[Example('zeroOrMore() — matches 0 or more of anything')]
    public function testZeroOrMore(): void
    {
        $m = Ast::callExpression(
            Ast::name('log'),
            Ast::anyList(Ast::arg(Ast::stringLiteral()), Ast::zeroOrMore())
        );
        $this->assertMatches($m, ['log("msg")', 'log("msg", $ctx)', 'log("msg", $ctx, $extra)']);
        $this->assertNotMatches($m, ['log()', 'log(42)', 'log(42, "msg")']);
    }

    #[Example('zeroOrMore(matcher) — matches 0 or more elements of a specific type')]
    public function testZeroOrMoreTyped(): void
    {
        $m = Ast::callExpression(
            null,
            Ast::anyList(Ast::arg(Ast::stringLiteral()), Ast::zeroOrMore(Ast::arg(Ast::numberLiteral())))
        );
        $this->assertMatches($m, ['foo("a")', 'foo("a", 1)', 'foo("a", 1, 2)']);
        $this->assertNotMatches($m, ['foo("a", "b")', 'foo("a", 1, "c")']);
    }

    // ─── oneOrMore ───────────────────────────────────────────────────────────

    #[Example('oneOrMore() — matches 1 or more of anything')]
    public function testOneOrMore(): void
    {
        $m = Ast::callExpression(
            Ast::name('collect'),
            Ast::anyList(Ast::oneOrMore())
        );
        $this->assertMatches($m, ['collect($a)', 'collect($a, $b)']);
        $this->assertNotMatches($m, ['collect()']);
    }

    #[Example('oneOrMore(stringLiteral) — requires at least one string arg')]
    public function testOneOrMoreTyped(): void
    {
        $m = Ast::callExpression(
            null,
            Ast::anyList(Ast::oneOrMore(Ast::arg(Ast::stringLiteral())))
        );
        $this->assertMatches($m, ['foo("a")', 'foo("a", "b", "c")']);
        $this->assertNotMatches($m, ['foo()', 'foo(1)', 'foo("a", 1)']);
    }

    // ─── spacer ───────────────────────────────────────────────────────────────

    #[Example('spacer(2) — matches exactly 2 elements when used in anyList')]
    public function testSpacerExact(): void
    {
        // spacer must be used inside anyList (not tupleOf) to consume multiple positions
        $m = Ast::callExpression(null, Ast::anyList(Ast::spacer(2)));
        $this->assertMatches($m, ['foo(1, 2)', 'foo("a", "b")']);
        $this->assertNotMatches($m, ['foo(1)', 'foo(1, 2, 3)']);
    }

    #[Example('spacer(min, max) — matches a range of elements')]
    public function testSpacerRange(): void
    {
        $m = Ast::callExpression(null, Ast::anyList(Ast::spacer(1, 3)));
        $this->assertMatches($m, ['foo(1)', 'foo(1, 2)', 'foo(1, 2, 3)']);
        $this->assertNotMatches($m, ['foo()', 'foo(1, 2, 3, 4)']);
    }

    // ─── or() ────────────────────────────────────────────────────────────────

    #[Example('or() — matches if any sub-matcher matches')]
    public function testOrMatcher(): void
    {
        $m = Ast::staticCall(Ast::or(Ast::name('DB'), Ast::name('Cache')));
        $this->assertMatches($m, ['DB::table("users")', 'Cache::get("key")', 'DB::select("sql")']);
        $this->assertNotMatches($m, ['Log::info("msg")', 'Auth::user()']);
    }

    // ─── predicate ───────────────────────────────────────────────────────────

    #[Example('predicate($fn) — custom match logic')]
    public function testPredicate(): void
    {
        $m = Ast::callExpression(
            Ast::predicate(fn($node) => $node instanceof \PhpParser\Node\Name
                && str_starts_with($node->toString(), 'str_'))
        );
        $this->assertMatches($m, ['str_replace("a", "b", $s)', 'str_contains($s, "x")']);
        $this->assertNotMatches($m, ['array_map(fn($x) => $x, [])', 'sprintf("%s", $x)']);
    }
}
