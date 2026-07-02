<?php

namespace Fleet\AstMatcher\Matchers\Captures;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class FromCaptureMatcher extends Matcher
{
    private CapturedMatcher $capturedMatcher;

    public function __construct(CapturedMatcher $capturedMatcher)
    {
        $this->capturedMatcher = $capturedMatcher;
    }

    public function matchValue($value, $keys = []): bool
    {
        $captured = $this->capturedMatcher->getCurrent();
        if (NodeTypes::isNode($captured) && NodeTypes::isNode($value)) {
            return self::nodesEquivalent($captured, $value);
        }
        return $captured === $value;
    }

    private static function nodesEquivalent($a, $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if (get_class($a) !== get_class($b)) {
            return false;
        }
        foreach ($a->getSubNodeNames() as $key) {
            $aVal = $a->{$key};
            $bVal = $b->{$key};
            if ($aVal === $bVal) {
                continue;
            }
            if (is_array($aVal) && is_array($bVal)) {
                if (count($aVal) !== count($bVal)) {
                    return false;
                }
                for ($i = 0; $i < count($aVal); $i++) {
                    $av = $aVal[$i];
                    $bv = $bVal[$i];
                    if (NodeTypes::isNode($av) && NodeTypes::isNode($bv)) {
                        if (!self::nodesEquivalent($av, $bv)) {
                            return false;
                        }
                    } elseif ($av !== $bv) {
                        return false;
                    }
                }
                continue;
            }
            if (NodeTypes::isNode($aVal) && NodeTypes::isNode($bVal)) {
                if (!self::nodesEquivalent($aVal, $bVal)) {
                    return false;
                }
                continue;
            }
            return false;
        }
        return true;
    }
}
