<?php
/**
 * Suite 14 — ChainCallMatcher
 *
 * Demonstrates matching full method chains as a unit using Ast::chain().
 * Unlike Ast::staticCall() / Ast::methodCall() which match a single node,
 * Ast::chain() inspects the root AND any ->chained() calls together.
 *
 * Run via:  php examples/run.php --suite=14
 */

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class ChainCallMatcherExamples extends AstTestRunner
{
    // ─── 1. rootClass + rootMethod ────────────────────────────────────────────

    #[Example('matches exact root class and method')]
    public function matchRootClassMethod(): void
    {
        $m = Ast::chain()->rootClass('Text')->rootMethod('make');
        $this->assertMatches($m, "Text::make('Name', 'name')");
        $this->assertMatches($m, "Text::make('Email', 'email')->rules('required')->sortable()");
        $this->assertNotMatches($m, "ID::make('id')");
        $this->assertNotMatches($m, "Text::create('Name', 'name')");
    }

    #[Example('rootClass with Matcher — flexible class matching')]
    public function matchRootClassWithMatcher(): void
    {
        $m = Ast::chain()->rootClass(Ast::or(Ast::name('Text'), Ast::name('Textarea')));
        $this->assertMatches($m, "Text::make('Name', 'name')");
        $this->assertMatches($m, "Textarea::make('Bio', 'bio')");
        $this->assertNotMatches($m, "ID::make('id')");
    }

    #[Example('rootClassIn — shorthand for multiple class names')]
    public function matchRootClassIn(): void
    {
        $m = Ast::chain()
            ->rootClassIn(['Text', 'ID', 'Textarea', 'Select'])
            ->rootMethod('make');
        $this->assertMatches($m, "Text::make('Name', 'name')");
        $this->assertMatches($m, "ID::make('id')");
        $this->assertMatches($m, "Select::make('Status', 'status')->options(['a'=>'A'])");
        $this->assertNotMatches($m, "BelongsTo::make('User', 'user', User::class)");
    }

    // ─── 2. hasCall ───────────────────────────────────────────────────────────

    #[Example('hasCall — chain must contain a specific call')]
    public function matchHasCall(): void
    {
        $m = Ast::chain()->rootMethod('make')->hasCall('sortable');
        $this->assertMatches($m, "Text::make('Name', 'name')->sortable()");
        $this->assertMatches($m, "Text::make('Name', 'name')->rules('required')->sortable()->nullable()");
        $this->assertNotMatches($m, "Text::make('Name', 'name')->rules('required')");
    }

    #[Example('multiple hasCall — all must exist')]
    public function matchMultipleHasCall(): void
    {
        $m = Ast::chain()->rootMethod('make')->hasCall('sortable')->hasCall('nullable');
        $this->assertMatches($m, "Text::make('Name','name')->sortable()->nullable()");
        $this->assertMatches($m, "Text::make('Name','name')->nullable()->rules('x')->sortable()");
        $this->assertNotMatches($m, "Text::make('Name','name')->sortable()");
        $this->assertNotMatches($m, "Text::make('Name','name')->nullable()");
    }

    // ─── 3. lacksCall ─────────────────────────────────────────────────────────

    #[Example('lacksCall — chain must NOT contain a specific call')]
    public function matchLacksCall(): void
    {
        $m = Ast::chain()->rootMethod('make')->lacksCall('hideFromIndex');
        $this->assertMatches($m, "Text::make('Name', 'name')->sortable()");
        $this->assertNotMatches($m, "Text::make('Name', 'name')->hideFromIndex()");
    }

    #[Example('hasCall + lacksCall together')]
    public function matchHasAndLacks(): void
    {
        $m = Ast::chain()
            ->rootClass('Text')
            ->rootMethod('make')
            ->hasCall('rules')
            ->lacksCall('readonly');
        $this->assertMatches($m, "Text::make('Name','name')->rules('required')");
        $this->assertNotMatches($m, "Text::make('Name','name')->rules('required')->readonly()");
        $this->assertNotMatches($m, "Text::make('Name','name')->nullable()");
    }

    // ─── 4. hasAnyCall ────────────────────────────────────────────────────────

    #[Example('hasAnyCall — at least one from a list')]
    public function matchHasAnyCall(): void
    {
        $m = Ast::chain()
            ->rootIsStaticCall()
            ->hasAnyCall(['get', 'first', 'paginate', 'count', 'exists']);
        $this->assertMatches($m, "User::where('active', 1)->get()");
        $this->assertMatches($m, "Post::published()->orderBy('date')->paginate(15)");
        $this->assertMatches($m, "Order::where('user_id', \$id)->count()");
        $this->assertNotMatches($m, "User::where('active', 1)->orderBy('name')");
    }

    // ─── 5. rootArgs ──────────────────────────────────────────────────────────

    #[Example('rootArgs — match the root call args')]
    public function matchRootArgs(): void
    {
        $m = Ast::chain()
            ->rootClass('Text')
            ->rootMethod('make')
            ->rootArgs([Ast::arg(Ast::string('Name')), Ast::arg(Ast::string('name'))]);
        $this->assertMatches($m, "Text::make('Name', 'name')");
        $this->assertMatches($m, "Text::make('Name', 'name')->sortable()");
        $this->assertNotMatches($m, "Text::make('Email', 'email')");
        $this->assertNotMatches($m, "Text::make('Name', 'other_col')");
    }

    #[Example('rootArgs with wildcard — only match the label')]
    public function matchRootArgsPartial(): void
    {
        $m = Ast::chain()
            ->rootClass('Text')
            ->rootArgs([Ast::arg(Ast::string('Name')), Ast::arg()]);
        $this->assertMatches($m, "Text::make('Name', 'name')");
        $this->assertMatches($m, "Text::make('Name', 'anything')");
        $this->assertNotMatches($m, "Text::make('Other', 'name')");
    }

    // ─── 6. callArgs ─────────────────────────────────────────────────────────

    #[Example('callArgs — match args of a specific chain call')]
    public function matchCallArgs(): void
    {
        $m = Ast::chain()
            ->rootMethod('make')
            ->callArgs('rules', Ast::anyList(Ast::arg(Ast::string('required')), Ast::zeroOrMore()));
        $this->assertMatches($m, "Text::make('Name','name')->rules('required')");
        $this->assertMatches($m, "Text::make('Name','name')->rules('required','max:255')");
        $this->assertNotMatches($m, "Text::make('Name','name')->rules('nullable')");
        $this->assertNotMatches($m, "Text::make('Name','name')->sortable()");
    }

    #[Example('callArgs implies the call must exist')]
    public function matchCallArgsImpliesExists(): void
    {
        $m = Ast::chain()
            ->rootMethod('make')
            ->callArgs('placeholder', [Ast::arg(Ast::string())]);
        $this->assertMatches($m, "Text::make('Name','name')->placeholder('Enter name')");
        $this->assertNotMatches($m, "Text::make('Name','name')->sortable()");
    }

    // ─── 7. chainLength ───────────────────────────────────────────────────────

    #[Example('chainLength — bare call has 0 chain calls')]
    public function matchChainLengthZero(): void
    {
        $m = Ast::chain()->rootClass('ID')->chainLength(0, 0);
        $this->assertMatches($m, "ID::make('id')");
        $this->assertNotMatches($m, "ID::make('id')->sortable()");
    }

    #[Example('chainLength — at least 2 chain calls')]
    public function matchChainLengthMin(): void
    {
        $m = Ast::chain()->rootMethod('make')->chainLength(2);
        $this->assertMatches($m, "Text::make('Name','name')->rules('required')->sortable()");
        $this->assertMatches($m, "Text::make('Name','name')->a()->b()->c()");
        $this->assertNotMatches($m, "Text::make('Name','name')->rules('required')");
        $this->assertNotMatches($m, "Text::make('Name','name')");
    }

    // ─── 8. rootIsStaticCall ─────────────────────────────────────────────────

    #[Example('rootIsStaticCall — excludes method call chains')]
    public function matchRootIsStaticCall(): void
    {
        $m = Ast::chain()->rootIsStaticCall()->rootMethod('make');
        $this->assertMatches($m, "Text::make('Name', 'name')");
        $this->assertMatches($m, "Text::make('Name', 'name')->sortable()");
        $this->assertNotMatches($m, "\$obj->make('Name', 'name')");
    }

    // ─── 9. Integration with capture ─────────────────────────────────────────

    #[Example('chain works inside capture() — captures outermost node')]
    public function matchWithCapture(): void
    {
        $cap = Ast::capture(
            Ast::chain()->rootClass('Text')->rootMethod('make')->hasCall('sortable')
        );
        $node = $this->parseExpression("Text::make('Name','name')->rules('required')->sortable()");
        $this->assertTrue($cap->matchValue($node));
        $this->assertNotNull($cap->first());
    }

    #[Example('chain inside containerOf — finds in nested context')]
    public function matchInsideContainerOf(): void
    {
        $m = Ast::containerOf(
            Ast::chain()
                ->rootClassIn(['Text', 'ID'])
                ->rootMethod('make')
                ->hasCall('sortable')
        );
        $code = <<<'PHP'
            return [
                ID::make('id'),
                Text::make('Name', 'name')->rules('required')->sortable(),
                BelongsTo::make('User', 'user', User::class),
            ];
        PHP;
        $this->assertMatches($m, $code);
    }

    // ─── 10. Composed with other matchers ────────────────────────────────────

    #[Example('chain inside return() — find a field in a return statement')]
    public function matchInsideReturnStatement(): void
    {
        $m = Ast::return(
            Ast::arrayExpression([
                Ast::arrayItem(
                    Ast::chain()->rootClass('Text')->hasCall('sortable')
                ),
            ])
        );
        $code = "return [Text::make('Name','name')->sortable()];";
        $this->assertMatches($m, $code);
        $this->assertNotMatches($m, "return [Text::make('Name','name')];");
    }

    #[Example('Ast::or() of two chain() matchers')]
    public function matchOrOfChains(): void
    {
        $m = Ast::or(
            Ast::chain()->rootClass('Text')->hasCall('sortable'),
            Ast::chain()->rootClass('ID')
        );
        $this->assertMatches($m, "Text::make('Name','name')->sortable()");
        $this->assertMatches($m, "ID::make('id')");
        $this->assertNotMatches($m, "Text::make('Name','name')");
        $this->assertNotMatches($m, "BelongsTo::make('User','user',User::class)");
    }
}

ChainCallMatcherExamples::run();
