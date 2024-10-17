<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$copyRightHeader = <<<EOF
This file is part of the TYPO3 CMS project.

(c) Simon Schaufelberger

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.

For the full copyright and license information, please read the
LICENSE file that was distributed with this source code.

The TYPO3 project - inspiring people to share!
EOF;

return (new Config())
    ->setFinder(
        (new Finder())
            ->ignoreVCSIgnored(true)
            ->in([
                __DIR__ . '/../../Classes/',
                __DIR__ . '/../../Configuration/',
                __DIR__ . '/../../Tests/',
            ])
            // warning: these are relative paths!
            ->notPath([
            ])
    )
    ->setCacheFile('Build/php-cs-fixer/.php-cs-fixer.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PSR2' => true,
        'array_indentation' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'blank_line_after_opening_tag' => true,
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'cast_spaces' => [
            'space' => 'none',
        ],
        'compact_nullable_typehint' => true,
        // @todo: Can be dropped once we enable @PER-CS2.0
        'concat_space' => [
            'spacing' => 'one',
        ],
        'declare_equal_normalize' => [
            'space' => 'none',
        ],
        'declare_parentheses' => true,
        'dir_constant' => true,
        // @todo: Can be dropped once we enable @PER-CS2.0
        'function_declaration' => [
            'closure_fn_spacing' => 'none',
        ],
        'function_typehint_space' => true,
        'function_to_constant' => [
            'functions' => ['get_called_class', 'get_class', 'get_class_this', 'php_sapi_name', 'phpversion', 'pi'],
        ],
        'type_declaration_spaces' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'lowercase_cast' => true,
        'list_syntax' => [
            'syntax' => 'short',
        ],
        // @todo: Can be dropped once we enable @PER-CS2.0
        'method_argument_space' => true,
        'modernize_strpos' => true,
        'modernize_types_casting' => true,
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_null_property_initialization' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_nullsafe_operator' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'php_unit_construct' => [
            'assertions' => ['assertEquals', 'assertSame', 'assertNotEquals', 'assertNotSame'],
        ],
        'php_unit_mock_short_will_return' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
        'single_quote' => true,
        'single_space_around_construct' => true,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        // @todo: Can be dropped once we enable @PER-CS2.0
        'single_line_empty_body' => true,
        'single_trait_insert_per_statement' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays'],
        ],
        'whitespace_after_comma_in_array' => [
            'ensure_single_space' => true,
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'header_comment' => [
            'header' => $copyRightHeader,
            'comment_type' => 'comment',
            'location' => 'after_declare_strict',
            'separate' => 'both',
        ],
    ]);
