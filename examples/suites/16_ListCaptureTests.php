<?php
/**
 * Suite 16 — List Capture (captureList)
 *
 * captureList() is a variant of capture() designed for slice matchers
 * (zeroOrMore / oneOrMore / spacer).  Instead of recording each element
 * individually as it is visited, it waits until the full AnyListMatcher
 * distribution succeeds and then stores the entire matched element array
 * as a single capture entry.
 *
 *   capture()     → captures each node separately; all() gives [n1, n2, n3]
 *   captureList() → captures the whole slice at once; get() gives [n1, n2, n3]
 *
 * Run via:  php examples/run.php --suite=16
 */

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;

class ListCaptureTests extends AstTestRunner
{
    // ─── Basic captureList() ─────────────────────────────────────────────────

    #[Example('captureList() — stores all matched args as a single array')]
    public function testCaptureListBasic(): void
    {
        $m = Ast::callExpression(
            Ast::name('route'),
            Ast::anyList(
                Ast::zeroOrMore(Ast::arg())->captureList('args')
            )
        );

        $this->assertTrue(Ast::match($m, static::parseExpression("route('home', 'GET', 'web')")));

        $args = Ast::globalCaptures()->get('args');
        $this->assertTrue(is_array($args));
        $this->assertTrue(count($args) === 3);
        $this->assertIsInstanceOf($args[0], Arg::class);
    }

    #[Example('captureList() first arg fixed + rest captured')]
    public function testCaptureListPartial(): void
    {
        $m = Ast::callExpression(
            Ast::name('query'),
            Ast::anyList(
                Ast::arg(Ast::string()),                                        // first arg fixed
                Ast::zeroOrMore(Ast::arg(Ast::string()))->captureList('rest')  // remaining
            )
        );

        Ast::match($m, static::parseExpression("query('table', 'where1', 'where2')"));

        $rest = Ast::globalCaptures()->get('rest');
        $this->assertTrue(is_array($rest));
        $this->assertTrue(count($rest) === 2);
        $values = array_map(fn($arg) => $arg->value->value, $rest);
        $this->assertTrue($values === ['where1', 'where2']);
    }

    #[Example('captureList() with oneOrMore — at least one element required')]
    public function testCaptureListOneOrMore(): void
    {
        $m = Ast::callExpression(
            Ast::name('log'),
            Ast::anyList(Ast::oneOrMore(Ast::arg())->captureList('messages'))
        );

        $this->assertTrue(Ast::match($m, static::parseExpression("log('a', 'b')")));
        $msgs = Ast::globalCaptures()->get('messages');
        $this->assertTrue(count($msgs) === 2);

        // zeroOrMore with empty — empty array captured
        $m2 = Ast::callExpression(
            Ast::name('log'),
            Ast::anyList(Ast::zeroOrMore(Ast::arg())->captureList('messages2'))
        );
        Ast::match($m2, static::parseExpression("log()"));
        $empty = Ast::globalCaptures()->get('messages2');
        $this->assertTrue(is_array($empty));
        $this->assertTrue(count($empty) === 0);
    }

    // ─── Two captureList() slots in one pattern ───────────────────────────────

    #[Example('two captureList() slots — each collects its own portion')]
    public function testTwoCaptureListSlots(): void
    {
        $m = Ast::callExpression(
            Ast::name('migrate'),
            Ast::anyList(
                Ast::zeroOrMore(Ast::arg(Ast::string()))->captureList('strings'),
                Ast::zeroOrMore(Ast::arg(Ast::number()))->captureList('numbers')
            )
        );

        Ast::match($m, static::parseExpression("migrate('up', 'down', 1, 2, 3)"));

        $strings = Ast::globalCaptures()->get('strings');
        $numbers = Ast::globalCaptures()->get('numbers');

        $this->assertTrue(count($strings) === 2);
        $this->assertTrue(count($numbers) === 3);

        $strVals = array_map(fn($a) => $a->value->value, $strings);
        $this->assertTrue($strVals === ['up', 'down']);

        $numVals = array_map(fn($a) => $a->value->value, $numbers);
        $this->assertTrue($numVals === [1, 2, 3]);
    }

