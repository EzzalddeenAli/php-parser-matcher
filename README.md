# fleet/ast-matcher

PHP AST pattern matching library built on top of [`nikic/php-parser`](https://github.com/nikic/PHP-Parser).  
Search for specific code structures inside parsed PHP — the foundation for codemods, static analysis, and automated refactoring tools.

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

---

## Requirements

| | |
|---|---|
| PHP | **8.1+** |
| nikic/php-parser | **^5.7** |

## Installation

```bash
composer require fleet/ast-matcher
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
| `Ast::arrayExpression([…])` | `[item1, item2]` |
| `Ast::throw($expr)` | `throw new Foo()` (PHP 8 throw expression) |
| `Ast::matchExpr($subject, $arms)` | `match ($x) { … }` |

Alias: `Ast::array()` → `arrayExpression()`

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

Extract matched nodes for later use.

```php
$capture = Ast::capture();  // a CapturedMatcher

$matcher = Ast::staticCall(
    Ast::name('Text'),
    $capture,  // will capture the method name node
);

if ($matcher->matchValue($node, [])) {
    $methodName = $capture->getLastCapture();  // → Identifier node
}
```

### Multiple captures — `captureCollector()`

Collects every match across the entire traversal.

```php
$methodNames = Ast::captureCollector();

$matcher = Ast::classDeclaration(
    null,
    null,
    Ast::body(
        Ast::zeroOrMore(),
        Ast::classMethod($methodNames),
        Ast::zeroOrMore(),
    )
);

$matcher->matchValue($classNode, []);

$names = $methodNames->getCaptures();  // array of Identifier nodes
```

### Backreference — `fromCapture()`

```php
// Match assignments where both sides are the same variable: $a = $a
$cap = Ast::capture(Ast::variable());

$matcher = Ast::assign(
    $cap,
    Ast::fromCapture($cap)  // right side must equal what left side captured
);
```

### Deep search — `containerOf()`

```php
// Match any node that contains a call to abort() somewhere inside it
$matcher = Ast::containerOf(
    Ast::callExpression(Ast::name('abort'))
);
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
            [Ast::arg($cap)]
        );

        $this->assertMatches($matcher, "route('home')");
        // $cap->getLastCapture() → Scalar\String_('home')
    }
}

MyMatcherTests::run();
```

### Runner output

```
══════════════════════════════════════
  MyMatcherTests
══════════════════════════════════════
▶ [1/2] Match any call to abort()
  ✔ Passed

▶ [2/2] Capture the first argument of route()
  ✔ Passed
──────────────────────────────────────
Results: 2 passed | 0 failed / 2 total
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

## Real-world Example — Finding Eloquent Patterns

```php
use Fleet\AstMatcher\Facade\Ast;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$stmts  = $parser->parse(file_get_contents('app/Models/User.php'));

// Capture the column name in every ->where('column', $value) call
$columnCapture = Ast::capture(Ast::stringLiteral());
$valueCapture  = Ast::capture();

$matcher = Ast::methodCall(
    null,
    Ast::name('where'),
    [Ast::arg($columnCapture), Ast::arg($valueCapture)]
);

$nodes = (new NodeFinder())->find($stmts, fn($n) => $matcher->matchValue($n, []));

foreach ($nodes as $node) {
    $column = $columnCapture->getLastCapture()->value;
    echo "where clause on column: {$column}\n";
}
```

---

## License

MIT
