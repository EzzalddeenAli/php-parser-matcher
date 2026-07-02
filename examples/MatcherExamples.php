<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

include __DIR__ . '/../vendor/autoload.php';

trait ControlFlowExamples
{
    #[Example('if_: مضاهاة if بسيط')]
    public function exampleIf(): void
    {
        $matcher = Ast::if(Ast::variable('flag'));
        $this->assertMatches($matcher, 'if ($flag) {}');
        $this->assertNotMatches($matcher, 'while ($flag) {}');
    }

    #[Example('if_: مضاهاة if مع else')]
    public function exampleIfElse(): void
    {
        $matcher = Ast::if(
            Ast::variable('x'),
            null,
            null,
            Ast::else()
        );
        $this->assertMatches($matcher, 'if ($x) { foo(); } else { bar(); }');
        $this->assertNotMatches($matcher, 'if ($x) { foo(); }');
    }

    #[Example('if_: مضاهاة if مع elseif')]
    public function exampleIfElseIf(): void
    {
        $elseifs = Ast::anyList(Ast::elseIf(Ast::variable('y')));
        $matcher = Ast::if(Ast::variable('x'), null, $elseifs);
        $this->assertMatches($matcher, 'if ($x) {} elseif ($y) {}');
        $this->assertNotMatches($matcher, 'if ($x) {}');
    }

    #[Example('foreach_: مضاهاة foreach (null keyVar = wildcard يطابق مع وبدون key)')]
    public function exampleForeach(): void
    {
        $matcher = Ast::foreach(
            Ast::variable('items'),
            Ast::variable('item')
        );
        // null keyVar = wildcard — يطابق مع أو بدون key
        $this->assertMatches($matcher, 'foreach ($items as $item) {}');
        $this->assertMatches($matcher, 'foreach ($items as $k => $item) {}');
        // خطأ في الـ valueVar يفشل
        $this->assertNotMatches($matcher, 'foreach ($items as $row) {}');
    }

    #[Example('foreach_: مضاهاة foreach مع key')]
    public function exampleForeachWithKey(): void
    {
        $matcher = Ast::foreach(
            null,
            Ast::variable('value'),
            Ast::variable('key')
        );
        $this->assertMatches($matcher, 'foreach ($items as $key => $value) {}');
        $this->assertMatches($matcher, 'foreach ($rows as $key => $value) { doSomething($key); }');
    }

    #[Example('while_: مضاهاة while loop')]
    public function exampleWhile(): void
    {
        $matcher = Ast::while(Ast::variable('running'));
        $this->assertMatches($matcher, 'while ($running) { process(); }');
        $this->assertNotMatches($matcher, 'do { process(); } while ($running);');
    }

    #[Example('doWhile: مضاهاة do-while loop')]
    public function exampleDoWhile(): void
    {
        $matcher = Ast::doWhile(null, Ast::variable('running'));
        $this->assertMatches($matcher, 'do { process(); } while ($running);');
        $this->assertNotMatches($matcher, 'while ($running) { process(); }');
    }

    #[Example('for_: مضاهاة for loop')]
    public function exampleFor(): void
    {
        $matcher = Ast::for();
        $this->assertMatches($matcher, 'for ($i = 0; $i < 10; $i++) {}');
        $this->assertNotMatches($matcher, 'foreach ($a as $b) {}');
    }

    #[Example('tryCatch: مضاهاة try-catch')]
    public function exampleTryCatch(): void
    {
        $matcher = Ast::tryCatch(
            null,
            Ast::anyList(Ast::catch())
        );
        $this->assertMatches($matcher, 'try { foo(); } catch (Exception $e) { bar(); }');
        $this->assertNotMatches($matcher, 'try { foo(); } finally { bar(); }');
    }

    #[Example('tryCatch: مضاهاة try-catch مع finally')]
    public function exampleTryCatchFinally(): void
    {
        $matcher = Ast::tryCatch(
            null,
            null,
            Ast::finally()
        );
        $this->assertMatches($matcher, 'try { foo(); } finally { cleanup(); }');
        $this->assertMatches($matcher, 'try { foo(); } catch (Exception $e) {} finally { cleanup(); }');
    }

