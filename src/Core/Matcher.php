<?php

namespace Fleet\AstMatcher\Core;

use Fleet\AstMatcher\Contracts\MatcherInterface;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;

abstract class Matcher implements MatcherInterface
{
    abstract public function matchValue($value, $keys = []);

    public function match($value, $keys = [])
    {
        return $this->matchValue($value, $keys);
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
