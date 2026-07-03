<?php

use Fleet\AstMatcher\Facade\Ast;
use Fleet\AstMatcher\Testing\Attributes\Example;
use Fleet\AstMatcher\Testing\AstTestRunner;

class DeclarationTests extends AstTestRunner
{
    // ─── Function Declaration ─────────────────────────────────────────────────

    #[Example('functionDeclaration() wildcard — matches any function definition')]
    public function testFuncDeclWildcard(): void
    {
        $m = Ast::functionDeclaration();
        $this->assertMatches($m, [
            'function foo() {}',
            'function bar($x, $y) { return $x + $y; }',
        ]);
        $this->assertNotMatches($m, ['$fn = function() {};', 'class Foo {}']);
    }

    #[Example('functionDeclaration(name("helper")) — matches a specific named function')]
    public function testFuncDeclByName(): void
    {
        $m = Ast::functionDeclaration(Ast::name('helper'));
        $this->assertMatches($m, ['function helper() {}', 'function helper($x) { return $x; }']);
        $this->assertNotMatches($m, ['function other() {}']);
    }

    #[Example('functionDeclaration with params — matches function with typed param')]
    public function testFuncDeclWithParam(): void
    {
        $m = Ast::functionDeclaration(null, [Ast::param('id', 'int')]);
        $this->assertMatches($m, ['function find(int $id) {}', 'function load(int $id) { return $id; }']);
        $this->assertNotMatches($m, ['function find($id) {}', 'function find(string $id) {}']);
    }

    // ─── Class Declaration ────────────────────────────────────────────────────

    #[Example('classDeclaration() wildcard — matches any class definition')]
    public function testClassDeclWildcard(): void
    {
        $m = Ast::classDeclaration();
        $this->assertMatches($m, [
            'class Foo {}',
            'class Bar extends Baz {}',
            'class MyModel extends Model {}',
        ]);
        $this->assertNotMatches($m, ['interface Foo {}', 'trait Foo {}']);
    }

    #[Example('classDeclaration(name, extends) — matches a class that extends specific parent')]
    public function testClassDeclWithExtends(): void
    {
        $m = Ast::classDeclaration(null, Ast::name('Model'));
        $this->assertMatches($m, [
            'class User extends Model {}',
            'class Post extends Model {}',
        ]);
        $this->assertNotMatches($m, ['class Foo {}', 'class Foo extends Controller {}']);
    }

    // ─── Class Method ─────────────────────────────────────────────────────────

    #[Example('classMethod() wildcard — matches any class method within a class')]
    public function testClassMethodWildcard(): void
    {
        // classMethod() matches Stmt\ClassMethod nodes — test via classDeclaration() body
        $m = Ast::classDeclaration(null, null, Ast::anyList(Ast::classMethod()));
        $this->assertMatches($m, [
            'class Foo { public function bar() {} }',
            'class Foo { private function baz(): void {} }',
        ]);
    }

    #[Example('classMethod(name("boot")) — matches methods named boot')]
    public function testClassMethodByName(): void
    {
        $m = Ast::classDeclaration(null, null, Ast::anyList(Ast::classMethod(Ast::name('boot'))));
        $this->assertMatches($m, [
            'class Foo { public static function boot() {} }',
            'class Foo { protected function boot(): void { parent::boot(); } }',
        ]);
        $this->assertNotMatches($m, ['class Foo { public function setUp() {} }']);
    }

    #[Example('classMethod(static: true) — matches only static methods')]
    public function testClassMethodStatic(): void
    {
        $m = Ast::classDeclaration(null, null, Ast::anyList(Ast::classMethod(null, null, null, true)));
        $this->assertMatches($m, ['class Foo { public static function create() {} }']);
        $this->assertNotMatches($m, ['class Foo { public function create() {} }']);
    }

    // ─── Class Property ──────────────────────────────────────────────────────

    #[Example('classProperty() wildcard — matches any property declaration within a class')]
    public function testClassPropertyWildcard(): void
    {
        $m = Ast::classDeclaration(null, null, Ast::anyList(Ast::classProperty()));
        $this->assertMatches($m, [
            'class Foo { public $bar; }',
            'class Foo { protected string $name = "default"; }',
        ]);
    }

    #[Example('classProperty(name("table")) — matches $table property')]
    public function testClassPropertyByName(): void
    {
        $m = Ast::classDeclaration(null, null, Ast::anyList(Ast::classProperty(Ast::name('table'))));
        $this->assertMatches($m, [
            'class Foo { protected $table; }',
            'class Foo { public string $table = "users"; }',
        ]);
        $this->assertNotMatches($m, ['class Foo { protected $fillable; }']);
    }

    // ─── Trait ────────────────────────────────────────────────────────────────

    #[Example('trait() wildcard — matches any trait definition')]
    public function testTraitWildcard(): void
    {
        $m = Ast::trait();
        $this->assertMatches($m, ['trait Foo {}', 'trait HasTimestamps { public function touch() {} }']);
        $this->assertNotMatches($m, ['class Foo {}', 'interface Foo {}']);
    }

    #[Example('trait(name) — matches a specific trait by name')]
    public function testTraitByName(): void
    {
        $m = Ast::trait(Ast::name('SoftDeletes'));
        $this->assertMatches($m, ['trait SoftDeletes {}', 'trait SoftDeletes { public function delete() {} }']);
        $this->assertNotMatches($m, ['trait HasTimestamps {}']);
    }

    // ─── Interface ────────────────────────────────────────────────────────────

    #[Example('interface() wildcard — matches any interface definition')]
    public function testInterfaceWildcard(): void
    {
        $m = Ast::interface();
        $this->assertMatches($m, [
            'interface Foo {}',
            'interface Repository { public function find(int $id); }',
        ]);
        $this->assertNotMatches($m, ['class Foo {}', 'trait Foo {}']);
    }

    // ─── Enum ─────────────────────────────────────────────────────────────────

    #[Example('enum() wildcard — matches any enum definition')]
    public function testEnumWildcard(): void
    {
        $m = Ast::enum();
        $this->assertMatches($m, ['enum Status {}', 'enum Color: string { case Red = "red"; }']);
        $this->assertNotMatches($m, ['class Status {}']);
    }

    #[Example('enumCase(name) — matches a specific enum case within an enum')]
    public function testEnumCaseByName(): void
    {
        $m = Ast::enum(null, null, Ast::anyList(Ast::enumCase(Ast::name('Active'))));
        $this->assertMatches($m, [
            'enum Color { case Active; }',
            'enum Status: int { case Active = 1; }',
        ]);
        $this->assertNotMatches($m, ['enum Color { case Inactive; }']);
    }

    // ─── Use Statement ────────────────────────────────────────────────────────

    #[Example('use() wildcard — matches any use statement')]
    public function testUseWildcard(): void
    {
        $m = Ast::use();
        $this->assertMatches($m, [
            'use App\\Models\\User',
            'use Illuminate\\Support\\Str',
            'use App\\Traits\\HasTimestamps as Timestamps',
        ]);
        $this->assertNotMatches($m, ['$x', 'namespace App\\Models']);
    }

    // ─── traitUse ─────────────────────────────────────────────────────────────

    #[Example('traitUse() — matches a use-trait statement inside a class')]
    public function testTraitUseInClass(): void
    {
        $m = Ast::classDeclaration(null, null, Ast::anyList(Ast::traitUse()));
        $this->assertMatches($m, [
            'class Foo { use SoftDeletes; }',
            'class Foo { use HasTimestamps, SoftDeletes; }',
        ]);
        $this->assertNotMatches($m, ['class Foo {}']);
    }
}
