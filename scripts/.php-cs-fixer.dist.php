<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/bin')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/test')
    ->in(__DIR__.'/tests')
    ->name('*.php')
    ->append([
        __FILE__,
    ])
;

$config = new PhpCsFixer\Config();
$config
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'no_unused_imports' => true,
     ])
;

return $config;
