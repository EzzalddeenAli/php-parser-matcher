<?php

namespace Fleet\AstMatcher\Printer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;

/**
 * Converts any PhpParser Node into the PHP code that creates a matching Ast matcher.
 *
 * Example:
 *   $printer->printCode("Text::make('Name', 'name')")
 *   → "Ast::staticCall(Ast::name('Text'), Ast::name('make'), [...])"
 */
class MatcherPrinter
{
    private readonly string $prefix;
    private readonly string $pad;

    // Reserved words that need an underscore suffix when used as global functions
    private const FUNCTION_RESERVED = [
        'if', 'else', 'elseIf', 'foreach', 'while', 'doWhile', 'for',
        'catch', 'finally', 'switch', 'case', 'echo', 'break', 'continue',
        'new', 'instanceof', 'return', 'throw', 'true', 'false', 'null',
        'trait', 'interface', 'enum', 'use', 'namespace', 'array',
    ];

    /**
     * @param string $facade  'Ast' (default) or 'function' to use global helper names
     * @param int    $indent  Spaces per indent level (default 4)
     */
    public function __construct(
        private readonly string $facade = 'Ast',
        private readonly int    $indent = 4,
    ) {
        $this->prefix = ($facade === 'function') ? '' : $facade . '::';
        $this->pad    = str_repeat(' ', $indent);
    }

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Parse a PHP expression/statement string and return its matcher code.
     */
    public function printCode(string $phpCode): string
    {
        static $factory = null;
        $factory ??= new ParserFactory();
        $parser = $factory->createForNewestSupportedVersion();

        $code  = '<?php ' . rtrim(trim($phpCode), ';') . ';';
        $stmts = $parser->parse($code);
        $node  = $stmts[0] ?? null;

        if ($node === null) {
            throw new \RuntimeException('Could not parse: ' . $phpCode);
        }

        return $this->printNode($node);
    }

    /**
     * Convert an already-parsed PhpParser Node to its matcher code string.
     */
    public function printNode(Node $node): string
    {
        if ($node instanceof Stmt\Expression) {
            $node = $node->expr;
        }
        return $this->convertNode($node);
    }

    // ─── Formatting ──────────────────────────────────────────────────────────

    private function call(string $method, array $args): string
    {
        $name = $this->resolveName($method);

        // Strip trailing null args (they represent optional wildcard params)
        while (!empty($args) && end($args) === 'null') {
            array_pop($args);
        }

        if (empty($args)) {
            return $this->prefix . $name . '()';
        }

        // Try inline: no newlines in any arg AND total width fits in 80 chars
        $hasNewlines = array_reduce($args, fn($c, $a) => $c || str_contains($a, "\n"), false);
        if (!$hasNewlines) {
            $inline = $this->prefix . $name . '(' . implode(', ', $args) . ')';
            if (strlen($inline) <= 80) {
                return $inline;
            }
        }

        // Multi-line: each arg at +1 indent, closing ')' at current level
        $lines = array_map(fn($a) => $this->pad . $this->reindent($a), $args);
        return $this->prefix . $name . "(\n" . implode(",\n", $lines) . "\n)";
    }

    // Global functions need underscore suffix for reserved words
    private function resolveName(string $method): string
    {
        if ($this->facade !== 'function') return $method;
        return in_array($method, self::FUNCTION_RESERVED, true) ? $method . '_' : $method;
    }

    // Indent continuation lines (lines after the first) — used when embedding
    // a multi-line string as an arg inside another call.
    private function reindent(string $s): string
    {
        return str_replace("\n", "\n" . $this->pad, $s);
    }

    private function convert(mixed $value): string
    {
        if ($value === null)                        return 'null';
        if (is_string($value))                      return var_export($value, true);
        if (is_int($value) || is_float($value))     return (string) $value;
        if (is_bool($value))                        return $value ? 'true' : 'false';
        if ($value instanceof Node)                 return $this->convertNode($value);
        if (is_array($value))                       return $this->convertArray($value);
        return 'null';
    }

    private function convertArray(array $items): string
    {
        if (empty($items)) return '[]';
        $lines = array_map(
            fn($item) => $this->pad . $this->reindent($this->convert($item)),
            $items,
        );
        return "[\n" . implode(",\n", $lines) . ",\n]";
    }

    // For For_ init/cond/loop — collapses single-element arrays to a plain node
    private function convertFirstOrArray(array $items): string
    {
        return match(count($items)) {
            0       => 'null',
            1       => $this->convert($items[0]),
            default => $this->convertArray($items),
        };
    }

