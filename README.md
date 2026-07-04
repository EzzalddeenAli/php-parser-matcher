# fleet/ast-matcher

Fluent PHP AST pattern matching — composable, null-means-wildcard matchers for codemods, static analysis, and automated refactoring.  
Built on top of [`nikic/php-parser`](https://github.com/nikic/PHP-Parser).

```php
use Fleet\AstMatcher\Facade\Ast;

// Find every Text::make() chain that has ->sortable() and capture the field nodes
$m = Ast::anyList(
    Ast::zeroOrMore(
        Ast::chain()
            ->rootClass('Text')
            ->rootMethod('make')
            ->hasCall('sortable')
    )->captureList('fields')
);

Ast::match($m, $nodes);
$fields = Ast::globalCaptures()->get('fields'); // → array of matched chain nodes
```

```php
use Fleet\AstMatcher\Facade\Ast;

// Match every static call to Text::make() with exactly two string arguments
$matcher = Ast::staticCall(
    Ast::name('Text'),
    Ast::name('make'),
    [Ast::arg(Ast::anyString()), Ast::arg(Ast::anyString())]
);

$matched = $matcher->matchValue($node, []);  // true / false
```

## Requirements

| |          |
|---|----------|
| PHP | **8.1+** |
| nikic/php-parser | **^5.0**  |

## Installation

```bash
composer config repositories.ast-matcher vcs https://github.com/EzzalddeenAli/php-parser-matcher

composer require fleet/ast-matcher

#or

composer require fleet/ast-matcher:dev-master
```

---

## Table of Contents

- [Core Concept](#core-concept)
- [Two API Styles](#two-api-styles)
- [Quick Start](#quick-start)
- [Matcher Reference](#matcher-reference)
  - [Generic](#generic)
  - [Scalars](#scalars)
  - [Names & Variables](#names--variables)
  - [Calls](#calls)
  - [Chain Call Matcher](#chain-call-matcher)
  - [Access](#access)
  - [Assignment](#assignment)
  - [Operations](#operations)
  - [Objects](#objects)
  - [Functions](#functions)
  - [Expressions](#expressions)
  - [Statements](#statements)
  - [Declarations](#declarations)
  - [Control Flow](#control-flow)
  - [Sub-nodes](#sub-nodes)
- [Collection Matchers](#collection-matchers)
- [Captures](#captures)
  - [capture() — unnamed slot](#capture--unnamed-slot)
  - [captures() — named group](#captures--named-group)
  - [Inline capture — `->capture('name')`](#inline-capture---capturename)
  - [Global CaptureGroup](#global-capturegroup)
  - [captureList() — capture a whole slice](#capturelist--capture-a-whole-slice)
  - [fromCapture() — backreference](#fromcapture--backreference)
  - [containerOf() — deep search](#containerof--deep-search)
- [MatcherPrinter](#matcherprinter)
- [Build Facade](#build-facade)
- [Testing Framework](#testing-framework)

---

## Core Concept

**`null` = wildcard.** Every parameter is optional. When a parameter is `null`, it matches anything in that position.

```php
Ast::callExpression()                             // any function call
Ast::callExpression(Ast::name('foo'))             // calls to foo()  (any args)
Ast::callExpression(Ast::name('foo'), [])         // calls to foo()  with no args
Ast::callExpression(Ast::name('foo'), [
    Ast::arg(Ast::stringLiteral('bar'))           // foo('bar')  exactly
])
```

---

## Two API Styles

Both are functionally identical — choose what fits your project.

### Static Facade (recommended)

```php
use Fleet\AstMatcher\Facade\Ast;

$matcher = Ast::staticCall(Ast::name('DB'), Ast::name('table'));
```

### Global Functions

Loaded automatically via Composer's `files` autoload — no import needed.

```php
$matcher = staticCall(name('DB'), name('table'));
```

> Reserved PHP keywords (`if`, `else`, `foreach`, `new`, …) get an underscore suffix as global functions: `if_()`, `else_()`, `new_()`, etc. The `Ast::` facade uses the bare name since PHP allows reserved words as class method names.

---

## Quick Start

```php
use Fleet\AstMatcher\Facade\Ast;
use PhpParser\ParserFactory;

// 1. Parse some PHP code
$parser = (new ParserFactory())->createForNewestSupportedVersion();
$ast    = $parser->parse('<?php $repo->findBy(["active" => true]);');

// 2. Build a matcher
$matcher = Ast::methodCall(
    Ast::variable('repo'),
    Ast::name('findBy')
);

// 3. Walk the AST and test each node
$finder = new \PhpParser\NodeFinder();
$node   = $finder->findFirst($ast, fn($n) => $matcher->matchValue($n, []));

// $node is the MethodCall node, or null if not found
```

---

## Matcher Reference

### Generic

| Method | Matches |
|--------|---------|
| `Ast::any()` | Anything (value, node, null) |
| `Ast::anyNode()` | Any PhpParser `Node` |
| `Ast::anyStatement()` | Any `Stmt` node |
| `Ast::or($m1, $m2, …)` | Any one of the given matchers |
| `Ast::predicate(fn($v) => …)` | Custom callable returning bool |

### Scalars

| Method | Matches |
|--------|---------|
| `Ast::stringLiteral('foo')` | `'foo'` string literal |
| `Ast::stringLiteral()` | Any string literal |
| `Ast::numberLiteral(42)` | `42` (int or float, loose comparison) |
| `Ast::anyString()` | Any PHP string value (non-node) |
| `Ast::anyNumber()` | Any PHP number value (non-node) |

Aliases: `Ast::string()` → `stringLiteral()`, `Ast::number()` → `numberLiteral()`

### Names & Variables

| Method | Matches |
|--------|---------|
| `Ast::name('Foo')` | Identifier or Name with value `Foo` |
| `Ast::identifier('foo')` | Same as `name()` |
| `Ast::variable('user')` | `$user` |
| `Ast::variable()` | Any variable |

Alias: `Ast::var()` → `variable()`

### Calls

| Method | Signature | Matches |
|--------|-----------|---------|
| `callExpression` | `($callee, $args)` | `foo(…)` |
| `methodCall` | `($object, $name, $args)` | `$obj->method(…)` |
| `staticCall` | `($class, $name, $args)` | `Class::method(…)` |
| `nullsafeCall` | `($object, $name, $args)` | `$obj?->method(…)` |

`$args` accepts: `null` (wildcard), a plain array of `ArgMatcher`, or any collection matcher.

Alias: `Ast::call()` → `callExpression()`

---

### Chain Call Matcher

Matches a full **method chain** as a unit — root call plus every chained `->call()`.  
Unlike `staticCall()` / `methodCall()` which match a single AST node, `chain()` flattens the entire chain tree and lets you assert conditions across the root AND individual chain calls in one fluent expression.

```php
$m = Ast::chain()
    ->rootClass('Text')        // root is a static call on class "Text"
    ->rootMethod('make')       // root method is "make"
    ->hasCall('sortable')      // chain contains ->sortable()
    ->lacksCall('hideFromIndex')
    ->callArgs('rules', Ast::anyList(Ast::arg(Ast::string('required')), Ast::zeroOrMore()));
```

#### Root constraints

| Method | Description |
|--------|-------------|
| `->rootClass(string\|Matcher)` | Root call's class name |
| `->rootMethod(string\|Matcher)` | Root call's method name |
| `->rootClassIn(string[])` | Root class is one of the given names |
| `->rootIsStaticCall()` | Root must be a `Class::method()` static call |
| `->rootArgs(array\|Matcher)` | Root call's arguments |

#### Chain-level constraints

| Method | Description |
|--------|-------------|
| `->hasCall(string)` | Chain must contain this call anywhere |
| `->lacksCall(string)` | Chain must NOT contain this call |
| `->hasAnyCall(string[])` | At least one of the given call names must be present |
| `->callArgs(string, array\|Matcher)` | A specific chained call must have these args |
| `->chainLength(int $min, ?int $max)` | Number of chained calls (root not counted) |

All conditions are ANDed together.

#### Examples

```php
// Match Text::make() or Textarea::make() that has ->rules('required')
$m = Ast::chain()
    ->rootClassIn(['Text', 'Textarea'])
    ->rootMethod('make')
    ->callArgs('rules', Ast::anyList(Ast::arg(Ast::string('required')), Ast::zeroOrMore()));

// Match any Eloquent query that calls ->get(), ->first(), or ->paginate()
$m = Ast::chain()
    ->rootIsStaticCall()
    ->hasAnyCall(['get', 'first', 'paginate']);

// Match chains with at least 2 chained calls after the root
$m = Ast::chain()->rootMethod('make')->chainLength(2);

// Chain inside containerOf() — search deeply in a return statement
$m = Ast::containerOf(
    Ast::chain()->rootClassIn(['Text', 'ID'])->hasCall('sortable')
);
```

Alias: `Ast::chainCall()` → `chain()`

---

### Access

| Method | Matches |
|--------|---------|
| `Ast::propertyFetch($obj, $prop)` | `$obj->prop` |
| `Ast::nullsafeProp($obj, $prop)` | `$obj?->prop` |
| `Ast::classConstFetch($class, $name)` | `Class::CONST` |
| `Ast::constFetch('PHP_EOL')` | Any constant by name |
| `Ast::true()` | `true` |
| `Ast::false()` | `false` |
| `Ast::null()` | `null` |
| `Ast::arrayAccess($var, $dim)` | `$arr['key']` / `$arr[0]` |

Aliases: `Ast::memberExpression()` → `propertyFetch()`, `Ast::arrayDimFetch()` → `arrayAccess()`

### Assignment

| Method | Matches |
|--------|---------|
| `Ast::assign($var, $expr)` | `$a = $b` |
| `Ast::assignOp('+=', $var, $expr)` | `$a += $b` |

Supported `assignOp` operators: `+=` `-=` `*=` `/=` `%=` `**=` `.=` `&=` `|=` `^=` `<<=` `>>=` `??=`

### Operations

| Method | Matches |
|--------|---------|
| `Ast::binaryOp('+', $left, $right)` | `$a + $b` |
| `Ast::binaryOp('??', $left, $right)` | `$a ?? $b` |
| `Ast::binaryOp()` | Any binary operation |
| `Ast::unaryOp('!', $expr)` | `!$expr` |
| `Ast::ternary($cond, $if, $else)` | `$a ? $b : $c` or `$a ?: $c` |
| `Ast::cast('int', $expr)` | `(int) $expr` |

Supported binary operators: `+` `-` `*` `/` `%` `**` `.` `==` `!=` `===` `!==` `<` `<=` `>` `>=` `&&` `||` `and` `or` `xor` `&` `|` `^` `<<` `>>` `??` `<=>`

Supported cast types: `int` `float` `string` `bool` `array` `object` `unset`

Supported unary operators: `!` `~` `-` `+` `++` `--` `++$` (post-increment) `--$` (post-decrement)

Alias: `Ast::logicalExpression()` → `binaryOp()`

### Objects

| Method | Matches |
|--------|---------|
| `Ast::new($class, $args)` | `new Foo(…)` |
| `Ast::instanceof($expr, $class)` | `$x instanceof Foo` |

### Functions

| Method | Signature | Matches |
|--------|-----------|---------|
| `closure` | `($params, $body, $static)` | `function(…) { … }` |
| `arrowFn` | `($params, $expr, $static)` | `fn(…) => expr` |

### Expressions

| Method | Matches |
|--------|---------|
| `Ast::arrayExpression([…])` | `[item1, item2]` — each element matched by `ArrayItemMatcher` |
| `Ast::arrayExpression(Ast::anyList(…))` | `[…]` — element VALUES passed directly to a collection matcher |
| `Ast::throw($expr)` | `throw new Foo()` (PHP 8 throw expression) |
| `Ast::matchExpr($subject, $arms)` | `match ($x) { … }` |

Alias: `Ast::array()` → `arrayExpression()`

> **Two modes for `arrayExpression()`:**  
> Pass an **array** → each element is matched as an `ArrayItem` node (key + value).  
> Pass a **single Matcher** → the element VALUES are unwrapped and passed directly to the matcher, enabling collection matchers like `anyList()` to operate on the values without caring about `ArrayItem` wrappers.

```php
// Array mode — match [true, false] exactly
Ast::arrayExpression([
    Ast::arrayItem(Ast::true()),
    Ast::arrayItem(Ast::false()),
])

// Matcher mode — flexible, value-only
Ast::arrayExpression(
    Ast::anyList(
        Ast::zeroOrMore(Ast::chain()->rootIsStaticCall()),
        Ast::zeroOrMore()
    )
)
```

### Statements

| Method | Matches |
|--------|---------|
| `Ast::return($argument)` | `return $x;` |
| `Ast::expressionStatement($expr)` | A bare expression as a statement |
| `Ast::echo($exprs)` | `echo …;` |
| `Ast::break($num)` | `break;` / `break 2;` |
| `Ast::continue($num)` | `continue;` / `continue 2;` |

Aliases: `Ast::returnStatement()` → `return()`, `Ast::statement()` → `expressionStatement()`

### Declarations

| Method | Signature | Matches |
|--------|-----------|---------|
| `functionDeclaration` | `($name, $params, $body)` | `function foo(…) { … }` |
| `classDeclaration` | `($name, $extends, $body)` | `class Foo extends Bar { … }` |
| `classMethod` | `($name, $params, $body, $static)` | Class method declaration |
| `classProperty` | `($name, $default, $static)` | Class property declaration |
| `trait` | `($name, $body)` | `trait Foo { … }` |
| `interface` | `($name, $extends, $body)` | `interface Foo { … }` |
| `enum` | `($name, $scalarType, $body)` | `enum Status: string { … }` |
| `enumCase` | `($name, $expr)` | `case Active = 'active';` |
| `namespace` | `($name, $stmts)` | `namespace Foo\Bar;` |
| `use` | `($name, $alias)` | `use Foo\Bar as Baz;` |

### Control Flow

| Method | Signature | Matches |
|--------|-----------|---------|
| `if` | `($cond, $then, $elseifs, $else)` | `if (…) { … }` |
| `elseIf` | `($cond, $body)` | `elseif (…) { … }` |
| `else` | `($body)` | `else { … }` |
| `foreach` | `($expr, $valueVar, $keyVar, $body)` | `foreach (… as …)` |
| `while` | `($cond, $body)` | `while (…) { … }` |
| `doWhile` | `($body, $cond)` | `do { … } while (…);` |
| `for` | `($init, $cond, $loop, $body)` | `for (…;…;…) { … }` |
| `tryCatch` | `($body, $catches, $finally)` | `try { … } catch { … }` |
| `catch` | `($types, $var, $body)` | `catch (Exception $e) { … }` |
| `finally` | `($body)` | `finally { … }` |
| `switch` | `($cond, $cases)` | `switch ($x) { … }` |
| `case` | `($cond, $body)` | `case 'foo':` |

### Sub-nodes

| Method | Matches |
|--------|---------|
| `Ast::arg($value, $name)` | Function/method argument (optionally named) |
| `Ast::param($name, $type)` | Function/method parameter |
| `Ast::arrayItem($value, $key)` | Array element `key => value` |
| `Ast::attribute($name, $args)` | PHP 8 attribute `#[Attr(…)]` |
| `Ast::traitUse($traits)` | `use TraitName;` inside a class |

`param()` accepts string shorthand: `Ast::param('userId', 'int')` matches `int $userId`.

---

## Collection Matchers

Use these as the `$args`, `$body`, or `$params` of any matcher to describe an array of nodes.

| Matcher | Behaviour |
|---------|-----------|
| `[m1, m2]` | Shorthand for `tupleOf` — exact ordered match |
| `Ast::tupleOf($m1, $m2)` | Exactly these elements in this order |
| `Ast::anyList($m1, zeroOrMore(), $m2)` | Flexible ordered match with wildcards |
| `Ast::body($m1, zeroOrMore(), $m2)` | Alias for `anyList()` |
| `Ast::arrayOf($matcher)` | Every element matches `$matcher` |
| `Ast::zeroOrMore($matcher)` | 0 or more elements matching `$matcher` |
| `Ast::oneOrMore($matcher)` | 1 or more elements matching `$matcher` |
| `Ast::spacer($n)` | Exactly `$n` elements (any value) |
| `Ast::slice(['min'=>1,'max'=>3], $m)` | Between 1 and 3 elements matching `$m` |

### Example — flexible argument lists

```php
// Match any call to foo() that has 'bar' as its last argument
$matcher = Ast::callExpression(
    Ast::name('foo'),
    Ast::anyList(
        Ast::zeroOrMore(),                    // any number of leading args
        Ast::arg(Ast::stringLiteral('bar'))   // 'bar' as the final arg
    )
);
```

---

## Captures

Extract matched nodes for later use. There are four complementary styles — pick the one that fits your pattern.

---

### `capture()` — unnamed slot

Wraps any matcher. Records every node it matches (accumulates across a single `match()` call).

```php
$cap = Ast::capture(Ast::string());           // typed
$cap = Ast::capture();                        // wildcard (any node)

$m = Ast::callExpression(
    Ast::name('route'),
    Ast::anyList(Ast::arg($cap), Ast::zeroOrMore())
);

if ($m->match($node)) {
    $cap->first();    // first captured node
    $cap->last();     // last captured node
    $cap->all();      // all captured nodes (array)
    $cap->count();    // number of captures
    $cap->matched();  // bool — was anything captured?
    $cap->reset();    // clear to reuse
}
```

Inside `zeroOrMore`, `capture()` records every element separately:

```php
$cap = Ast::capture(Ast::string());
$m   = Ast::callExpression(
    Ast::name('route'),
    Ast::anyList(Ast::zeroOrMore(Ast::arg($cap)))
);

$m->match($node);  // route('home', 'GET', 'web')
$cap->count();     // 3
$cap->all();       // [String_('home'), String_('GET'), String_('web')]
$cap->first();     // String_('home')
$cap->last();      // String_('web')
```

---

### `captures()` — named group

Create a `CaptureGroup` once, embed named slots inline, then read all results from a single object.

```php
$caps = Ast::captures();

$m = Ast::callExpression(
    $caps->capture('fn',    Ast::name()),
    Ast::anyList(
        Ast::arg($caps->capture('first', Ast::string())),
        Ast::zeroOrMore()
    )
);

if ($m->match($node)) {
    $caps->get('fn');            // first matched value for 'fn'
    $caps->all('args');          // all values for a slot used inside zeroOrMore
    $caps->has('fn');            // bool
    $caps->matcher('fn');        // the underlying CapturedMatcher
    $caps->toArray();            // ['fn' => node, 'first' => node, …]
    $caps->reset();              // clear all slots for reuse
}
```

---

### Inline capture — `->capture('name')`

Every `Matcher` instance exposes `->capture()` as a fluent shorthand. No need to pre-create a variable.

```php
// Anonymous (no name, no registration) — same as Ast::capture($this)
$cap = Ast::string()->capture();

// Named — registers automatically in the global CaptureGroup
$m = Ast::callExpression(
    Ast::name()->capture('fn'),
    Ast::anyList(
        Ast::arg(Ast::string()->capture('path')),
        Ast::zeroOrMore()
    )
);

Ast::match($m, $node);                       // run + auto-reset
Ast::globalCaptures()->get('fn');            // Name node
Ast::globalCaptures()->get('path');          // String_ node

// Named — registers in an explicit group (global untouched)
$g = Ast::captures();
$m = Ast::callExpression(Ast::name()->capture('fn', $g));
$m->match($node);
$g->get('fn');
```

**Signature:** `$matcher->capture(?string $name = null, ?CaptureGroup $group = null): CapturedMatcher`

---

### Global CaptureGroup

When `->capture('name')` is called without an explicit group, it registers in a process-wide singleton — the **global CaptureGroup**.

| Method | Description |
|--------|-------------|
| `Ast::globalCaptures()` | Returns the global `CaptureGroup` |
| `Ast::resetCaptures()` | Clears all captured data (keeps slot registrations) |
| `Ast::match($matcher, $node)` | **Resets** global captures, then runs the match |

`Ast::match()` is the idiomatic entry point when using inline captures — it ensures each call starts with a clean slate:

```php
$m = Ast::callExpression(
    Ast::name()->capture('fn'),
    Ast::anyList(Ast::zeroOrMore(Ast::arg(Ast::string()->capture('args'))))
);

// First node
Ast::match($m, $node1);
Ast::globalCaptures()->get('fn');               // 'route'
Ast::globalCaptures()->matcher('args')->all();  // [String_('home'), …]

// Second node — auto-reset, fresh data
Ast::match($m, $node2);
Ast::globalCaptures()->get('fn');               // 'middleware'
```

Use `Ast::resetCaptures()` instead when you prefer calling `$m->match()` directly:

```php
Ast::resetCaptures();
$m->match($node);
Ast::globalCaptures()->get('fn');
```

---

### `captureList()` — capture a whole slice

`captureList()` is available on **slice matchers** (`zeroOrMore`, `oneOrMore`, `spacer`).  
Instead of recording each element individually, it waits until the full list match succeeds and then stores the **entire matched sub-array as a single capture entry**.

```php
$m = Ast::callExpression(
    Ast::name('select'),
    Ast::anyList(
        Ast::zeroOrMore(Ast::arg(Ast::string()))->captureList('columns')
    )
);

Ast::match($m, static::parseExpression("select('id', 'name', 'email')"));

$cols = Ast::globalCaptures()->get('columns');  // → [Arg, Arg, Arg]  (one array)
count($cols);                                   // 3
```

**Signature:** `SliceMatcher::captureList(?string $name = null, ?CaptureGroup $group = null): SliceCaptureMatcher`

Comparison with `capture()`:

| | `capture()` on each element | `captureList()` on the slice |
|---|---|---|
| `first()` | First element node | The whole array `[n1, n2, n3]` |
| `all()` | `[n1, n2, n3]` | `[[n1, n2, n3]]` (one entry per match) |
| `count()` | 3 | 1 |
| Backtrack-safe | No — records during failed distributions | **Yes** — committed only on success |

**Multiple slices in one pattern:**

```php
$m = Ast::callExpression(
    Ast::name('migrate'),
    Ast::anyList(
        Ast::zeroOrMore(Ast::arg(Ast::string()))->captureList('stringArgs'),
        Ast::zeroOrMore(Ast::arg(Ast::number()))->captureList('numberArgs')
    )
);

Ast::match($m, static::parseExpression("migrate('up', 'down', 1, 2, 3)"));

count(Ast::globalCaptures()->get('stringArgs')); // 2
count(Ast::globalCaptures()->get('numberArgs')); // 3
```

**Chain collection example** (typical Nova / Filament resource pattern):

```php
$m = Ast::containerOf(
    Ast::arrayExpression(
        Ast::anyList(
            Ast::zeroOrMore(
                Ast::chain()->rootIsStaticCall()->rootMethod('make')
            )->captureList('fields'),
            Ast::zeroOrMore()  // any trailing items
        )
    )
);

Ast::match($m, $returnStatement);
$fields = Ast::globalCaptures()->get('fields'); // array of chain nodes
```

> **Note:** `captureList()` requires matched elements to be **contiguous** in the list, because `anyList()` operates on sequential slices.

---

### `fromCapture()` — backreference

Match a node that is structurally equal to whatever a previous capture collected.

```php
// Match $x === $x (same variable on both sides)
$cap = Ast::capture(Ast::variable());

$m = Ast::binaryOp('===', $cap, Ast::fromCapture($cap));

$m->match($node);   // true for "$foo === $foo", false for "$x === $y"
```

`fromCapture()` uses the **most recent** captured value (`last()`), so it correctly handles patterns where the same capture accumulates multiple nodes.

---

### `containerOf()` — deep search

Matches any node that contains the given pattern **anywhere in its subtree**.  
Extends `CapturedMatcher`, so `first()` returns the matching inner node.

```php
$m = Ast::containerOf(
    Ast::callExpression(Ast::name('abort'))
);

$m->match($ifNode);    // true if abort() appears anywhere inside the if
$m->first();           // the FuncCall node for abort()

// Deep inside nested structures
$m->match($classNode); // searches recursively through the entire class
```

---

## MatcherPrinter

Convert any PHP expression or statement into the `Ast::` code that would match it.  
Useful for generating matchers from real-world code samples.

```php
use Fleet\AstMatcher\Printer\MatcherPrinter;

$printer = new MatcherPrinter();

echo $printer->printCode("Text::make('Name', 'name')");
```

Output:

```php
Ast::staticCall(
    Ast::name('Text'),
    Ast::name('make'),
    [
        Ast::arg(Ast::stringLiteral('Name')),
        Ast::arg(Ast::stringLiteral('name')),
    ]
)
```

The generated code is valid PHP you can paste directly into your codebase.

### Options

```php
new MatcherPrinter(
    facade: 'Ast',      // 'Ast' (default) or 'function' for global helper style
    indent: 4,          // spaces per indent level (default 4)
)
```

### Work with an already-parsed node

```php
$printer->printNode($phpParserNode);
```

---

## Build Facade

Mirror of `Ast::` that accepts **concrete values** and returns **PhpParser Nodes** — ready to pretty-print back to PHP source.  
String arguments are auto-converted: class names → `Name`, method names → `Identifier`.

```php
use Fleet\AstMatcher\Facade\Build;

$node = Build::staticCall('Text', 'make', [
    Build::arg(Build::string('Name')),
    Build::arg(Build::string('name')),
]);

echo Build::print($node);
// → Text::make('Name', 'name')
```

### Building a class

```php
$class = Build::classDeclaration('MyController', 'Controller', [
    Build::classMethod('index', [], [
        Build::return(
            Build::staticCall('View', 'make', [
                Build::arg(Build::string('home')),
            ])
        ),
    ]),
]);

echo Build::print($class);
```

```php
class MyController extends Controller
{
    public function index()
    {
        return View::make('home');
    }
}
```

### Scalar & name helpers

| Method | Returns |
|--------|---------|
| `Build::string('foo')` | `Scalar\String_` |
| `Build::int(42)` | `Scalar\LNumber` |
| `Build::float(3.14)` | `Scalar\DNumber` |
| `Build::true()` / `Build::false()` / `Build::null()` | `Expr\ConstFetch` |
| `Build::variable('name')` | `Expr\Variable` |
| `Build::name('Foo')` | `Node\Name` |
| `Build::identifier('method')` | `Node\Identifier` |

### Printing helpers

| Method | Description |
|--------|-------------|
| `Build::print($node)` | Expr → no semicolon; Stmt → with semicolon |
| `Build::printExpr($expr)` | Pretty-print an expression |
| `Build::printStatement($stmt)` | Pretty-print a statement |
| `Build::printExprAsStatement($expr)` | Wrap in expression-statement and print |

---

## Testing Framework

The library ships with a lightweight CLI test runner for example-based tests — no PHPUnit required.

```php
use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class MyMatcherTests extends AstTestRunner
{
    #[Example('Match any call to abort()')]
    public function exampleAbortCall(): void
    {
        $matcher = Ast::callExpression(Ast::name('abort'));

        $this->assertMatches($matcher, 'abort()');
        $this->assertMatches($matcher, 'abort(403)');
        $this->assertNotMatches($matcher, 'exit()');
    }

    #[Example('Capture the first argument of route()')]
    public function exampleCaptureRouteName(): void
    {
        $cap     = Ast::capture(Ast::stringLiteral());
        $matcher = Ast::callExpression(
            Ast::name('route'),
            Ast::anyList(Ast::arg($cap), Ast::zeroOrMore())
        );

        $this->assertMatches($matcher, "route('home')");
        // $cap->first() → Scalar\String_('home')
    }

    #[Example('Inline capture — named slot in global group')]
    public function exampleInlineCapture(): void
    {
        $matcher = Ast::callExpression(
            Ast::name('route')->capture('fn'),
            Ast::anyList(Ast::arg(Ast::string()->capture('path')), Ast::zeroOrMore())
        );

        $this->assertTrue(Ast::match($matcher, $this->parseExpression("route('home')")));
        $this->assertTrue(Ast::globalCaptures()->get('fn')->toString() === 'route');
        $this->assertTrue(Ast::globalCaptures()->get('path')->value === 'home');
    }
}

MyMatcherTests::run();
```

### Runner output

```
══════════════════════════════════════
  MyMatcherTests  (3 tests)
══════════════════════════════════════
▶ [1/3] Match any call to abort()
  ✔ Passed

▶ [2/3] Capture the first argument of route()
  ✔ Passed

▶ [3/3] Inline capture — named slot in global group
  ✔ Passed
──────────────────────────────────────
Results: 3 passed | 0 failed / 3 total
```

### Parsing helpers (static)

| Method | Returns |
|--------|---------|
| `AstTestRunner::parseExpression($code)` | Inner `Expr` node |
| `AstTestRunner::parseStatement($code)` | `Stmt` node |
| `AstTestRunner::parseFile($code)` | Full `Stmt[]` array |

### Assertions

| Method | Description |
|--------|-------------|
| `$this->assertMatches($matcher, $code)` | Fails if matcher does not match |
| `$this->assertNotMatches($matcher, $code)` | Fails if matcher does match |

Both accept a single code string or an array of strings.

---

## Real-world Example — Nova/Filament field analysis

Collect all `Text::make()` and `ID::make()` chains from a resource `fields()` method, along with their labels and whether they call `->sortable()`.

```php
use Fleet\AstMatcher\Facade\Ast;
use PhpParser\ParserFactory;
use PhpParser\NodeFinder;

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$stmts  = $parser->parse(file_get_contents('app/Nova/UserResource.php'));

// ── 1. Build the matcher ──────────────────────────────────────────────────────

// Capture all field chains from a fields() method return array
$m = Ast::containerOf(
    Ast::classMethod(
        Ast::name('fields'),
        null,
        Ast::anyList(
            Ast::zeroOrMore(),
            Ast::return(
                Ast::arrayExpression(
                    Ast::anyList(
                        Ast::zeroOrMore(
                            Ast::chain()->rootIsStaticCall()
                        )->captureList('fields')
                    )
                )
            ),
            Ast::zeroOrMore()
        )
    )
);

// ── 2. Run ────────────────────────────────────────────────────────────────────

if (Ast::match($m, $stmts)) {
    $fields = Ast::globalCaptures()->get('fields'); // array of chain nodes

    foreach ($fields as $chainNode) {
        // Use ChainCallMatcher to inspect each field
        $labelCap = Ast::capture(Ast::string());
        $isText = Ast::chain()
            ->rootClassIn(['Text', 'Textarea'])
            ->rootMethod('make')
            ->rootArgs(Ast::anyList(Ast::arg($labelCap), Ast::zeroOrMore()));

        $hasSortable = Ast::chain()->hasCall('sortable');

        if ($isText->match($chainNode)) {
            $label    = $labelCap->first()->value;
            $sortable = $hasSortable->match($chainNode);
            echo "{$label}" . ($sortable ? ' (sortable)' : '') . "\n";
        }
    }
}
```

---

## License

MIT
