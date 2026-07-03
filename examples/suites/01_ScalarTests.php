<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class ScalarTests extends AstTestRunner
{
    // ─── String Literals ─────────────────────────────────────────────────────

    #[Example('stringLiteral() — wildcard matches any string')]
    public function testStringWildcard(): void
    {
        $m = Ast::stringLiteral();
        $this->assertMatches($m, ["'hello'", '"world"', "''", "'multi word string'"]);
        $this->assertNotMatches($m, ['42', 'true', '$var']);
    }

    #[Example('stringLiteral("foo") — matches only that exact value')]
    public function testStringExact(): void
    {
        $m = Ast::stringLiteral('hello');
        $this->assertMatches($m, ["'hello'", '"hello"']);
        $this->assertNotMatches($m, ["'world'", "'Hello'", "'hello world'"]);
    }

    #[Example('string() is an alias for stringLiteral()')]
    public function testStringAlias(): void
    {
        $m = Ast::string('bar');
        $this->assertMatches($m, ["'bar'"]);
        $this->assertNotMatches($m, ["'baz'"]);
    }

    // ─── Number Literals ─────────────────────────────────────────────────────

    #[Example('numberLiteral() — wildcard matches any int or float')]
    public function testNumberWildcard(): void
    {
        $m = Ast::numberLiteral();
        $this->assertMatches($m, ['42', '0', '3.14', '1_000_000']);
        $this->assertNotMatches($m, ["'42'", 'true', '$n']);
    }

    #[Example('numberLiteral(42) — matches 42 as int or float (loose ==)')]
    public function testNumberExact(): void
    {
        $m = Ast::numberLiteral(42);
        $this->assertMatches($m, ['42', '42.0']);
        $this->assertNotMatches($m, ['43', '41.9', "'42'"]);
    }

    #[Example('number() is an alias for numberLiteral()')]
    public function testNumberAlias(): void
    {
        $m = Ast::number(0);
        $this->assertMatches($m, ['0', '0.0']);
        $this->assertNotMatches($m, ['1']);
    }

    // ─── Boolean / Null Constants ─────────────────────────────────────────────

    #[Example('true(), false(), null() — match PHP keywords')]
    public function testBoolNullConstants(): void
    {
        $this->assertMatches(Ast::true(),  ['true',  'TRUE']);
        $this->assertMatches(Ast::false(), ['false', 'FALSE']);
        $this->assertMatches(Ast::null(),  ['null',  'NULL']);

        $this->assertNotMatches(Ast::true(),  ['false', 'null', '1']);
        $this->assertNotMatches(Ast::false(), ['true',  'null', '0']);
        $this->assertNotMatches(Ast::null(),  ['true',  'false']);
    }

    #[Example('constFetch("PHP_EOL") — matches a named constant')]
    public function testNamedConstant(): void
    {
        $m = Ast::constFetch('PHP_EOL');
        $this->assertMatches($m, ['PHP_EOL']);
        $this->assertNotMatches($m, ['PHP_INT_MAX', 'true']);
    }
}
