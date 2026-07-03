<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class OperationTests extends AstTestRunner
{
    // ─── Binary Operations ────────────────────────────────────────────────────

    #[Example('binaryOp() wildcard — matches any binary expression')]
    public function testBinaryOpWildcard(): void
    {
        $m = Ast::binaryOp();
        $this->assertMatches($m, ['$a + $b', '$a === $b', '$a && $b', '$a . $b', '$a ?? $b']);
        $this->assertNotMatches($m, ['!$a', '$a', '$a = $b']);
    }

    #[Example('binaryOp("+") — matches only addition expressions')]
    public function testBinaryOpAddition(): void
    {
        $m = Ast::binaryOp('+');
        $this->assertMatches($m, ['$a + $b', '1 + 2', '$x + 10']);
        $this->assertNotMatches($m, ['$a - $b', '$a * $b', '$a . $b']);
    }

    #[Example('binaryOp("===") — matches strict equality')]
    public function testBinaryOpStrictEq(): void
    {
        $m = Ast::binaryOp('===', Ast::variable('type'), Ast::stringLiteral('admin'));
        $this->assertMatches($m, ['$type === "admin"', '$type === \'admin\'']);
        $this->assertNotMatches($m, ['$type == "admin"', '$role === "admin"']);
    }

    #[Example('binaryOp("??") — matches null-coalescing operator')]
    public function testBinaryOpNullCoalesce(): void
    {
        $m = Ast::binaryOp('??');
        $this->assertMatches($m, ['$a ?? "default"', '$config["key"] ?? null', '$user->name ?? ""']);
        $this->assertNotMatches($m, ['$a ?: "default"', '$a || $b']);
    }

    #[Example('logicalExpression() is an alias for binaryOp()')]
    public function testLogicalExpressionAlias(): void
    {
        $m = Ast::logicalExpression('&&');
        $this->assertMatches($m, ['$a && $b', '$isAdmin && $isActive']);
        $this->assertNotMatches($m, ['$a || $b', '$a and $b']);
    }

    // ─── Unary Operations ─────────────────────────────────────────────────────

    #[Example('unaryOp("!") — matches boolean NOT expressions')]
    public function testUnaryOpNot(): void
    {
        $m = Ast::unaryOp('!');
        $this->assertMatches($m, ['!$active', '!$user->isAdmin()']);
        $this->assertNotMatches($m, ['~$x', '-$x', '$active']);
    }

    #[Example('unaryOp("-") — matches negation')]
    public function testUnaryOpMinus(): void
    {
        $m = Ast::unaryOp('-');
        $this->assertMatches($m, ['-$x', '-42']);
        $this->assertNotMatches($m, ['+$x', '!$x', '$x - $y']);
    }

    #[Example('unaryOp("++") — matches pre-increment')]
    public function testUnaryOpPreInc(): void
    {
        $m = Ast::unaryOp('++');
        $this->assertMatches($m, ['++$i']);
        $this->assertNotMatches($m, ['$i++', '--$i', '$i + 1']);
    }

    // ─── Ternary ──────────────────────────────────────────────────────────────

    #[Example('ternary() wildcard — matches any $a ? $b : $c')]
    public function testTernaryWildcard(): void
    {
        $m = Ast::ternary();
        $this->assertMatches($m, ['$a ? $b : $c', 'true ? 1 : 0', '$x > 0 ? "pos" : "neg"']);
        $this->assertNotMatches($m, ['$a ?? $b', '$a', 'if($a) {}']);
    }

    #[Example('ternary with specific condition')]
    public function testTernarySpecific(): void
    {
        $m = Ast::ternary(Ast::variable('active'));
        $this->assertMatches($m, ['$active ? "yes" : "no"', '$active ? 1 : 0']);
        $this->assertNotMatches($m, ['$enabled ? "yes" : "no"']);
    }

    // ─── Cast ─────────────────────────────────────────────────────────────────

    #[Example('cast("int") — matches (int) cast expressions')]
    public function testCastInt(): void
    {
        $m = Ast::cast('int');
        $this->assertMatches($m, ['(int) $x', '(int) "42"']);
        $this->assertNotMatches($m, ['(string) $x', '(float) $x']);
    }

    #[Example('cast("string") — matches (string) cast')]
    public function testCastString(): void
    {
        $m = Ast::cast('string');
        $this->assertMatches($m, ['(string) $id', '(string) 42']);
        $this->assertNotMatches($m, ['(int) $id', '(bool) $x']);
    }

    #[Example('cast() wildcard — matches any cast')]
    public function testCastWildcard(): void
    {
        $m = Ast::cast();
        $this->assertMatches($m, ['(int) $x', '(string) $x', '(bool) $x', '(array) $x', '(object) $x']);
        $this->assertNotMatches($m, ['$x', 'intval($x)']);
    }

    // ─── Assignment ──────────────────────────────────────────────────────────

    #[Example('assign() wildcard — matches any $var = $expr')]
    public function testAssignWildcard(): void
    {
        $m = Ast::assign();
        $this->assertMatches($m, ['$x = 1', '$this->name = $name', '$arr["key"] = "val"']);
        $this->assertNotMatches($m, ['$x += 1', '$x == 1', '$x']);
    }

    #[Example('assign(variable("x")) — matches assignments to $x')]
    public function testAssignSpecific(): void
    {
        $m = Ast::assign(Ast::variable('result'));
        $this->assertMatches($m, ['$result = 0', '$result = $a + $b']);
        $this->assertNotMatches($m, ['$total = 0', '$result += 1']);
    }

    #[Example('assignOp("+=") — matches compound assignment')]
    public function testAssignOp(): void
    {
        $m = Ast::assignOp('+=');
        $this->assertMatches($m, ['$count += 1', '$total += $price']);
        $this->assertNotMatches($m, ['$count = $count + 1', '$count -= 1', '$count++']);
    }

    #[Example('assignOp() wildcard — matches any compound assignment')]
    public function testAssignOpWildcard(): void
    {
        $m = Ast::assignOp();
        $this->assertMatches($m, ['$x += 1', '$x -= 1', '$x *= 2', '$x .= " suffix"', '$x ??= "default"']);
        $this->assertNotMatches($m, ['$x = 1', '$x++']);
    }
}
