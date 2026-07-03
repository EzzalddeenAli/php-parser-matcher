<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

/**
 * End-to-end tests against realistic PHP code patterns.
 * These mirror the intent of the original MatcherExamples.php file.
 */
class RealWorldTests extends AstTestRunner
{
    #[Example('HasMany::make with __() translation call')]
    public function testHasManyMake(): void
    {
        $matcher = Ast::staticCall(
            Ast::name('HasMany'),
            Ast::name('make'),
            [
                Ast::arg(Ast::callExpression(Ast::name('__'), [Ast::arg(Ast::stringLiteral('Plan Goal Kpis'))])),
                Ast::arg(Ast::stringLiteral('planGoalKpis')),
                Ast::arg(Ast::classConstFetch(Ast::name('PlanGoalKpi'), Ast::name('class'))),
            ]
        );

        $this->assertMatches($matcher, <<<'PHP'
            HasMany::make(
                __('Plan Goal Kpis'),
                'planGoalKpis',
                PlanGoalKpi::class
            )
        PHP);
    }

    #[Example('Text::make with capture — extracts the label string')]
    public function testTextMakeCapture(): void
    {
        $nameCapture = Ast::capture(Ast::stringLiteral());

        $matcher = Ast::staticCall(
            Ast::name('Text'),
            Ast::name('make'),
            [
                Ast::arg($nameCapture),
                Ast::arg(Ast::stringLiteral('name')),
            ]
        );

        $this->assertMatches($matcher, ["Text::make('Item Name', 'name')"]);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('Text::make — label can be a bare string OR __() call')]
    public function testTextMakeLabelOrTranslated(): void
    {
        $nameCapture = Ast::capture(Ast::stringLiteral());

        $labelMatcher = Ast::or(
            $nameCapture,
            Ast::callExpression(Ast::name('__'), [Ast::arg($nameCapture)])
        );

        $matcher = Ast::staticCall(
            Ast::name('Text'),
            Ast::name('make'),
            [
                Ast::arg($labelMatcher),
                Ast::arg(Ast::stringLiteral('name')),
            ]
        );

        $this->assertMatches($matcher, [
            "Text::make(__('Item Name'), 'name')",
            "Text::make('Item Name', 'name')",
        ]);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('Text::make chained with ->rules()')]
    public function testTextMakeWithRules(): void
    {
        $nameCapture  = Ast::capture(Ast::stringLiteral());
        $labelMatcher = Ast::or(
            $nameCapture,
            Ast::callExpression(Ast::name('__'), [Ast::arg($nameCapture)])
        );

        $matcher = Ast::methodCall(
            Ast::staticCall(
                Ast::name('Text'),
                Ast::name('make'),
                [Ast::arg($labelMatcher), Ast::any()]
            ),
            Ast::name('rules'),
            [
                Ast::arg(Ast::stringLiteral('required')),
                Ast::arg(Ast::stringLiteral('max:255')),
                Ast::arg(Ast::stringLiteral('string')),
            ]
        );

        $this->assertMatches($matcher, [
            "Text::make(__('Item Name'), 'name')->rules('required', 'max:255', 'string')",
            "Text::make('Item Name', 'name')->rules('required', 'max:255', 'string')",
        ]);
    }

