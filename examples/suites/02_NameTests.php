<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class NameTests extends AstTestRunner
{
    // ─── Identifiers / Names ──────────────────────────────────────────────────

    #[Example('name() wildcard — matches any identifier or qualified name')]
    public function testNameWildcard(): void
    {
        // Ast::name() matches Node\Name and Node\Identifier nodes.
        // These appear as callees, class references, etc. — verify via staticCall context.
        $m = Ast::staticCall(Ast::name());
        $this->assertMatches($m, ['Foo::bar()', 'DB::table("x")', 'Cache::get("k")']);
        $this->assertNotMatches($m, ['$obj->method()', 'foo()']);
    }

    #[Example('name("Foo") — matches an identifier with that exact string')]
    public function testNameExact(): void
    {
        // Inside a static call, $node->class is a Name node
        $m = Ast::staticCall(Ast::name('DB'));
        $this->assertMatches($m, ['DB::table("users")', 'DB::select("SELECT 1")']);
        $this->assertNotMatches($m, ['Cache::get("key")', 'App::make("foo")']);
    }

    #[Example('identifier() is an alias for name()')]
    public function testIdentifierAlias(): void
    {
        $m = Ast::staticCall(Ast::identifier('Log'));
        $this->assertMatches($m, ['Log::info("msg")', 'Log::error("err")']);
        $this->assertNotMatches($m, ['Cache::get("key")']);
    }

    // ─── Variables ────────────────────────────────────────────────────────────

    #[Example('variable() wildcard — matches any variable')]
    public function testVariableWildcard(): void
    {
        $m = Ast::variable();
        $this->assertMatches($m, ['$foo', '$bar', '$this', '$_SERVER']);
        $this->assertNotMatches($m, ["'string'", '42', 'true']);
    }

    #[Example('variable("user") — matches only $user')]
    public function testVariableExact(): void
    {
        $m = Ast::variable('user');
        $this->assertMatches($m, ['$user']);
        $this->assertNotMatches($m, ['$users', '$User', '$request']);
    }

    #[Example('var() is an alias for variable()')]
    public function testVarAlias(): void
    {
        $m = Ast::var('request');
        $this->assertMatches($m, ['$request']);
        $this->assertNotMatches($m, ['$req']);
    }

    // ─── Name Matcher Scope ───────────────────────────────────────────────────

    #[Example('name() matches both Node\\Name and Node\\Identifier nodes')]
    public function testNameMatchesBothNodeTypes(): void
    {
        // In a static call:  $node->class is Name,  $node->name is Identifier
        // Ast::name() matches both — useful for writing flexible patterns
        $m = Ast::staticCall(Ast::name('Text'), Ast::name('make'));
        $this->assertMatches($m, ["Text::make('foo', 'bar')"]);
        $this->assertNotMatches($m, ["Text::create('foo', 'bar')", "Block::make('foo')"]);
    }
}