    #[Example('catch_: مضاهاة catch مع نوع محدد')]
    public function exampleCatch(): void
    {
        $matcher = Ast::tryCatch(
            null,
            Ast::anyList(
                Ast::catch(null, Ast::variable('e'))
            )
        );
        $this->assertMatches($matcher, 'try { foo(); } catch (RuntimeException $e) { handle($e); }');
    }

    #[Example('switch_: مضاهاة switch')]
    public function exampleSwitch(): void
    {
        $matcher = Ast::switch(Ast::variable('status'));
        $this->assertMatches($matcher, 'switch ($status) { case 1: break; case 2: break; }');
        $this->assertNotMatches($matcher, 'match ($status) { 1 => foo(), 2 => bar() }');
    }

    #[Example('switch_: مضاهاة switch مع case محدد')]
    public function exampleSwitchWithCase(): void
    {
        $matcher = Ast::switch(
            null,
            Ast::anyList(
                Ast::zeroOrMore(Ast::case()),
                Ast::case(Ast::stringLiteral('active'))
            )
        );
        $this->assertMatches($matcher, "switch (\$s) { case 'active': echo 'yes'; break; }");
        $this->assertMatches($matcher, "switch (\$s) { case 'pending': break; case 'active': echo 'yes'; break; }");
    }

    #[Example('echo_: مضاهاة echo statement')]
    public function exampleEcho(): void
    {
        $matcher = Ast::echo();
        $this->assertMatches($matcher, 'echo "hello";');
        $this->assertMatches($matcher, 'echo $a, $b;');
        $this->assertNotMatches($matcher, 'print "hello";');
    }

    #[Example('break_: مضاهاة break statement')]
    public function exampleBreak(): void
    {
        $matcher = Ast::break();
        $this->assertMatches($matcher, 'break;');
        $this->assertMatches($matcher, 'break 2;');
        $this->assertNotMatches($matcher, 'continue;');
    }

    #[Example('continue_: مضاهاة continue statement')]
    public function exampleContinue(): void
    {
        $matcher = Ast::continue();
        $this->assertMatches($matcher, 'continue;');
        $this->assertNotMatches($matcher, 'break;');
    }
}
class MatcherExamples extends AstTestRunner
{
    use ControlFlowExamples;
    #[Example('مضاهاة دالة HasMany::make مع استخدام الترجمة __()')]
    public function exampleHasManyNode(): void
    {
        $matcher = Ast::staticCall(
            Ast::name("HasMany"),
            Ast::name("make"),
            [
                Ast::arg(Ast::callExpression(
                    Ast::name("__"),
                    [Ast::arg(Ast::stringLiteral("Plan Goal Kpis"))]
                )),
                Ast::arg(Ast::stringLiteral("planGoalKpis")),
                Ast::arg(Ast::classConstFetch(
                    Ast::name("PlanGoalKpi"),
                    Ast::name("class")
                )),
            ]
        );

        $code = <<<'PHP'
        HasMany::make(
            __('Plan Goal Kpis'),
            'planGoalKpis',
            PlanGoalKpi::class
        )
        PHP;

        $this->assertMatches($matcher, $code);
    }

