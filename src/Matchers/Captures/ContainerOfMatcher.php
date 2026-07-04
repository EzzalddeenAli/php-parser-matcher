<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

/**
 * Matches any node (or array of nodes) that contains the target matcher
 * somewhere in its subtree.
 *
 * Extends CapturedMatcher so the found node is automatically recorded:
 *   $m = Ast::containerOf(Ast::callExpression(Ast::name('abort')));
 *   $m->match($ast);
 *   $found = $m->first();  // the MethodCall / StaticCall node that matched
 */
class ContainerOfMatcher extends CapturedMatcher
{
    private Matcher $containedMatcher;
    private ?Matcher $whenCond = null;

    public function __construct(Matcher $containedMatcher)
    {
        parent::__construct();
        $this->containedMatcher = $containedMatcher;
    }

    public function whenChild(Matcher $cond): static
    {
        $this->whenCond = $cond;
        return $this;
    }

    public function matchValue($value, $keys = []): bool
    {
        if (is_array($value)) {
            foreach ($value as $i => $element) {
                if ($this->matchValue($element, array_merge($keys, [$i]))) {
                    return true;
                }
            }
        }
        if (!NodeTypes::isNode($value)) {
            return false;
        }
        if ($this->containedMatcher->matchValue($value, $keys)) {
            $this->addCapture($value);
            return true;
        }
        foreach ($value->getSubNodeNames() as $key) {
            $sub = $value->{$key};
            if (is_array($sub)) {
                foreach ($sub as $i => $element) {
                    if ($this->allowsSubNode($element)) {
                        if ($this->matchValue($element, array_merge($keys, [$key, $i]))) {
                            return true;
                        }
                    }
                }
            } else {
                if ($this->allowsSubNode($sub)) {
                    if ($this->matchValue($sub, array_merge($keys, [$key]))) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function allowsSubNode(mixed $node): bool
    {
        if ($this->whenCond !== null) {
            return $this->whenCond->matchValue($node) || $this->containedMatcher->matchValue($node);
        }
        return true;
    }
}
