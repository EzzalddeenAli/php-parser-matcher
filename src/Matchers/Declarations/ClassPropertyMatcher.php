<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

class ClassPropertyMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name    = null,
        private readonly ?Matcher $default = null,
        private readonly ?bool    $static  = null,
    ) {}

    protected function nodeClass(): string { return Property::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->static !== null) {
            $isStatic = (bool) ($node->flags & Class_::MODIFIER_STATIC);
            if ($this->static !== $isStatic) return false;
        }
        $prop = $node->props[0] ?? null;
        if ($prop === null) return false;

        return $this->matchField($this->name,    $prop->name,    $keys, 'name')
            && $this->matchField($this->default, $prop->default, $keys, 'default');
    }
}