    #[Example('مضاهاة دالة Text::make مع التقاط الاسم — نص مباشر')]
    public function exampleTextMakeWithCapture(): void
    {
        $nameCapture = Ast::capture(Ast::stringLiteral());

        $matcher = Ast::staticCall(
            Ast::name("Text"),
            Ast::name("make"),
            [
                Ast::arg($nameCapture),
                Ast::arg(Ast::stringLiteral("name")),
            ]
        );

        $this->assertMatches($matcher, [
            <<<'PHP'
                Text::make('Item Name', 'name')
            PHP,
        ]);

        $this->assertString($nameCapture->getCurrent()->value);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('مضاهاة Text::make مع التقاط الاسم — نص مباشر أو ترجمة __()')]
    public function exampleTextMakeWithCapture2(): void
    {
        $nameCapture = Ast::capture(Ast::stringLiteral());

        $matcher = Ast::staticCall(
            Ast::name("Text"),
            Ast::name("make"),
            [
                Ast::arg(Ast::or(
                    $nameCapture,
                    Ast::callExpression(
                        Ast::name("__"),
                        [Ast::arg($nameCapture)]
                    )
                )),
                Ast::arg(Ast::stringLiteral("name")),
            ]
        );

        $this->assertMatches($matcher, [
            <<<'PHP'
                Text::make(__('Item Name'), 'name')
            PHP,
            <<<'PHP'
                Text::make('Item Name', 'name');
            PHP,
        ]);

        $this->assertString($nameCapture->getCurrent()->value);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('مضاهاة Text::make مع method chaining (rules)')]
    public function exampleTextMakeWithCapture3(): void
    {
        $nameCapture = Ast::capture(Ast::stringLiteral());

        $matcher = Ast::methodCall(
            Ast::staticCall(
                Ast::name("Text"),
                Ast::name("make"),
                [
                    Ast::arg(Ast::or(
                        $nameCapture,
                        Ast::callExpression(
                            Ast::name("__"),
                            [Ast::arg($nameCapture)]
                        )
                    )),
                    Ast::any(),
                ]
            ),
            Ast::name("rules"),
            [
                Ast::arg(Ast::stringLiteral("required")),
                Ast::arg(Ast::stringLiteral("max:255")),
                Ast::arg(Ast::stringLiteral("string")),
            ]
        );

        $this->assertMatches($matcher, [
            <<<'PHP'
                Text::make(__('Item Name'), 'name')->rules('required', 'max:255', 'string')
            PHP,
            <<<'PHP'
                Text::make('Item Name', 'name')->rules('required', 'max:255', 'string');
            PHP,
        ]);

        $this->assertString($nameCapture->getCurrent()->value);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('مضاهاة Text::make مع method chaining متعدد (rules + placeholder)')]
    public function exampleTextMakeWithCapture4(): void
    {
        $nameCapture = Ast::capture(Ast::stringLiteral());

        $matcher = Ast::methodCall(
            Ast::methodCall(
                Ast::staticCall(
                    Ast::name("Text"),
                    Ast::name("make"),
                    [
                        Ast::arg(Ast::or(
                            $nameCapture,
                            Ast::callExpression(
                                Ast::name("__"),
                                [Ast::arg($nameCapture)]
                            )
                        )),
                        Ast::arg(Ast::stringLiteral("name")),
                    ]
                ),
                Ast::name("rules"),
                [
                    Ast::arg(Ast::stringLiteral("required")),
                    Ast::arg(Ast::stringLiteral("max:255")),
                    Ast::arg(Ast::stringLiteral("string")),
                ]
            ),
            Ast::name("placeholder"),
            [Ast::arg(Ast::stringLiteral("Name"))]
        );

        $this->assertMatches($matcher, [
            <<<'PHP'
            Text::make(__('Item Name'), 'name')
                ->rules('required', 'max:255', 'string')
                ->placeholder('Name')
            PHP,
            <<<'PHP'
            Text::make('Item Name', 'name')
                ->rules('required', 'max:255', 'string')
                ->placeholder('Name')
            PHP,
        ]);

        $this->assertString($nameCapture->getCurrent()->value);
        $this->assertTrue($nameCapture->getCurrent()->value === 'Item Name');
    }

