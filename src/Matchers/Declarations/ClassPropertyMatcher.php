<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ClassPropertyMatcher extends Matcher
{
    private $name;
    private $default;
    private $static;

    public function __construct($name = null, $default = null, $static = null)
    {
        $this->name = $name;
        $this->default = $default;
        $this->static = $static;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isClassProperty($node)) {
            return false;
        }
        // ClassProperty is Stmt\Property — contains multiple props; check first prop
        $prop = $node->props[0] ?? null;
        if ($prop === null) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($prop->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->default !== null && !$this->default->matchValue($prop->default, array_merge($keys, ['default']))) {
            return false;
        }
        if ($this->static !== null && is_bool($this->static)) {
            $isStatic = (bool)($node->flags & \PhpParser\Node\Stmt\Class_::MODIFIER_STATIC);
            if ($this->static !== $isStatic) {
                return false;
            }
        }
        return true;
    }
}
