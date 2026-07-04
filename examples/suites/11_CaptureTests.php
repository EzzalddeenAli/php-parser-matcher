<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Name;

class CaptureTests extends AstTestRunner
{
    // ─── capture() — single unnamed slot ─────────────────────────────────────

    #[Example('capture() — first() returns the matched node')]
    public function testCaptureFirst(): void
    {
        $cap = Ast::capture(Ast::string());
        $m   = Ast::callExpression(Ast::name('route'), Ast::anyList(Ast::arg($cap), Ast::zeroOrMore()));

        $node = static::parseExpression("route('home')");
        $this->assertTrue($m->match($node));

        $this->assertTrue($cap->matched());
        $this->assertIsInstanceOf($cap->first(), String_::class);
        $this->assertTrue($cap->first()->value === 'home');
    }

    #[Example('capture() — all() collects every match (e.g. inside zeroOrMore)')]
    public function testCaptureAll(): void
    {
        $cap = Ast::capture(Ast::string());
        $m   = Ast::callExpression(
            Ast::name('route'),
            Ast::anyList(Ast::zeroOrMore(Ast::arg($cap)))
        );

        $node = static::parseExpression("route('home', 'GET', 'prefix')");
        $this->assertTrue($m->match($node));

        $this->assertTrue($cap->count() === 3);
        $values = array_map(fn($n) => $n->value, $cap->all());
        $this->assertTrue($values === ['home', 'GET', 'prefix']);
    }

    #[Example('capture() — last() returns the final matched node')]
    public function testCaptureLast(): void
    {
        $cap = Ast::capture(Ast::string());
        $m   = Ast::callExpression(
            Ast::name('func'),
            Ast::anyList(Ast::zeroOrMore(Ast::arg($cap)))
        );

        $this->assertTrue($m->match(static::parseExpression("func('a', 'b', 'c')")));
        $this->assertTrue($cap->last()->value === 'c');
    }

    #[Example('capture() — reset() clears results for reuse')]
    public function testCaptureReset(): void
    {
        $cap = Ast::capture(Ast::name());
        $m   = Ast::callExpression($cap);

        $m->match(static::parseExpression('foo()'));
        $this->assertTrue($cap->first()->toString() === 'foo');

        $cap->reset();
        $this->assertFalse($cap->matched());

        $m->match(static::parseExpression('bar()'));
        $this->assertTrue($cap->first()->toString() === 'bar');
    }

    #[Example('capture() accumulates across successive matches without reset')]
    public function testCaptureAccumulates(): void
    {
        $cap = Ast::capture(Ast::name());
        $m   = Ast::callExpression($cap);

        $m->match(static::parseExpression('foo()'));
        $m->match(static::parseExpression('bar()'));

        // Both are in all() — capture accumulates until reset() is called
        $this->assertTrue($cap->count() === 2);
        $names = array_map(fn($n) => $n->toString(), $cap->all());
        $this->assertTrue($names === ['foo', 'bar']);

        // first() = foo, last() = bar
        $this->assertTrue($cap->first()->toString() === 'foo');
        $this->assertTrue($cap->last()->toString() === 'bar');
    }

    // ─── Ast::captures() — named-capture bag ─────────────────────────────────

    #[Example('captures() — get() reads a named slot after the match')]
    public function testCaptureGroupGet(): void
    {
        $caps = Ast::captures();
        $m = Ast::callExpression(
            $caps->capture('fn', Ast::name()),
            Ast::anyList(
                Ast::arg($caps->capture('first', Ast::string())),
                Ast::zeroOrMore()
            )
        );

        $node = static::parseExpression("route('home', extra())");
        $this->assertTrue($m->match($node));

        $this->assertTrue($caps->get('fn')->toString() === 'route');
        $this->assertTrue($caps->get('first')->value === 'home');
    }

    #[Example('captures() — toArray() returns all slots as [name => first value]')]
    public function testCaptureGroupToArray(): void
    {
        $caps = Ast::captures();
        $m = Ast::binaryOp('===',
            Ast::assign($caps->capture('var', Ast::variable()), Ast::any()),
            $caps->capture('rhs', Ast::any())
        );

        $node = static::parseExpression('($x = foo()) === $x');
        $this->assertTrue($m->match($node));

        $arr = $caps->toArray();
        $this->assertTrue(array_key_exists('var', $arr));
        $this->assertTrue(array_key_exists('rhs', $arr));
    }

    #[Example('captures() — has() checks whether a slot was matched')]
    public function testCaptureGroupHas(): void
    {
        $caps = Ast::captures();
        $m = Ast::callExpression(
            Ast::name('func'),
            Ast::anyList(
                Ast::zeroOrMore(Ast::arg($caps->capture('args', Ast::string())))
            )
        );

        $this->assertTrue($m->match(static::parseExpression("func('a', 'b')")));
        $this->assertTrue($caps->has('args'));
        $this->assertTrue($caps->matcher('args')->count() === 2);

        $caps->reset();
        $this->assertTrue($m->match(static::parseExpression('func()')));
        $this->assertFalse($caps->has('args'));
    }

    #[Example('captures() — reset() clears all slots at once')]
    public function testCaptureGroupReset(): void
    {
        $caps = Ast::captures();
        $m = Ast::callExpression($caps->capture('fn', Ast::name()));

        $m->match(static::parseExpression('foo()'));
        $this->assertTrue($caps->has('fn'));

        $caps->reset();
        $this->assertFalse($caps->has('fn'));
    }

    // ─── fromCapture() ────────────────────────────────────────────────────────

    #[Example('fromCapture() — matches the same node captured earlier')]
    public function testFromCapture(): void
    {
        $cap = Ast::capture(Ast::variable());
        $m   = Ast::binaryOp('===', $cap, Ast::fromCapture($cap));

        $this->assertMatches($m, ['$x === $x', '$foo === $foo']);
        $this->assertNotMatches($m, ['$x === $y', '$x === $z']);
    }

    // ─── containerOf() ────────────────────────────────────────────────────────

    #[Example('containerOf() — matches any node that contains the target anywhere inside')]
    public function testContainerOf(): void
    {
        $m = Ast::containerOf(Ast::callExpression(Ast::name('abort')));

        $this->assertTrue($m->match(static::parseStatement('if ($x) { abort(403); }')));
        $this->assertFalse($m->match(static::parseStatement('if ($x) { return false; }')));
    }

    #[Example('containerOf() — first() returns the found inner node')]
    public function testContainerOfFirst(): void
    {
        $m = Ast::containerOf(Ast::stringLiteral('users'));

        $node = static::parseExpression('DB::table("users")->where("active", true)');
        $this->assertTrue($m->match($node));

        // first() = the String_("users") node that was found
        $found = $m->first();
        $this->assertIsInstanceOf($found, String_::class);
        $this->assertTrue($found->value === 'users');
    }

    #[Example('containerOf() — finds deeply nested pattern')]
    public function testContainerOfNested(): void
    {
        $m = Ast::containerOf(Ast::callExpression(Ast::name('abort')));

        $deep = static::parseStatement('if ($a) { if ($b) { abort(403); } }');
        $this->assertTrue($m->match($deep));

        $none = static::parseStatement('if ($a) { return null; }');
        $this->assertFalse($m->match($none));
    }
}
