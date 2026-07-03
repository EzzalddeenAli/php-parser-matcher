<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class ControlFlowTests extends AstTestRunner
{
    // ─── If / Else ────────────────────────────────────────────────────────────

    #[Example('if() wildcard — matches any if statement')]
    public function testIfWildcard(): void
    {
        $m = Ast::if();
        $this->assertMatches($m, [
            'if (true) {}',
            'if ($x > 0) { return $x; }',
            'if ($a) { } else { }',
            'if ($a) { } elseif ($b) { } else { }',
        ]);
        $this->assertNotMatches($m, ['$x ? $y : $z', 'foreach ($a as $b) {}']);
    }

    #[Example('if with specific condition — matches if($user->isAdmin())')]
    public function testIfSpecificCondition(): void
    {
        $m = Ast::if(Ast::methodCall(Ast::variable('user'), Ast::name('isAdmin')));
        $this->assertMatches($m, ['if ($user->isAdmin()) {}', 'if ($user->isAdmin()) { return true; }']);
        $this->assertNotMatches($m, ['if ($user->isGuest()) {}', 'if ($admin->isAdmin()) {}']);
    }

    // ─── Foreach ──────────────────────────────────────────────────────────────

    #[Example('foreach() wildcard — matches any foreach loop')]
    public function testForeachWildcard(): void
    {
        $m = Ast::foreach();
        $this->assertMatches($m, [
            'foreach ($items as $item) {}',
            'foreach ($arr as $k => $v) {}',
        ]);
        $this->assertNotMatches($m, ['for ($i = 0; $i < 10; $i++) {}', 'while (true) {}']);
    }

    #[Example('foreach(expr, valueVar) — null keyVar is wildcard (matches with or without key)')]
    public function testForeachSpecific(): void
    {
        // null keyVar = wildcard: matches both "as $item" and "as $k => $item"
        $m = Ast::foreach(Ast::variable('items'), Ast::variable('item'));
        $this->assertMatches($m, [
            'foreach ($items as $item) {}',
            'foreach ($items as $k => $item) {}',  // keyVar wildcard matches with key too
        ]);
        $this->assertNotMatches($m, [
            'foreach ($rows as $row) {}',  // wrong var names
            'foreach ($rows as $k => $item) {}',  // wrong iterable
        ]);
    }

    #[Example('foreach with key — matches foreach ($x as $k => $v)')]
    public function testForeachWithKey(): void
    {
        $m = Ast::foreach(null, Ast::variable('value'), Ast::variable('key'));
        $this->assertMatches($m, [
            'foreach ($data as $key => $value) {}',
            'foreach ($arr as $key => $value) { echo "$key: $value"; }',
        ]);
        $this->assertNotMatches($m, ['foreach ($data as $value) {}']);
    }

    // ─── While ────────────────────────────────────────────────────────────────

    #[Example('while() wildcard — matches any while loop')]
    public function testWhileWildcard(): void
    {
        $m = Ast::while();
        $this->assertMatches($m, ['while (true) {}', 'while ($queue->count() > 0) {}']);
        $this->assertNotMatches($m, ['do {} while (true)', 'for (;;) {}']);
    }

    #[Example('while(condition) — matches while($condition) specifically')]
    public function testWhileSpecific(): void
    {
        $m = Ast::while(Ast::true());
        $this->assertMatches($m, ['while (true) {}', 'while (true) { break; }']);
        $this->assertNotMatches($m, ['while (false) {}', 'while ($running) {}']);
    }

    // ─── Do-While ─────────────────────────────────────────────────────────────

    #[Example('doWhile() wildcard — matches any do-while loop')]
    public function testDoWhileWildcard(): void
    {
        $m = Ast::doWhile();
        $this->assertMatches($m, ['do {} while (true)', 'do { $i++; } while ($i < 10)']);
        $this->assertNotMatches($m, ['while (true) {}', 'for (;;) {}']);
    }

    // ─── For ──────────────────────────────────────────────────────────────────

    #[Example('for() wildcard — matches any for loop')]
    public function testForWildcard(): void
    {
        $m = Ast::for();
        $this->assertMatches($m, [
            'for ($i = 0; $i < 10; $i++) {}',
            'for (;;) {}',
        ]);
        $this->assertNotMatches($m, ['while (true) {}', 'foreach ($a as $b) {}']);
    }

    // ─── Try / Catch ──────────────────────────────────────────────────────────

    #[Example('tryCatch() wildcard — matches any try block')]
    public function testTryCatchWildcard(): void
    {
        $m = Ast::tryCatch();
        $this->assertMatches($m, [
            'try {} catch (\Exception $e) {}',
            'try {} catch (\Exception $e) {} finally {}',
        ]);
        $this->assertNotMatches($m, ['if (true) {}', '$x = 1']);
    }

    #[Example('tryCatch with specific catch — matches try/catch(RuntimeException)')]
    public function testTryCatchSpecific(): void
    {
        $m = Ast::tryCatch(
            null,
            Ast::anyList(Ast::catch(Ast::tupleOf(Ast::name('RuntimeException'))))
        );
        $this->assertMatches($m, ['try { $x = 1; } catch (RuntimeException $e) {}']);
        $this->assertNotMatches($m, ['try {} catch (Exception $e) {}']);
    }

    // ─── Switch ──────────────────────────────────────────────────────────────

    #[Example('switch() wildcard — matches any switch statement')]
    public function testSwitchWildcard(): void
    {
        $m = Ast::switch();
        $this->assertMatches($m, [
            'switch ($x) { case 1: break; }',
            'switch ($status) { case "active": return true; default: return false; }',
        ]);
        $this->assertNotMatches($m, ['match ($x) { 1 => true }', 'if ($x === 1) {}']);
    }

    #[Example('switch(variable) — matches switch on a specific variable')]
    public function testSwitchOnVariable(): void
    {
        $m = Ast::switch(Ast::variable('status'));
        $this->assertMatches($m, [
            'switch ($status) { case "active": break; }',
        ]);
        $this->assertNotMatches($m, ['switch ($type) { case "a": break; }']);
    }
}