    #[Example('مضاهاة تركيب كامل: Tab::make مع array_merge وclass كامل')]
    public function exampleTextMakeWithArray(): void
    {
        $matcher = Ast::staticCall(
            Ast::name("Tab"),
            Ast::name("make"),
            [
                Ast::arg(Ast::callExpression(
                    Ast::name("__"),
                    [Ast::arg(Ast::stringLiteral("Measurement Frequency"))],
                )),
                Ast::arg(Ast::arrayExpression([
                    Ast::methodCall(
                        Ast::staticCall(
                            Ast::name("ID"),
                            Ast::name("make"),
                            [Ast::arg(Ast::stringLiteral("id"))],
                        ),
                        Ast::name("sortable"),
                    ),
                    Ast::methodCall(
                        Ast::methodCall(
                            Ast::staticCall(
                                Ast::name("Text"),
                                Ast::name("make"),
                                [
                                    Ast::arg(Ast::callExpression(
                                        Ast::name("__"),
                                        [Ast::arg(Ast::stringLiteral())]
                                    )),
                                    Ast::arg(Ast::stringLiteral("name")),
                                ]
                            ),
                            Ast::name("rules"),
                            [
                                Ast::arg(Ast::stringLiteral("required")),
                                Ast::arg(Ast::stringLiteral("max:255")),
                                Ast::arg(Ast::stringLiteral("string")),
                            ]
                        ),
                        Ast::name("placeholder"),
                        [Ast::arg(Ast::stringLiteral("Name"))]
                    ),
                    Ast::staticCall(
                        Ast::name("HasMany"),
                        Ast::name("make"),
                        [
                            Ast::arg(Ast::callExpression(
                                Ast::name("__"),
                                [Ast::arg(Ast::stringLiteral("Plan Goal Kpis"))]
                            )),
                            Ast::arg(Ast::stringLiteral("planGoalKpis")),
                            Ast::arg(Ast::classConstFetch(
                                Ast::name("PlanGoalKpi"),
                                Ast::name("class")
                            )),
                        ]
                    ),
                ])),
            ],
        );

        $this->assertMatches($matcher, [
            <<<'PHP'
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
                ]);
            PHP,
        ]);

        $mergeMatcher = Ast::callExpression(
            Ast::name("array_merge"),
            [
                Ast::arg(Ast::arrayExpression([Ast::arrayItem($matcher)])),
                Ast::arg(Ast::binaryOp(
                    "??",
                    Ast::propertyFetch(Ast::variable('this'), Ast::name("newFields")),
                    Ast::array([])
                )),
            ],
        );

        $this->assertMatches($mergeMatcher, [
            <<<'PHP'
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
            PHP,
        ]);

        $returnMatcher = Ast::returnStatement(
            Ast::arrayExpression([
                Ast::arrayItem(Ast::staticCall(
                    Ast::name("Tabs"),
                    Ast::name("make"),
                    [
                        Ast::arg(Ast::callExpression(Ast::name("__"), [
                            Ast::arg(Ast::stringLiteral("Measurement Frequency")),
                        ])),
                        Ast::arg($mergeMatcher),
                    ],
                )),
            ]),
        );

        $this->assertMatches($returnMatcher, <<<'TXT'
<?php
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
TXT
        );

        $classMatcher = Ast::classDeclaration(
            Ast::name("MeasurementFrequency"),
            Ast::name("Resource"),
            Ast::blockStatement(
                Ast::oneOrMore(Ast::classProperty()),
                Ast::classMethod(
                    Ast::name("fields"),
                    [Ast::param("request", 'Request')],
                    Ast::blockStatement([
                        $returnMatcher,
                    ]),
                ),
            ),
        );

        $this->assertMatches($classMatcher, <<<'TXT'
<?php
class MeasurementFrequency extends Resource
{
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
}
TXT
        );

        // flexible matcher — tolerates use statements and extra methods
        $classMatcher2 = Ast::classDeclaration(
            Ast::name("MeasurementFrequency"),
            Ast::name("Resource"),
            Ast::blockStatement(
                Ast::zeroOrMore(Ast::or(Ast::classProperty(), Ast::anyStatement())),
                Ast::classMethod(
                    Ast::name("fields"),
                    [Ast::param("request", 'Request')],
                    Ast::blockStatement([$returnMatcher]),
                ),
            ),
        );

        // classMatcher2: tolerates use statement and extra properties before fields
        $this->assertMatches($classMatcher2, <<<'TXT'
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
}
TXT
        );

        // extra variant — matches class with trailing sibling methods by name
        $classMatcher3 = Ast::classDeclaration(
            Ast::name("MeasurementFrequency"),
            Ast::name("Resource"),
            Ast::blockStatement(
                Ast::zeroOrMore(Ast::or(Ast::classProperty(), Ast::anyStatement())),
                Ast::classMethod(
                    Ast::name("fields"),
                    [Ast::param("request", 'Request')],
                    Ast::blockStatement([$returnMatcher]),
                ),
                Ast::zeroOrMore(Ast::classMethod(
                    Ast::or(
                        Ast::name("cards"),
                        Ast::name("filters"),
                        Ast::name("lenses"),
                        Ast::name("actions"),
                    )
                )),
            ),
        );

        $this->assertMatches($classMatcher3, <<<'TXT'
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
TXT
        );
    }
}

MatcherExamples::run();