    // ─── Node dispatch ───────────────────────────────────────────────────────

    private function convertNode(Node $node): string
    {
        if ($node instanceof Stmt\Expression) {
            return $this->convertNode($node->expr);
        }

        return match(true) {

            // ── Scalars
            $node instanceof Scalar\String_ =>
                $this->call('stringLiteral', [var_export($node->value, true)]),

            $node instanceof Scalar\LNumber =>
                $this->call('numberLiteral', [(string) $node->value]),

            $node instanceof Scalar\DNumber =>
                $this->call('numberLiteral', [(string) $node->value]),

            // ── Names / Identifiers
            $node instanceof Name =>
                $this->call('name', [var_export($node->toString(), true)]),

            $node instanceof Identifier =>
                $this->call('name', [var_export($node->name, true)]),

            // ── Variable  ($x)
            $node instanceof Expr\Variable =>
                is_string($node->name)
                    ? $this->call('variable', [var_export($node->name, true)])
                    : $this->call('variable', [$this->convert($node->name)]),

            // ── Const fetch  (true / false / null / PHP_EOL / …)
            $node instanceof Expr\ConstFetch =>
                $this->convertConstFetch($node),

            // ── Function call
            $node instanceof Expr\FuncCall =>
                $this->call('callExpression', [
                    $this->convert($node->name),
                    $this->convertArray($node->args),
                ]),

            // ── Method call
            $node instanceof Expr\MethodCall =>
                $this->call('methodCall', [
                    $this->convert($node->var),
                    $this->convert($node->name),
                    $this->convertArray($node->args),
                ]),

            // ── Nullsafe method call
            $node instanceof Expr\NullsafeMethodCall =>
                $this->call('nullsafeCall', [
                    $this->convert($node->var),
                    $this->convert($node->name),
                    $this->convertArray($node->args),
                ]),

            // ── Static call
            $node instanceof Expr\StaticCall =>
                $this->call('staticCall', [
                    $this->convert($node->class),
                    $this->convert($node->name),
                    $this->convertArray($node->args),
                ]),

            // ── Property fetch
            $node instanceof Expr\PropertyFetch =>
                $this->call('propertyFetch', [
                    $this->convert($node->var),
                    $this->convert($node->name),
                ]),

            // ── Nullsafe property
            $node instanceof Expr\NullsafePropertyFetch =>
                $this->call('nullsafeProp', [
                    $this->convert($node->var),
                    $this->convert($node->name),
                ]),

            // ── Class const fetch  (Foo::BAR)
            $node instanceof Expr\ClassConstFetch =>
                $this->call('classConstFetch', [
                    $this->convert($node->class),
                    $this->convert($node->name),
                ]),

            // ── Array dim fetch  ($a['key'] / $a[0])
            $node instanceof Expr\ArrayDimFetch =>
                $this->call('arrayAccess', [
                    $this->convert($node->var),
                    $this->convert($node->dim),
                ]),

            // ── Assignment
            $node instanceof Expr\Assign =>
                $this->call('assign', [
                    $this->convert($node->var),
                    $this->convert($node->expr),
                ]),

            $node instanceof Expr\AssignOp =>
                $this->call('assignOp', [
                    var_export($node->getOperatorSigil(), true),
                    $this->convert($node->var),
                    $this->convert($node->expr),
                ]),

            // ── Binary op  (+, -, &&, ??, …)
            $node instanceof Expr\BinaryOp =>
                $this->call('binaryOp', [
                    var_export($node->getOperatorSigil(), true),
                    $this->convert($node->left),
                    $this->convert($node->right),
                ]),

            // ── Unary ops
            $node instanceof Expr\BooleanNot  => $this->call('unaryOp', ["'!'",    $this->convert($node->expr)]),
            $node instanceof Expr\BitwiseNot  => $this->call('unaryOp', ["'~'",    $this->convert($node->expr)]),
            $node instanceof Expr\UnaryMinus  => $this->call('unaryOp', ["'-'",    $this->convert($node->expr)]),
            $node instanceof Expr\UnaryPlus   => $this->call('unaryOp', ["'+'",    $this->convert($node->expr)]),
            $node instanceof Expr\PreInc      => $this->call('unaryOp', ["'++'",   $this->convert($node->var)]),
            $node instanceof Expr\PreDec      => $this->call('unaryOp', ["'--'",   $this->convert($node->var)]),
            $node instanceof Expr\PostInc     => $this->call('unaryOp', ["'++\$'", $this->convert($node->var)]),
            $node instanceof Expr\PostDec     => $this->call('unaryOp', ["'--\$'", $this->convert($node->var)]),

            // ── Ternary  ($a ? $b : $c  or  $a ?: $c)
            $node instanceof Expr\Ternary =>
                $this->call('ternary', [
                    $this->convert($node->cond),
                    $this->convert($node->if),
                    $this->convert($node->else),
                ]),

            // ── Cast  ((int) $x)
            $node instanceof Expr\Cast =>
                $this->convertCast($node),

            // ── new / instanceof
            $node instanceof Expr\New_ =>
                $this->call('new', [
                    $this->convert($node->class),
                    $this->convertArray($node->args),
                ]),

            $node instanceof Expr\Instanceof_ =>
                $this->call('instanceof', [
                    $this->convert($node->expr),
                    $this->convert($node->class),
                ]),

            // ── Closure / Arrow function
            $node instanceof Expr\Closure =>
                $this->call('closure', [
                    $this->convertArray($node->params),
                    $this->convertArray($node->stmts),
                    $node->static ? 'true' : 'null',
                ]),

            $node instanceof Expr\ArrowFunction =>
                $this->call('arrowFn', [
                    $this->convertArray($node->params),
                    $this->convert($node->expr),
                    $node->static ? 'true' : 'null',
                ]),

            // ── Array expression  ([1, 2, 3])
            $node instanceof Expr\Array_ =>
                $this->call('arrayExpression', [
                    $this->convertArray($node->items),
                ]),

            // ── Throw / Match
            $node instanceof Expr\Throw_ =>
                $this->call('throw', [$this->convert($node->expr)]),

            $node instanceof Expr\Match_ =>
                $this->call('matchExpr', [
                    $this->convert($node->subject),
                    $this->convertArray($node->arms),
                ]),

            // ── Sub-nodes
            $node instanceof Node\Arg =>
                $this->call('arg', [
                    $this->convert($node->value),
                    $node->name !== null ? $this->convert($node->name) : 'null',
                ]),

            $node instanceof Node\ArrayItem =>
                $this->call('arrayItem', [
                    $this->convert($node->value),
                    $node->key !== null ? $this->convert($node->key) : 'null',
                ]),

            $node instanceof Node\Param =>
                $this->call('param', [
                    is_string($node->var->name)
                        ? var_export($node->var->name, true)
                        : $this->convert($node->var),
                    $node->type !== null ? $this->convert($node->type) : 'null',
                ]),

            $node instanceof Node\Attribute =>
                $this->call('attribute', [
                    $this->convert($node->name),
                    $this->convertArray($node->args),
                ]),

            // ── Statements
            $node instanceof Stmt\Return_ =>
                $this->call('return', [$this->convert($node->expr)]),

            $node instanceof Stmt\Echo_ =>
                $this->call('echo', [$this->convertArray($node->exprs)]),

            $node instanceof Stmt\Break_ =>
                $this->call('break', [$node->num !== null ? $this->convert($node->num) : 'null']),

            $node instanceof Stmt\Continue_ =>
                $this->call('continue', [$node->num !== null ? $this->convert($node->num) : 'null']),

            $node instanceof Stmt\If_ =>
                $this->call('if', [
                    $this->convert($node->cond),
                    $this->convertArray($node->stmts),
                    !empty($node->elseifs) ? $this->convertArray($node->elseifs) : 'null',
                    $node->else !== null ? $this->convert($node->else) : 'null',
                ]),

            $node instanceof Stmt\ElseIf_ =>
                $this->call('elseIf', [
                    $this->convert($node->cond),
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\Else_ =>
                $this->call('else', [$this->convertArray($node->stmts)]),

            $node instanceof Stmt\Foreach_ =>
                $this->call('foreach', [
                    $this->convert($node->expr),
                    $this->convert($node->valueVar),
                    $node->keyVar !== null ? $this->convert($node->keyVar) : 'null',
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\While_ =>
                $this->call('while', [
                    $this->convert($node->cond),
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\Do_ =>
                $this->call('doWhile', [
                    $this->convertArray($node->stmts),
                    $this->convert($node->cond),
                ]),

            $node instanceof Stmt\For_ =>
                $this->call('for', [
                    $this->convertFirstOrArray($node->init),
                    $this->convertFirstOrArray($node->cond),
                    $this->convertFirstOrArray($node->loop),
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\TryCatch =>
                $this->call('tryCatch', [
                    $this->convertArray($node->stmts),
                    $this->convertArray($node->catches),
                    $node->finally !== null ? $this->convert($node->finally) : 'null',
                ]),

            $node instanceof Stmt\Catch_ =>
                $this->call('catch', [
                    $this->convertArray($node->types),
                    $node->var !== null ? $this->convert($node->var) : 'null',
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\Finally_ =>
                $this->call('finally', [$this->convertArray($node->stmts)]),

            $node instanceof Stmt\Switch_ =>
                $this->call('switch', [
                    $this->convert($node->cond),
                    $this->convertArray($node->cases),
                ]),

            $node instanceof Stmt\Case_ =>
                $this->call('case', [
                    $node->cond !== null ? $this->convert($node->cond) : 'null',
                    $this->convertArray($node->stmts),
                ]),

            // ── Declarations
            $node instanceof Stmt\Function_ =>
                $this->call('functionDeclaration', [
                    $this->convert($node->name),
                    $this->convertArray($node->params),
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\Class_ =>
                $this->call('classDeclaration', [
                    $this->convert($node->name),
                    $node->extends !== null ? $this->convert($node->extends) : 'null',
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\ClassMethod =>
                $this->call('classMethod', [
                    $this->convert($node->name),
                    $this->convertArray($node->params),
                    $node->stmts !== null ? $this->convertArray($node->stmts) : 'null',
                    $node->isStatic() ? 'true' : 'null',
                ]),

            $node instanceof Stmt\Property =>
                $this->call('classProperty', [
                    !empty($node->props) ? $this->convert($node->props[0]->name) : 'null',
                    !empty($node->props) && $node->props[0]->default !== null
                        ? $this->convert($node->props[0]->default)
                        : 'null',
                    $node->isStatic() ? 'true' : 'null',
                ]),

            $node instanceof Stmt\Trait_ =>
                $this->call('trait', [
                    $this->convert($node->name),
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\Interface_ =>
                $this->call('interface', [
                    $this->convert($node->name),
                    !empty($node->extends) ? $this->convertArray($node->extends) : 'null',
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\Enum_ =>
                $this->call('enum', [
                    $this->convert($node->name),
                    $node->scalarType !== null ? $this->convert($node->scalarType) : 'null',
                    $this->convertArray($node->stmts),
                ]),

            $node instanceof Stmt\EnumCase =>
                $this->call('enumCase', [
                    $this->convert($node->name),
                    $node->expr !== null ? $this->convert($node->expr) : 'null',
                ]),

            $node instanceof Stmt\Namespace_ =>
                $this->call('namespace', [
                    $node->name !== null ? $this->convert($node->name) : 'null',
                    $node->stmts !== null ? $this->convertArray($node->stmts) : 'null',
                ]),

            $node instanceof Stmt\Use_ =>
                $this->call('use', [
                    !empty($node->uses) ? $this->convert($node->uses[0]->name) : 'null',
                    !empty($node->uses) && $node->uses[0]->alias !== null
                        ? $this->convert($node->uses[0]->alias)
                        : 'null',
                ]),

            $node instanceof Stmt\TraitUse =>
                $this->call('traitUse', [$this->convertArray($node->traits)]),

            default => '/* unsupported: ' . $node->getType() . ' */',
        };
    }

    private function convertConstFetch(Expr\ConstFetch $node): string
    {
        $lower = strtolower($node->name->toString());
        if (in_array($lower, ['true', 'false', 'null'], true)) {
            $method = $this->facade === 'function' ? $lower . '_' : $lower;
            return $this->prefix . $method . '()';
        }
        return $this->call('constFetch', [var_export($node->name->toString(), true)]);
    }

    private function convertCast(Expr\Cast $node): string
    {
        $type = match(true) {
            $node instanceof Expr\Cast\Int_    => 'int',
            $node instanceof Expr\Cast\Double  => 'float',
            $node instanceof Expr\Cast\String_ => 'string',
            $node instanceof Expr\Cast\Bool_   => 'bool',
            $node instanceof Expr\Cast\Array_  => 'array',
            $node instanceof Expr\Cast\Object_ => 'object',
            $node instanceof Expr\Cast\Unset_  => 'unset',
            default                            => 'unknown',
        };
        return $this->call('cast', [var_export($type, true), $this->convert($node->expr)]);
    }
}
