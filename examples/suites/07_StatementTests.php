<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class StatementTests extends AstTestRunner
{
    // ─── Return ───────────────────────────────────────────────────────────────

    #[Example('return() wildcard — matches any return statement')]
    public function testReturnWildcard(): void
    {
        $m = Ast::return();
        $this->assertMatches($m, ['return', 'return $x', 'return true', 'return null', 'return foo()']);
        $this->assertNotMatches($m, ['$x', 'echo $x']);
    }

    #[Example('return(stringLiteral) — matches return "literal"')]
    public function testReturnString(): void
    {
        $m = Ast::return(Ast::stringLiteral('ok'));
        $this->assertMatches($m, ['return "ok"', "return 'ok'"]);
        $this->assertNotMatches($m, ['return "error"', 'return $ok']);
    }

    #[Example('returnStatement() is an alias for return()')]
    public function testReturnAlias(): void
    {
        $m = Ast::returnStatement(Ast::null());
        $this->assertMatches($m, ['return null', 'return NULL']);
        $this->assertNotMatches($m, ['return false', 'return']);
    }

    #[Example('return(callExpression) — matches return value of a function call')]
    public function testReturnCall(): void
    {
        $m = Ast::return(Ast::callExpression(Ast::name('response')));
        $this->assertMatches($m, ['return response()', 'return response($data)']);
        $this->assertNotMatches($m, ['return $response', 'return json_encode($data)']);
    }

    // ─── Echo ─────────────────────────────────────────────────────────────────

    #[Example('echo() wildcard — matches any echo statement')]
    public function testEchoWildcard(): void
    {
        $m = Ast::echo();
        $this->assertMatches($m, ['echo "hello"', 'echo $x', 'echo $a, $b, $c']);
        $this->assertNotMatches($m, ['print("hello")', '$x']);
    }

    #[Example('echo(tupleOf) — matches echo "specific string" (exprs is a list)')]
    public function testEchoSpecific(): void
    {
        // echo's $exprs is an array — use a collection matcher
        $m = Ast::echo(Ast::tupleOf(Ast::stringLiteral('hello')));
        $this->assertMatches($m, ['echo "hello"', "echo 'hello'"]);
        $this->assertNotMatches($m, ['echo "world"', 'echo $msg']);
    }

    // ─── Break & Continue ─────────────────────────────────────────────────────

    #[Example('break() — matches any break statement')]
    public function testBreakWildcard(): void
    {
        $m = Ast::break();
        $this->assertMatches($m, ['break', 'break 2', 'break 3']);
        $this->assertNotMatches($m, ['continue', 'return', '$x']);
    }

    #[Example('continue() — matches any continue statement')]
    public function testContinueWildcard(): void
    {
        $m = Ast::continue();
        $this->assertMatches($m, ['continue', 'continue 2']);
        $this->assertNotMatches($m, ['break', 'return']);
    }

    // ─── Expression Statement ─────────────────────────────────────────────────

    #[Example('statement(callExpression) — matches Stmt\\Expression wrapping a call')]
    public function testExpressionStatement(): void
    {
        // statement() matches Stmt\Expression nodes (the wrapper).
        // parseSingleCode() unwraps that, so we test via parseStatement() directly.
        $m    = Ast::statement(Ast::callExpression(Ast::name('abort')));
        $node = static::parseStatement('abort(403);');
        $this->assertTrue($m->match($node));

        $node2 = static::parseStatement('abort(404, "not found");');
        $this->assertTrue($m->match($node2));

        // return statement is NOT a Stmt\Expression
        $node3 = static::parseStatement('return abort(403);');
        $this->assertFalse($m->match($node3));
    }

    #[Example('expressionStatement() is an alias for statement()')]
    public function testExpressionStatementAlias(): void
    {
        $m    = Ast::expressionStatement(Ast::methodCall(null, Ast::name('save')));
        $node = static::parseStatement('$model->save();');
        $this->assertTrue($m->match($node));

        $node2 = static::parseStatement('$result = $model->save();');
        $this->assertFalse($m->match($node2));
    }
}