    // ─── captureList() with chain() ──────────────────────────────────────────

    #[Example('captureList() on chain() — typical Nova fields use case')]
    public function testCaptureListChain(): void
    {
        // arrayExpression(Matcher) mode: values are unwrapped from ArrayItem,
        // so the list receives chain nodes directly (StaticCall / MethodCall).
        $m = Ast::containerOf(
            Ast::arrayExpression(
                Ast::anyList(
                    Ast::zeroOrMore(Ast::chain()->rootIsStaticCall())->captureList('fields')
                )
            )
        );

        $code = <<<'PHP'
            return [
                Text::make('Name', 'name')->sortable(),
                ID::make('id'),
                BelongsTo::make('User', 'user', User::class),
            ];
        PHP;

        $this->assertTrue(Ast::match($m, static::parseStatement($code)));

        $fields = Ast::globalCaptures()->get('fields');
        $this->assertTrue(is_array($fields));
        $this->assertTrue(count($fields) === 3);

        // Each value is a chain node (StaticCall or MethodCall)
        foreach ($fields as $node) {
            $isChain = $node instanceof StaticCall || $node instanceof MethodCall;
            $this->assertTrue($isChain);
        }
    }

    #[Example('captureList() — only Text::make() fields out of a mixed array')]
    public function testCaptureListFilteredChain(): void
    {
        $m = Ast::containerOf(
            Ast::arrayExpression(
                Ast::anyList(
                    Ast::zeroOrMore(
                        Ast::chain()->rootClass('Text')->rootMethod('make')
                    )->captureList('textFields'),
                    Ast::zeroOrMore()  // ignore the rest
                )
            )
        );

        // Text::make() items must be contiguous at the front for zeroOrMore to capture them.
        $code = <<<'PHP'
            return [
                Text::make('Name', 'name'),
                Text::make('Email', 'email'),
                ID::make('id'),
            ];
        PHP;

        $this->assertTrue(Ast::match($m, static::parseStatement($code)));

        $textFields = Ast::globalCaptures()->get('textFields');
        $this->assertTrue(count($textFields) === 2);
    }

    // ─── captureList() with explicit CaptureGroup ─────────────────────────────

    #[Example('captureList() with an explicit CaptureGroup')]
    public function testCaptureListExplicitGroup(): void
    {
        $group = Ast::captures();

        $m = Ast::callExpression(
            Ast::name('select'),
            Ast::anyList(
                Ast::zeroOrMore(Ast::arg(Ast::string()))->captureList('cols', $group)
            )
        );

        $m->match(static::parseExpression("select('id', 'name', 'email')"));

        $cols = $group->get('cols');
        $this->assertTrue(count($cols) === 3);
        $colNames = array_map(fn($a) => $a->value->value, $cols);
        $this->assertTrue($colNames === ['id', 'name', 'email']);

        // Global group must NOT be affected
        $this->assertFalse(Ast::globalCaptures()->has('cols'));
    }

    // ─── Reuse across calls (Ast::match auto-reset) ───────────────────────────

    #[Example('captureList() resets cleanly between Ast::match() calls')]
    public function testCaptureListReset(): void
    {
        $m = Ast::callExpression(
            Ast::name('route'),
            Ast::anyList(Ast::zeroOrMore(Ast::arg())->captureList('args'))
        );

        Ast::match($m, static::parseExpression("route('a', 'b', 'c')"));
        $this->assertTrue(count(Ast::globalCaptures()->get('args')) === 3);

        // Second call — auto-reset, fresh data
        Ast::match($m, static::parseExpression("route('x', 'y')"));
        $this->assertTrue(count(Ast::globalCaptures()->get('args')) === 2);

        // getCaptured() gives direct access to the CapturedMatcher
        $capMatcher = Ast::globalCaptures()->matcher('args');
        $this->assertTrue($capMatcher->count() === 1);    // 1 capture entry (the array itself)
        $this->assertTrue(count($capMatcher->first()) === 2); // the entry IS a 2-element array
    }
}

ListCaptureTests::run();
