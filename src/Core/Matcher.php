<?php

namespace Fleet\AstMatcher\Core;

use Fleet\AstMatcher\Contracts\MatcherInterface;
use Fleet\AstMatcher\Matchers\Captures\CapturedMatcher;
use Fleet\AstMatcher\Matchers\Captures\CaptureGroup;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;

abstract class Matcher implements MatcherInterface
{
    abstract public function matchValue($value, $keys = []);

    public final function match($value, $keys = [])
    {
        CaptureGroup::resetGlobal();
        return $this->matchValue($value, $keys);
    }

    /**
     * Wrap this matcher in a CapturedMatcher and, optionally, register it in a
     * CaptureGroup so results can be read by name after the match.
     *
     *   // Anonymous — equivalent to Ast::capture($this):
     *   $cap = Ast::string()->capture();
     *   $m->match($node);
     *   $cap->first();
     *
     *   // Inline registration in the global group:
     *   $m = Ast::callExpression(Ast::name()->capture('fn'), ...);
     *   Ast::match($m, $node);
     *   Ast::globalCaptures()->get('fn');
     *
     *   // Inline registration in an explicit group:
     *   $g = Ast::captures();
     *   $m = Ast::callExpression(Ast::name()->capture('fn', $g), ...);
     *   $m->match($node);
     *   $g->get('fn');
     */
    public function capture(?string $name = null, ?CaptureGroup $group = null): CapturedMatcher
    {
        $captured = new CapturedMatcher($this);
        if ($name !== null) {
            ($group ?? CaptureGroup::global())->register($name, $captured);
        }
        return $captured;
    }

    public static function getShortName($name): ?string
    {
        if (empty($name)) {
            return null;
        }
        if (is_string($name)) {
            return $name;
        }
        if ($name instanceof ClassLike) {
            if (!$name->name instanceof Identifier) {
                return '';
            }
            return self::getShortName($name->name);
        }
        if ($name instanceof Name || $name instanceof Identifier || $name instanceof Node\Expr\Variable) {
            $name = $name->toString();
        }
        $name = trim(strval($name), '\\');
        if (strpos($name, '\\') !== false) {
            $parts = explode('\\', $name);
            $shortName = end($parts);
            if (is_string($shortName)) {
                return $shortName;
            }
        }
        return $name;
    }

    public static function getIdentifierName($name): ?string
    {
        if (empty($name)) {
            return null;
        }
        if (is_string($name)) {
            return $name;
        }
        if ($name instanceof Name || $name instanceof Identifier || $name instanceof Node\Expr\Variable) {
            $name = $name->toString();
        } else {
            return null;
        }
        $name = trim(strval($name), '\\');
        if (strpos($name, '\\') !== false) {
            $parts = explode('\\', $name);
            $shortName = end($parts);
            if (is_string($shortName)) {
                return $shortName;
            }
        }
        return $name;
    }
}
