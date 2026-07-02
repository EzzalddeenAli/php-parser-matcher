<?php

namespace Fleet\AstMatcher\Testing;

use Fleet\AstMatcher\Testing\Attributes\Example;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Base class for running AST matcher examples/tests via CLI.
 *
 * @method void assertString(mixed $value, string $message = '')
 * @method void assertTrue(mixed $value, string $message = '')
 * @method void assertFalse(mixed $value, string $message = '')
 * @method void assertBoolean(mixed $value, string $message = '')
 * @method void assertStringNotEmpty(mixed $value, string $message = '')
 * @method void assertInteger(mixed $value, string $message = '')
 * @method void assertPositiveInteger(mixed $value, string $message = '')
 * @method void assertNotNull(mixed $value, string $message = '')
 */
class AstTestRunner
{
    protected Parser $parser;
    private static PrettyPrinter $printer;

    protected const COLOR_RESET  = "\033[0m";
    protected const COLOR_GREEN  = "\033[32m";
    protected const COLOR_RED    = "\033[31m";
    protected const COLOR_YELLOW = "\033[33m";
    protected const COLOR_CYAN   = "\033[36m";
    protected const COLOR_BOLD   = "\033[1m";

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        self::$printer = new PrettyPrinter();
    }

    public function __call(string $name, array $arguments): void
    {
        if (str_starts_with($name, 'assert')) {
            $method = lcfirst(substr($name, 6));
            if (method_exists(Assert::class, $method)) {
                Assert::$method(...$arguments);
                return;
            }
            throw new \BadMethodCallException("Assert::$method() does not exist.");
        }
        throw new \BadMethodCallException("Method '$name' is not defined.");
    }

    public static function parseExpression(string $code)
    {
        $instance = new static();
        $stmts = $instance->parser->parse('<?php ' . $code . ';');
        $first = $stmts[0] ?? null;
        return ($first instanceof Expression) ? $first->expr : $first;
    }

    public static function parseStatement(string $code)
    {
        $instance = new static();
        $stmts = $instance->parser->parse('<?php ' . $code . ';');
        return $stmts[0] ?? null;
    }

    public static function parseFile(string $code): array
    {
        $instance = new static();
        return $instance->parser->parse($code) ?? [];
    }

    protected function parseSingleCode(string $code)
    {
        if (!str_starts_with(trim($code), '<?php')) {
            $ast = $this->parser->parse('<?php ' . PHP_EOL . $code . ';');
        } else {
            $ast = $this->parser->parse($code);
        }

        if (!is_array($ast) || count($ast) === 0) {
            throw new \Exception("Failed to parse code — empty result.");
        }

        $first = $ast[0];
        return ($first instanceof Expression) ? $first->expr : $first;
    }

    protected function assertMatches($matcher, string|array $codes): void
    {
        $codes = is_array($codes) ? $codes : [$codes];

        foreach ($codes as $index => $code) {
            $node = $this->parseSingleCode($code);

            if (!$matcher->match($node)) {
                $dump = $this->dumpNode($node);
                throw new \Exception(
                    "Match failed for code #" . ($index + 1) . ":\n\n"
                    . trim($code)
                    . "\n\nActual AST:\n" . $dump
                );
            }
        }
    }

    protected function assertNotMatches($matcher, string|array $codes): void
    {
        $codes = is_array($codes) ? $codes : [$codes];

        foreach ($codes as $index => $code) {
            $node = $this->parseSingleCode($code);

            if ($matcher->match($node)) {
                throw new \Exception(
                    "Expected no match for code #" . ($index + 1) . " but it matched:\n\n" . trim($code)
                );
            }
        }
    }

    private function dumpNode($node): string
    {
        if ($node === null) {
            return 'null';
        }
        return self::$printer->prettyPrint(is_array($node) ? $node : [$node]);
    }

    public static function run(): void
    {
        $instance = new static();
        $reflection = new \ReflectionClass(static::class);
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        self::printHeader("Running: " . $reflection->getShortName());

        foreach ($reflection->getMethods() as $method) {
            $attrs = $method->getAttributes(Example::class);
            if (empty($attrs)) {
                continue;
            }

            $attr = $attrs[0]->newInstance();
            $description = $attr->description ?: $method->getName();

            if ($attr->skip) {
                echo self::COLOR_YELLOW . "  ⊘ SKIP: " . self::COLOR_RESET . $description . PHP_EOL;
                $skipped++;
                continue;
            }

            echo self::COLOR_CYAN . self::COLOR_BOLD . "▶ " . self::COLOR_RESET . $description . PHP_EOL;

            try {
                $method->invoke($instance);
                echo self::COLOR_GREEN . "  ✔ Passed" . self::COLOR_RESET . PHP_EOL;
                $passed++;
            } catch (InvalidArgumentException $e) {
                echo self::COLOR_RED . "  ✘ Failed: " . self::COLOR_RESET . $e->getMessage() . PHP_EOL;
                $failed++;
            } catch (\Throwable $e) {
                echo self::COLOR_RED . "  ❗ Error: " . self::COLOR_RESET . $e->getMessage() . PHP_EOL;
                $failed++;
            }

            echo str_repeat("─", 50) . PHP_EOL;
        }

        echo PHP_EOL;
        $total = $passed + $failed + $skipped;
        echo self::COLOR_BOLD . "Results: " . self::COLOR_RESET;
        echo self::COLOR_GREEN . "$passed passed" . self::COLOR_RESET . " | ";
        echo ($failed > 0 ? self::COLOR_RED : '') . "$failed failed" . self::COLOR_RESET;
        if ($skipped > 0) {
            echo " | " . self::COLOR_YELLOW . "$skipped skipped" . self::COLOR_RESET;
        }
        echo " / $total total" . PHP_EOL;
    }

    protected static function printHeader(string $title): void
    {
        echo PHP_EOL;
        echo self::COLOR_BOLD . self::COLOR_GREEN . str_repeat("═", 55) . self::COLOR_RESET . PHP_EOL;
        echo self::COLOR_BOLD . self::COLOR_YELLOW . "  " . $title . self::COLOR_RESET . PHP_EOL;
        echo self::COLOR_BOLD . self::COLOR_GREEN . str_repeat("═", 55) . self::COLOR_RESET . PHP_EOL . PHP_EOL;
    }
}
