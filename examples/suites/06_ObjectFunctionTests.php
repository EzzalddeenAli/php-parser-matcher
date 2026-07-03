<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class ObjectFunctionTests extends AstTestRunner
{
    // ─── new ─────────────────────────────────────────────────────────────────

    #[Example('new() wildcard — matches any object instantiation')]
    public function testNewWildcard(): void
    {
        $m = Ast::new();
        $this->assertMatches($m, ['new Foo()', 'new DateTime("now")', 'new self()']);
        $this->assertNotMatches($m, ['Foo::create()', '$obj']);
    }

    #[Example('new(name("Carbon")) — matches new Carbon(...)')]
    public function testNewExactClass(): void
    {
        $m = Ast::new(Ast::name('Carbon'));
        $this->assertMatches($m, ['new Carbon("2024-01-01")', 'new Carbon()']);
        $this->assertNotMatches($m, ['new DateTime()', 'Carbon::now()']);
    }

    #[Example('new with args — matches new Foo("bar") exactly')]
    public function testNewWithArgs(): void
    {
        $m = Ast::new(
            Ast::name('Exception'),
            [Ast::arg(Ast::stringLiteral('not found'))]
        );
        $this->assertMatches($m, ['new Exception("not found")']);
        $this->assertNotMatches($m, [
            'new Exception("server error")',
            'new RuntimeException("not found")',
            'new Exception()',
        ]);
    }

    // ─── instanceof ──────────────────────────────────────────────────────────

    #[Example('instanceof() wildcard — matches any instanceof check')]
    public function testInstanceofWildcard(): void
    {
        $m = Ast::instanceof();
        $this->assertMatches($m, ['$x instanceof Foo', '$model instanceof Model', '$e instanceof Exception']);
        $this->assertNotMatches($m, ['$x === Foo::class', 'is_a($x, "Foo")']);
    }

    #[Example('instanceof(var, name) — matches a specific type check')]
    public function testInstanceofSpecific(): void
    {
        $m = Ast::instanceof(Ast::variable('e'), Ast::name('RuntimeException'));
        $this->assertMatches($m, ['$e instanceof RuntimeException']);
        $this->assertNotMatches($m, [
            '$e instanceof Exception',          // wrong class
            '$ex instanceof RuntimeException',  // wrong var
        ]);
    }

    // ─── Closure ─────────────────────────────────────────────────────────────

    #[Example('closure() wildcard — matches any function() {} expression')]
    public function testClosureWildcard(): void
    {
        $m = Ast::closure();
        $this->assertMatches($m, [
            'function() {}',
            'function($x) { return $x; }',
            'static function() {}',
        ]);
        $this->assertNotMatches($m, ['fn() => true', 'function foo() {}']);
    }

    #[Example('closure(static: true) — matches only static closures')]
    public function testClosureStatic(): void
    {
        $m = Ast::closure(null, null, true);
        $this->assertMatches($m, ['static function() {}', 'static function($x) { return $x; }']);
        $this->assertNotMatches($m, ['function() {}']);
    }

    #[Example('closure with params — matches closure that takes specific params')]
    public function testClosureWithParams(): void
    {
        $m = Ast::closure([Ast::param('x')]);
        $this->assertMatches($m, ['function($x) {}', 'function($x) { return $x; }']);
        $this->assertNotMatches($m, ['function() {}', 'function($x, $y) {}']);
    }

    // ─── Arrow Function ──────────────────────────────────────────────────────

    #[Example('arrowFn() wildcard — matches any fn() => expr')]
    public function testArrowFnWildcard(): void
    {
        $m = Ast::arrowFn();
        $this->assertMatches($m, ['fn() => true', 'fn($x) => $x * 2', 'static fn() => null']);
        $this->assertNotMatches($m, ['function() {}']);
    }

    #[Example('arrowFn with expression — matches fn returning a specific expression')]
    public function testArrowFnWithExpr(): void
    {
        $m = Ast::arrowFn(null, Ast::binaryOp('*'));
        $this->assertMatches($m, ['fn($x, $y) => $x * $y', 'fn($n) => $n * 2']);
        $this->assertNotMatches($m, ['fn($x) => $x + 1', 'fn() => true']);
    }

    #[Example('arrowFn(static: true) — matches only static arrow functions')]
    public function testArrowFnStatic(): void
    {
        $m = Ast::arrowFn(null, null, true);
        $this->assertMatches($m, ['static fn() => true', 'static fn($x) => $x']);
        $this->assertNotMatches($m, ['fn() => true']);
    }

    // ─── Array Expression ─────────────────────────────────────────────────────

    #[Example('array() wildcard — matches any array literal [...]')]
    public function testArrayExprWildcard(): void
    {
        $m = Ast::array();
        $this->assertMatches($m, ['[]', '[1, 2, 3]', '["key" => "value"]', 'array()']);
        $this->assertNotMatches($m, ['$arr', 'foo()']);
    }

    #[Example('arrayItem(value, key) — matches a specific key => value pair inside an array')]
    public function testArrayItem(): void
    {
        // arrayItem matches individual array items; test within an array context
        $m = Ast::array([Ast::arrayItem(Ast::stringLiteral('users'), Ast::stringLiteral('table'))]);
        $this->assertMatches($m, ['["table" => "users"]']);
        $this->assertNotMatches($m, ['["table" => "posts"]', '["users" => "users"]']);
    }

    // ─── Throw Expression ─────────────────────────────────────────────────────

    #[Example('throw() — matches a throw expression (PHP 8+)')]
    public function testThrowExpr(): void
    {
        $m = Ast::throw();
        $this->assertMatches($m, [
            'throw new Exception("err")',
            'throw new RuntimeException("fail")',
        ]);
        $this->assertNotMatches($m, ['$x', 'new Exception()']);
    }

    #[Example('throw(new(name)) — matches throw new SpecificException()')]
    public function testThrowSpecific(): void
    {
        $m = Ast::throw(Ast::new(Ast::name('UnauthorizedException')));
        $this->assertMatches($m, ['throw new UnauthorizedException()']);
        $this->assertNotMatches($m, ['throw new ForbiddenException()', 'throw $e']);
    }
}
