<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class AccessTests extends AstTestRunner
{
    // ─── Property Fetch ───────────────────────────────────────────────────────

    #[Example('propertyFetch() wildcard — matches any $obj->prop access')]
    public function testPropertyFetchWildcard(): void
    {
        $m = Ast::propertyFetch();
        $this->assertMatches($m, ['$obj->name', '$this->id', '$user->email']);
        $this->assertNotMatches($m, ['$obj->method()', 'Foo::STATUS', '$arr["key"]']);
    }

    #[Example('propertyFetch($obj, name) — matches $obj->name specifically')]
    public function testPropertyFetchSpecific(): void
    {
        $m = Ast::propertyFetch(Ast::variable('user'), Ast::name('email'));
        $this->assertMatches($m, ['$user->email']);
        $this->assertNotMatches($m, [
            '$user->name',       // wrong property
            '$request->email',   // wrong object
        ]);
    }

    #[Example('memberExpression() is an alias for propertyFetch()')]
    public function testMemberExpressionAlias(): void
    {
        $m = Ast::memberExpression(Ast::variable('this'), Ast::name('model'));
        $this->assertMatches($m, ['$this->model']);
        $this->assertNotMatches($m, ['$this->table']);
    }

    #[Example('nullsafeProp() — matches $obj?->prop only')]
    public function testNullsafePropertyFetch(): void
    {
        $m = Ast::nullsafeProp(Ast::variable('user'), Ast::name('address'));
        $this->assertMatches($m, ['$user?->address']);
        $this->assertNotMatches($m, [
            '$user->address',      // not nullsafe
            '$admin?->address',    // wrong object
        ]);
    }

    // ─── Class Constant Fetch ─────────────────────────────────────────────────

    #[Example('classConstFetch() wildcard — matches any Class::CONST')]
    public function testClassConstFetchWildcard(): void
    {
        $m = Ast::classConstFetch();
        $this->assertMatches($m, ['Foo::BAR', 'Status::ACTIVE', 'self::LIMIT']);
        $this->assertNotMatches($m, ['Foo::bar()', '$obj->value']);
    }

    #[Example('classConstFetch(class, name) — matches Class::NAME precisely')]
    public function testClassConstFetchExact(): void
    {
        $m = Ast::classConstFetch(Ast::name('Status'), Ast::name('ACTIVE'));
        $this->assertMatches($m, ['Status::ACTIVE']);
        $this->assertNotMatches($m, ['Status::INACTIVE', 'Role::ACTIVE']);
    }

    #[Example('classConstFetch with ::class — matches Foo::class magic constant')]
    public function testClassConstFetchClass(): void
    {
        $m = Ast::classConstFetch(null, Ast::name('class'));
        $this->assertMatches($m, ['Foo::class', 'App\\Models\\User::class', 'self::class']);
        $this->assertNotMatches($m, ['Foo::BAR', 'Foo::bar()']);
    }

    // ─── Const Fetch ─────────────────────────────────────────────────────────

    #[Example('constFetch() wildcard — matches any bare constant (true/false/null/PHP_EOL etc.)')]
    public function testConstFetchWildcard(): void
    {
        $m = Ast::constFetch();
        $this->assertMatches($m, ['true', 'false', 'null', 'PHP_EOL', 'PHP_INT_MAX']);
        $this->assertNotMatches($m, ["'string'", '42', '$var', 'Foo::BAR']);
    }

    #[Example('constFetch("PHP_EOL") — matches one specific constant')]
    public function testConstFetchSpecific(): void
    {
        $m = Ast::constFetch('PHP_EOL');
        $this->assertMatches($m, ['PHP_EOL']);
        $this->assertNotMatches($m, ['PHP_INT_MAX', 'true', 'null']);
    }

    // ─── Array Dimension Fetch ────────────────────────────────────────────────

    #[Example('arrayAccess() wildcard — matches any $arr[...] access')]
    public function testArrayAccessWildcard(): void
    {
        $m = Ast::arrayAccess();
        $this->assertMatches($m, ['$arr[0]', '$arr["key"]', '$config["app"]["name"]']);
        $this->assertNotMatches($m, ['$arr', 'foo()']);
    }

    #[Example('arrayAccess($var, $dim) — matches $config["key"] specifically')]
    public function testArrayAccessSpecific(): void
    {
        $m = Ast::arrayAccess(Ast::variable('config'), Ast::stringLiteral('debug'));
        $this->assertMatches($m, ['$config["debug"]', '$config[\'debug\']']);
        $this->assertNotMatches($m, ['$config["app"]', '$env["debug"]']);
    }
}