    #[Example('Text::make chained with ->rules() and ->placeholder()')]
    public function testTextMakeWithRulesAndPlaceholder(): void
    {
        $nameCapture  = Ast::capture(Ast::stringLiteral());
        $labelMatcher = Ast::or(
            $nameCapture,
            Ast::callExpression(Ast::name('__'), [Ast::arg($nameCapture)])
        );

        $matcher = Ast::methodCall(
            Ast::methodCall(
                Ast::staticCall(
                    Ast::name('Text'),
                    Ast::name('make'),
                    [Ast::arg($labelMatcher), Ast::arg(Ast::stringLiteral('name'))]
                ),
                Ast::name('rules'),
                [
                    Ast::arg(Ast::stringLiteral('required')),
                    Ast::arg(Ast::stringLiteral('max:255')),
                    Ast::arg(Ast::stringLiteral('string')),
                ]
            ),
            Ast::name('placeholder'),
            [Ast::arg(Ast::stringLiteral('Name'))]
        );

        $this->assertMatches($matcher, [
            "Text::make(__('Item Name'), 'name')->rules('required', 'max:255', 'string')->placeholder('Name')",
            "Text::make('Item Name', 'name')->rules('required', 'max:255', 'string')->placeholder('Name')",
        ]);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('Full class matching: MeasurementFrequency extends Resource')]
    public function testFullClassDeclaration(): void
    {
        $nameCapture  = Ast::capture(Ast::stringLiteral());
        $labelMatcher = Ast::or(
            $nameCapture,
            Ast::callExpression(Ast::name('__'), [Ast::arg($nameCapture)])
        );

        $tabMatcher = Ast::staticCall(
            Ast::name('Tab'),
            Ast::name('make'),
            [
                Ast::arg(Ast::callExpression(Ast::name('__'), [Ast::arg(Ast::stringLiteral('Measurement Frequency'))])),
                Ast::arg(Ast::arrayExpression([
                    Ast::methodCall(Ast::staticCall(Ast::name('ID'), Ast::name('make'), [Ast::arg(Ast::stringLiteral('id'))]), Ast::name('sortable')),
                    Ast::methodCall(
                        Ast::methodCall(
                            Ast::staticCall(Ast::name('Text'), Ast::name('make'), [Ast::arg(Ast::callExpression(Ast::name('__'), [Ast::arg(Ast::stringLiteral())])), Ast::arg(Ast::stringLiteral('name'))]),
                            Ast::name('rules'),
                            [Ast::arg(Ast::stringLiteral('required')), Ast::arg(Ast::stringLiteral('max:255')), Ast::arg(Ast::stringLiteral('string'))]
                        ),
                        Ast::name('placeholder'),
                        [Ast::arg(Ast::stringLiteral('Name'))]
                    ),
                    Ast::staticCall(
                        Ast::name('HasMany'),
                        Ast::name('make'),
                        [Ast::arg(Ast::callExpression(Ast::name('__'), [Ast::arg(Ast::stringLiteral('Plan Goal Kpis'))])), Ast::arg(Ast::stringLiteral('planGoalKpis')), Ast::arg(Ast::classConstFetch(Ast::name('PlanGoalKpi'), Ast::name('class')))]
                    ),
                ])),
            ]
        );

        $returnMatcher = Ast::returnStatement(
            Ast::arrayExpression([
                Ast::arrayItem(Ast::staticCall(
                    Ast::name('Tabs'),
                    Ast::name('make'),
                    [
                        Ast::arg(Ast::callExpression(Ast::name('__'), [Ast::arg(Ast::stringLiteral('Measurement Frequency'))])),
                        Ast::arg(Ast::callExpression(
                            Ast::name('array_merge'),
                            [
                                Ast::arg(Ast::arrayExpression([Ast::arrayItem($tabMatcher)])),
                                Ast::arg(Ast::binaryOp('??', Ast::propertyFetch(Ast::variable('this'), Ast::name('newFields')), Ast::array([]))),
                            ]
                        )),
                    ]
                )),
            ])
        );

        $classMatcher = Ast::classDeclaration(
            Ast::name('MeasurementFrequency'),
            Ast::name('Resource'),
            Ast::blockStatement(
                Ast::zeroOrMore(Ast::or(Ast::classProperty(), Ast::anyStatement())),
                Ast::classMethod(
                    Ast::name('fields'),
                    [Ast::param('request', 'Request')],
                    Ast::blockStatement([$returnMatcher])
                ),
                Ast::zeroOrMore(Ast::classMethod(Ast::or(
                    Ast::name('cards'),
                    Ast::name('filters'),
                    Ast::name('lenses'),
                    Ast::name('actions'),
                )))
            )
        );

        $this->assertMatches($classMatcher, <<<'PHP'
<?php
class MeasurementFrequency extends Resource
{
    use Abcd;
    public static $model = \App\Models\MeasurementFrequency::class;
    public static $title = 'name';
    public static $search = ['name'];
    public $newFields = [];

    public function fields(Request $request)
    {
        return [
            Tabs::make(
                __('Measurement Frequency'),
                array_merge(
                    [
                        Tab::make(__('Measurement Frequency'), [
                            ID::make('id')->sortable(),

                            Text::make(__('Item Name'), 'name')
                                ->rules('required', 'max:255', 'string')
                                ->placeholder('Name'),

                            HasMany::make(
                                __('Plan Goal Kpis'),
                                'planGoalKpis',
                                PlanGoalKpi::class
                            ),
                        ]),
                    ],
                    $this->newFields ?? []
                )
            ),
        ];
    }

    public function cards(Request $request) { return []; }
    public function filters(Request $request) { return []; }
    public function lenses(Request $request) { return []; }
    public function actions(Request $request) { return []; }
}
PHP);
    }

    #[Example('containerOf — finds abort() call deeply nested in an if block')]
    public function testContainerOfDeepSearch(): void
    {
        $m = Ast::containerOf(Ast::callExpression(Ast::name('abort')));

        $this->assertMatches($m, 'if ($user->can("update")) { } else { abort(403); }');
        $this->assertNotMatches($m, 'if ($user->can("update")) { } else { throw new Exception(); }');
    }

    #[Example('fromCapture — matches patterns where a variable appears twice (e.g. self-comparison)')]
    public function testSelfComparison(): void
    {
        $cap = Ast::capture(Ast::variable());
        $m   = Ast::binaryOp('===', $cap, Ast::fromCapture($cap));

        $this->assertMatches($m, ['$x === $x', '$foo === $foo']);
        $this->assertNotMatches($m, ['$x === $y', '$a === $b']);
    }
}
