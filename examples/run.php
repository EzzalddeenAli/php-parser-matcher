<?php

/**
 * Master test runner — executes all example suites.
 *
 * Usage:
 *   php examples/run.php
 *   php examples/run.php --suite=03    # run only suites matching "03"
 */

require __DIR__ . '/../vendor/autoload.php';

// ─── Load all suite files ────────────────────────────────────────────────────

$suiteDir   = __DIR__ . '/suites';
$filter     = null;

foreach ($_SERVER['argv'] as $arg) {
    if (str_starts_with($arg, '--suite=')) {
        $filter = substr($arg, strlen('--suite='));
    }
}

$files = glob($suiteDir . '/*.php') ?: [];
sort($files);

$classes = [];
foreach ($files as $file) {
    if ($filter !== null && !str_contains(basename($file), $filter)) {
        continue;
    }
    require_once $file;
    // Derive class name from filename: "01_ScalarTests.php" → "ScalarTests"
    $base = basename($file, '.php');
    $name = preg_replace('/^\d+_/', '', $base);
    if (class_exists($name)) {
        $classes[] = $name;
    }
}

if (empty($classes)) {
    echo "No suites found" . ($filter ? " matching '$filter'" : '') . ".\n";
    exit(0);
}

use Fleet\AstMatcher\Testing\AstTestRunner;

AstTestRunner::runAll(...$classes);
