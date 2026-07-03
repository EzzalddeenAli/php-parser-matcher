<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class CallTests extends AstTestRunner
{
    // ─── Function Call ────────────────────────────────────────────────────────

    #[Example('callExpression() wildcard — matches any function call')]
    public function testFuncCallWildcard(): void
    {
        $m = Ast::callExpression();
        $this->assertMatches($m, ['foo()', 'bar(1, 2)', 'abort(403)', '__("label")']);
        $this->assertNotMatches($m, ['$obj->method()', 'Str::method()']);
    }

    #[Example('callExpression(name("foo")) — matches calls to foo() with any args')]
    public function testFuncCallByName(): void
    {
        $m = Ast::callExpression(Ast::name('abort'));
        $this->assertMatches($m, ['abort()', 'abort(403)', 'abort(404, "Not Found")']);
        $this->assertNotMatches($m, ['exit()', 'die()', '$fn()']);
    }

    #[Example('callExpression with exact args — matches foo("bar") only')]
    public function testFuncCallWithArgs(): void
    {
        $m = Ast::callExpression(
            Ast::name('route'),
            [Ast::arg(Ast::stringLiteral('home'))]
        );
        $this->assertMatches($m, ["route('home')"]);
        $this->assertNotMatches($m, ["route('about')", "route('home', ['id' => 1])", 'route()']);
    }

    // ─── Method Call ─────────────────────────────────────────────────────────

    #[Example('methodCall() wildcard — matches any method call')]
    public function testMethodCallWildcard(): void
    {
        $m = Ast::methodCall();
        $this->assertMatches($m, ['$obj->method()', '$this->save()', '$q->where("x", 1)']);
        $this->assertNotMatches($m, ['foo()', 'Str::method()']);
    }

    #[Example('methodCall($obj, $name) — matches a specific object and method')]
    public function testMethodCallSpecific(): void
    {
        $m = Ast::methodCall(Ast::variable('query'), Ast::name('where'));
        $this->assertMatches($m, [
            '$query->where("active", true)',
            '$query->where("status", "open")',
        ]);
        $this->assertNotMatches($m, [
            '$query->orderBy("name")',   // wrong method
            '$builder->where("x", 1)', // wrong object
        ]);
    }

    // ─── Static Call ─────────────────────────────────────────────────────────

    #[Example('staticCall(name("DB")) — matches any DB:: call')]
    public function testStaticCallByClass(): void
    {
        $m = Ast::staticCall(Ast::name('DB'));
        $this->assertMatches($m, [
            'DB::table("users")',
            'DB::select("SELECT 1")',
            'DB::statement("SET FOREIGN_KEY_CHECKS=0")',
        ]);
        $this->assertNotMatches($m, ['Cache::get("key")', 'DB::class']);
    }

    #[Example('staticCall(name, name) — matches class::method precisely')]
    public function testStaticCallExact(): void
    {
        $m = Ast::staticCall(Ast::name('Config'), Ast::name('get'));
        $this->assertMatches($m, ["Config::get('app.name')", 'Config::get("debug")']);
        $this->assertNotMatches($m, ['Config::set("k", "v")', 'Cache::get("k")']);
    }

    // ─── Nullsafe Method Call ─────────────────────────────────────────────────

    #[Example('nullsafeCall() — matches $obj?->method() only')]
    public function testNullsafeCall(): void
    {
        $m = Ast::nullsafeCall(Ast::variable('user'), Ast::name('getName'));
        $this->assertMatches($m, ['$user?->getName()']);
        $this->assertNotMatches($m, [
            '$user->getName()',        // not nullsafe
            '$admin?->getName()',      // wrong variable
            '$user?->getEmail()',      // wrong method
        ]);
    }

    // ─── call() alias ────────────────────────────────────────────────────────

    #[Example('call() is an alias for callExpression()')]
    public function testCallAlias(): void
    {
        $m = Ast::call(Ast::name('now'));
        $this->assertMatches($m, ['now()']);
        $this->assertNotMatches($m, ['Carbon::now()']);
    }
}
