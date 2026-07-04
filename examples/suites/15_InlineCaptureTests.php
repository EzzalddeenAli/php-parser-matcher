<?php
/**
 * Suite 15 — Inline Capture API
 *
 * Demonstrates the fluent ->capture() method available on every Matcher,
 * the global CaptureGroup, and the Ast::match() auto-reset helper.
 *
 * Styles compared:
 *   Classic:  $cap = Ast::capture(Ast::name()); ... $cap->first()
 *   Inline:   Ast::name()->capture('fn')  then  Ast::globalCaptures()->get('fn')
 *
 * Run via:  php examples/run.php --suite=15
 */

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Name;

class InlineCaptureTests extends AstTestRunner
{
    // ─── ->capture() anonymous ────────────────────────────────────────────────

    #[Example('->capture() with no name — equivalent to Ast::capture($this)')]
    public function testAnonymousInlineCapture(): void
    {
        // Inline creation: no name → not registered anywhere, just a CapturedMatcher
        $cap = Ast::name()->capture();

        $m = Ast::callExpression($cap);
        $this->assertTrue($m->match(static::parseExpression("route('home')")));

        $this->assertTrue($cap->matched());
        $this->assertTrue($cap->first()->toString() === 'route');
    }

    #[Example('->capture() can wrap any matcher — string, arg, call, etc.')]
    public function testInlineCaptureDifferentMatchers(): void
    {
        $capStr  = Ast::string()->capture();
        $capCall = Ast::callExpression()->capture();

        $m = Ast::anyList(
            Ast::arg($capStr),
            Ast::arg($capCall)
        );

        $args = static::parseExpression("foo('hello', bar())")->args;
        $this->assertTrue($m->matchValue($args));

        $this->assertIsInstanceOf($capStr->first(), String_::class);
        $this->assertTrue($capStr->first()->value === 'hello');
        $this->assertIsInstanceOf($capCall->first(), \PhpParser\Node\Expr\FuncCall::class);
    }

    // ─── ->capture('name') — global group ────────────────────────────────────

    #[Example('->capture("name") registers in the global group; Ast::match() auto-resets')]
    public function testInlineGlobalCapture(): void
    {
        $m = Ast::callExpression(
            Ast::name()->capture('fn'),
            Ast::anyList(
                Ast::arg(Ast::string()->capture('path')),
                Ast::zeroOrMore()
            )
        );

        $this->assertTrue(Ast::match($m, static::parseExpression("route('home')")));

        $this->assertTrue(Ast::globalCaptures()->get('fn')->toString() === 'route');
        $this->assertTrue(Ast::globalCaptures()->get('path')->value === 'home');
    }

    #[Example('Ast::match() resets global captures before each call')]
    public function testAstMatchAutoReset(): void
    {
        $m = Ast::callExpression(Ast::name()->capture('fn'));

        Ast::match($m, static::parseExpression("foo()"));
        $this->assertTrue(Ast::globalCaptures()->get('fn')->toString() === 'foo');

        // Second call auto-resets: only 'bar', not accumulation of foo+bar
        Ast::match($m, static::parseExpression("bar()"));
        $this->assertTrue(Ast::globalCaptures()->get('fn')->toString() === 'bar');
        $this->assertTrue(Ast::globalCaptures()->matcher('fn')->count() === 1);
    }

    #[Example('Ast::resetCaptures() + $matcher->match() — manual reset for explicit control')]
    public function testManualResetCaptures(): void
    {
        $m = Ast::callExpression(Ast::name()->capture('fn'));

        $m->match(static::parseExpression("foo()"));
        $this->assertTrue(Ast::globalCaptures()->get('fn')->toString() === 'foo');

        Ast::resetCaptures();
        $m->match(static::parseExpression("bar()"));
        $this->assertTrue(Ast::globalCaptures()->get('fn')->toString() === 'bar');
        $this->assertTrue(Ast::globalCaptures()->matcher('fn')->count() === 1);
    }

    #[Example('Inline capture with zeroOrMore — global group collects all via matcher()')]
    public function testInlineGlobalCaptureMultiple(): void
    {
        $m = Ast::callExpression(
            Ast::name()->capture('fn'),
            Ast::anyList(Ast::zeroOrMore(Ast::arg(Ast::string()->capture('args'))))
        );

        Ast::match($m, static::parseExpression("route('home', 'GET', 'v1')"));

        $g = Ast::globalCaptures();
        $this->assertTrue($g->get('fn')->toString() === 'route');
        $this->assertTrue($g->matcher('args')->count() === 3);
        $values = array_map(fn($n) => $n->value, $g->matcher('args')->all());
        $this->assertTrue($values === ['home', 'GET', 'v1']);
    }

    // ─── ->capture('name', $group) — explicit group ───────────────────────────

    #[Example('->capture("name", $group) registers in an explicit group, not the global one')]
    public function testInlineExplicitGroupCapture(): void
    {
        $group = Ast::captures();

        $m = Ast::callExpression(
            Ast::name()->capture('fn', $group),
            Ast::anyList(Ast::arg(Ast::string()->capture('arg', $group)), Ast::zeroOrMore())
        );

        // Clear any 'fn' data left in the global group by earlier tests
        Ast::resetCaptures();
        $m->match(static::parseExpression("table('users')"));

        // Read from the explicit group
        $this->assertTrue($group->get('fn')->toString() === 'table');
        $this->assertTrue($group->get('arg')->value === 'users');

        // Global group is untouched by this match
        $this->assertFalse(Ast::globalCaptures()->has('fn'));
    }

    #[Example('multiple explicit groups can coexist independently')]
    public function testMultipleExplicitGroups(): void
    {
        $g1 = Ast::captures();
        $g2 = Ast::captures();

        $m1 = Ast::callExpression(Ast::name()->capture('fn', $g1));
        $m2 = Ast::callExpression(Ast::name()->capture('fn', $g2));

        $m1->match(static::parseExpression("foo()"));
        $m2->match(static::parseExpression("bar()"));

        $this->assertTrue($g1->get('fn')->toString() === 'foo');
        $this->assertTrue($g2->get('fn')->toString() === 'bar');
    }

    // ─── Mixing styles ────────────────────────────────────────────────────────

    #[Example('classic Ast::captures() group and inline ->capture() can live in the same pattern')]
    public function testMixedStyles(): void
    {
        $caps = Ast::captures();

        // First arg via classic group, second arg via inline anonymous cap
        $capSecond = Ast::string()->capture();

        $m = Ast::callExpression(
            Ast::name(),
            Ast::anyList(
                Ast::arg($caps->capture('first', Ast::string())),
                Ast::arg($capSecond)
            )
        );

        $m->match(static::parseExpression("route('home', 'GET')"));

        $this->assertTrue($caps->get('first')->value === 'home');
        $this->assertTrue($capSecond->first()->value === 'GET');
    }

    #[Example('->capture() result is a CapturedMatcher — all its methods work normally')]
    public function testInlineCaptureIsFullCapturedMatcher(): void
    {
        $cap = Ast::string()->capture('word');

        $m = Ast::callExpression(
            Ast::name(),
            Ast::anyList(Ast::zeroOrMore(Ast::arg($cap)))
        );

        Ast::match($m, static::parseExpression("log('a', 'b', 'c')"));

        $this->assertTrue($cap->count() === 3);
        $this->assertTrue($cap->first()->value === 'a');
        $this->assertTrue($cap->last()->value === 'c');
        $values = array_map(fn($n) => $n->value, $cap->all());
        $this->assertTrue($values === ['a', 'b', 'c']);
    }
}

InlineCaptureTests::run();
