<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ClassConstFetchMatcher extends Matcher
{
    private $object;
    private $property;

    public function __construct($object = null, $property = null)
    {
        $this->object = $object;
        $this->property = $property;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isClassConstFetch($node)) {
            return false;
        }
        if ($this->object !== null && !$this->object->matchValue($node->class, array_merge($keys, ['class']))) {
            return false;
        }
        if ($this->property !== null && !$this->property->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        return true;
    }
}
