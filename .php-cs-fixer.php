<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/public')
    ->exclude(['vendor', 'var']);

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setIndent("    ")
    ->setLineEnding("\n")
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@PHP84Migration' => true, 
        'declare_strict_types' => true,

        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,

        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],

        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,

        
    ])
    ->setFinder($finder);
