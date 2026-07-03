<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Facade\Build;
use Fleet\AstMatcher\Printer\MatcherPrinter;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class PrinterBuildTests extends AstTestRunner
{
    // ─── MatcherPrinter ───────────────────────────────────────────────────────

    #[Example('MatcherPrinter: string literal → Ast::stringLiteral()')]
    public function testPrinterStringLiteral(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('"hello"');
        $this->assertStringNotEmpty($out);
        $this->assertTrue(str_contains($out, "Ast::stringLiteral('hello')"));
    }

    #[Example('MatcherPrinter: integer literal → Ast::numberLiteral()')]
    public function testPrinterNumberLiteral(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('42');
        $this->assertTrue(str_contains($out, 'Ast::numberLiteral(42)'));
    }

    #[Example('MatcherPrinter: variable → Ast::variable()')]
    public function testPrinterVariable(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('$foo');
        $this->assertTrue(str_contains($out, "Ast::variable('foo')"));
    }

    #[Example('MatcherPrinter: simple function call → Ast::callExpression()')]
    public function testPrinterFunctionCall(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('abort(403)');
        $this->assertTrue(str_contains($out, 'Ast::callExpression('));
        $this->assertTrue(str_contains($out, "Ast::name('abort')"));
        $this->assertTrue(str_contains($out, 'Ast::numberLiteral(403)'));
    }

    #[Example('MatcherPrinter: static call → Ast::staticCall()')]
    public function testPrinterStaticCall(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode("DB::table('users')");
        $this->assertTrue(str_contains($out, 'Ast::staticCall('));
        $this->assertTrue(str_contains($out, "Ast::name('DB')"));
        $this->assertTrue(str_contains($out, "Ast::name('table')"));
        $this->assertTrue(str_contains($out, "Ast::stringLiteral('users')"));
    }

    #[Example('MatcherPrinter: method call → Ast::methodCall()')]
    public function testPrinterMethodCall(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('$query->where("active", true)');
        $this->assertTrue(str_contains($out, 'Ast::methodCall('));
        $this->assertTrue(str_contains($out, "Ast::variable('query')"));
        $this->assertTrue(str_contains($out, "Ast::name('where')"));
    }

    #[Example('MatcherPrinter: property fetch → Ast::propertyFetch()')]
    public function testPrinterPropertyFetch(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('$this->name');
        $this->assertTrue(str_contains($out, 'Ast::propertyFetch('));
        $this->assertTrue(str_contains($out, "Ast::variable('this')"));
        $this->assertTrue(str_contains($out, "Ast::name('name')"));
    }

    #[Example('MatcherPrinter: class const fetch → Ast::classConstFetch()')]
    public function testPrinterClassConstFetch(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('Status::ACTIVE');
        $this->assertTrue(str_contains($out, 'Ast::classConstFetch('));
        $this->assertTrue(str_contains($out, "Ast::name('Status')"));
        $this->assertTrue(str_contains($out, "Ast::name('ACTIVE')"));
    }

    #[Example('MatcherPrinter: binary op → Ast::binaryOp()')]
    public function testPrinterBinaryOp(): void
    {
        $printer = new MatcherPrinter();
        $out     = $printer->printCode('$a + $b');
        $this->assertTrue(str_contains($out, 'Ast::binaryOp('));
        $this->assertTrue(str_contains($out, "'+'" ));
    }

    #[Example('MatcherPrinter: facade=function mode uses global function names')]
    public function testPrinterFunctionMode(): void
    {
        $printer = new MatcherPrinter(facade: 'function');
        $out     = $printer->printCode('abort(403)');
        $this->assertTrue(str_contains($out, 'callExpression('));
        $this->assertFalse(str_contains($out, 'Ast::'));
    }

    // ─── Build facade ─────────────────────────────────────────────────────────

    #[Example('Build::string() creates a scalar string node')]
    public function testBuildString(): void
    {
        $node = Build::string('hello');
        $this->assertIsInstanceOf($node, \PhpParser\Node\Scalar\String_::class);
        $this->assertTrue($node->value === 'hello');
    }

    #[Example('Build::int() creates an integer scalar node')]
    public function testBuildInt(): void
    {
        $node = Build::int(42);
        // PHP-Parser 5 uses LNumber or Int_ depending on version
        $this->assertTrue($node instanceof \PhpParser\Node\Scalar\LNumber || $node instanceof \PhpParser\Node\Scalar\Int_);
        $this->assertTrue($node->value === 42);
    }

    #[Example('Build::variable() creates a variable node')]
    public function testBuildVariable(): void
    {
        $node = Build::variable('user');
        $this->assertIsInstanceOf($node, \PhpParser\Node\Expr\Variable::class);
        $this->assertTrue($node->name === 'user');
    }

    #[Example('Build::staticCall() creates a static method call node')]
    public function testBuildStaticCall(): void
    {
        $node = Build::staticCall('DB', 'table', [Build::arg(Build::string('users'))]);
        $out  = Build::print($node);
        $this->assertTrue($out === "DB::table('users')");
    }

    #[Example('Build::methodCall() creates a method call node')]
    public function testBuildMethodCall(): void
    {
        $node = Build::methodCall(Build::variable('query'), 'where', [
            Build::arg(Build::string('active')),
            Build::arg(Build::true()),
        ]);
        $out = Build::print($node);
        $this->assertTrue($out === '$query->where(\'active\', true)');
    }

    #[Example('Build::callExpression() → print() roundtrip')]
    public function testBuildCallRoundtrip(): void
    {
        $node = Build::callExpression('abort', [Build::arg(Build::int(403))]);
        $out  = Build::print($node);
        $this->assertTrue($out === 'abort(403)');
    }

    #[Example('Build::binaryOp() creates a binary operation node')]
    public function testBuildBinaryOp(): void
    {
        $node = Build::binaryOp('??', Build::variable('x'), Build::string('default'));
        $out  = Build::print($node);
        $this->assertTrue($out === "\$x ?? 'default'");
    }

    #[Example('Build::new() creates an object instantiation node')]
    public function testBuildNew(): void
    {
        $node = Build::new('Exception', [Build::arg(Build::string('not found'))]);
        $out  = Build::print($node);
        $this->assertTrue($out === "new Exception('not found')");
    }

    #[Example('Build → MatcherPrinter roundtrip: build node then convert to matcher code')]
    public function testBuildPrinterRoundtrip(): void
    {
        // Build the PHP node
        $node = Build::staticCall('Text', 'make', [
            Build::arg(Build::string('Name')),
            Build::arg(Build::string('name')),
        ]);

        // Confirm the node prints to expected PHP
        $phpCode = Build::print($node);
        $this->assertTrue($phpCode === "Text::make('Name', 'name')");

        // Convert that node to a matcher pattern
        $printer = new MatcherPrinter();
        $pattern = $printer->printNode($node);
        $this->assertStringNotEmpty($pattern);
        $this->assertTrue(str_contains($pattern, 'Ast::staticCall('));

        // Evaluate the pattern and verify it matches
        $matcher = eval('use Fleet\\AstMatcher\\Facade\\Ast; return ' . $pattern . ';');
        $matchNode = static::parseExpression($phpCode);
        $this->assertTrue($matcher->match($matchNode));
    }
}
