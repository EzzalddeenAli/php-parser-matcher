<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class CaptureTests extends AstTestRunner
{
    // ─── capture ─────────────────────────────────────────────────────────────

    #[Example('capture() — captures the matched node for later inspection')]
    public function testCapture(): void
    {
        $cap = Ast::capture(Ast::stringLiteral());
        // Wrap capture in arg() so it matches the Arg node's inner value
        $m   = Ast::callExpression(Ast::name('route'), Ast::anyList(Ast::arg($cap), Ast::zeroOrMore()));

        $node = static::parseExpression("route('home')");
        $this->assertTrue($m->match($node));

        $captured = $cap->getCurrent();
        $this->assertNotNull($captured);
        // getCurrent() returns the matched String_ node (inside the Arg)
        $this->assertIsInstanceOf($captured, \PhpParser\Node\Scalar\String_::class);
        $this->assertTrue($captured->value === 'home');
    }

    #[Example('capture() updates getCurrent() on each successive match')]
    public function testCaptureReset(): void
    {
        $cap = Ast::capture(Ast::name());
        $m   = Ast::callExpression($cap);

        $node1 = static::parseExpression('foo()');
        $m->match($node1);
        $captured1 = $cap->getCurrent();
        $this->assertNotNull($captured1);

        $node2 = static::parseExpression('bar()');
        $m->match($node2);
        $captured2 = $cap->getCurrent();
        $this->assertNotNull($captured2);

        // The two captures should have different names
        $this->assertFalse($captured1->toString() === $captured2->toString());
    }

    // ─── fromCapture ─────────────────────────────────────────────────────────

    #[Example('fromCapture() — matches the same node as was previously captured')]
    public function testFromCapture(): void
    {
        // Pattern: $x === $x  (same variable on both sides)
        $cap = Ast::capture(Ast::variable());
        $m   = Ast::binaryOp('===', $cap, Ast::fromCapture($cap));

        $this->assertMatches($m, ['$x === $x', '$foo === $foo']);
        $this->assertNotMatches($m, ['$x === $y', '$x === $z']);
    }

    // ─── captureCollector ────────────────────────────────────────────────────

    #[Example('captureCollector() — wraps a CapturedMatcher and collects its results across iterations')]
    public function testCaptureCollector(): void
    {
        // captureCollector is designed to work with nested CapturedMatcher instances.
        // Verify it wraps correctly and the match succeeds.
        $inner = Ast::capture(Ast::stringLiteral());
        $col   = Ast::captureCollector($inner);

        $m = Ast::callExpression(
            Ast::name('route'),
            Ast::anyList(Ast::arg($col), Ast::zeroOrMore(Ast::arg($col)))
        );

        $node = static::parseExpression("route('home', 'GET')");
        $this->assertTrue($m->match($node));
        // At least one string was matched
        $this->assertNotNull($inner->getCurrent());
    }

    // ─── containerOf ─────────────────────────────────────────────────────────

    #[Example('containerOf() — matches any node that contains the target anywhere inside')]
    public function testContainerOf(): void
    {
        $m = Ast::containerOf(Ast::callExpression(Ast::name('abort')));

        $node = static::parseStatement('if ($x) { abort(403); }');
        $this->assertTrue($m->match($node));

        $node2 = static::parseStatement('if ($x) { return false; }');
        $this->assertTrue(!$m->match($node2));
    }

    #[Example('containerOf in a method call chain — finds deeply nested pattern')]
    public function testContainerOfNested(): void
    {
        $m = Ast::containerOf(Ast::stringLiteral('users'));

        $node = static::parseExpression('DB::table("users")->where("active", true)');
        $this->assertTrue($m->match($node));

        $node2 = static::parseExpression('DB::table("posts")->where("active", true)');
        $this->assertTrue(!$m->match($node2));
    }
}
