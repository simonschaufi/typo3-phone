<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\SingleLineEmptyBodyFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/../../Build',
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
        __DIR__ . '/../../Tests',
        __DIR__ . '/../../ext_emconf.php',
    ]);

    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::ARRAY,
        SetList::COMMON,
        SetList::COMMENTS,
        SetList::CONTROL_STRUCTURES,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::SPACES,
    ]);

    $ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
        'annotations' => ['author', 'package', 'group'],
    ]);

    $ecsConfig->ruleWithConfiguration(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
    ]);

    $ecsConfig->ruleWithConfiguration(CastSpacesFixer::class, [
        'space' => 'none',
    ]);

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => <<<EOF
This file is part of the TYPO3 CMS project.

(c) Simon Schaufelberger

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.

For the full copyright and license information, please read the
LICENSE file that was distributed with this source code.

The TYPO3 project - inspiring people to share!
EOF
    ]);

    // Rules that are not in a set
    $ecsConfig->rule(OperatorLinebreakFixer::class);
    $ecsConfig->rule(SingleLineEmptyBodyFixer::class);

    $ecsConfig->skip([
        LineLengthFixer::class,
        DeclareStrictTypesFixer::class => [
            __DIR__ . '/../../ext_emconf.php',
        ],
        PhpUnitMethodCasingFixer::class,
        OrderedClassElementsFixer::class,
        MethodChainingIndentationFixer::class => [
            __DIR__ . '/../../Tests/*',
        ],
        MethodChainingNewlineFixer::class => [
            __DIR__ . '/../../Tests/*',
        ],
        ArrayListItemNewlineFixer::class => [
            __DIR__ . '/../../Tests/*',
        ],
        ArrayOpenerAndCloserNewlineFixer::class => [
            __DIR__ . '/../../Tests/*',
        ],
        ArrayIndentationFixer::class => [
            __DIR__ . '/../../Tests/*',
        ],
        HeaderCommentFixer::class => [
            __DIR__ . '/../../ext_emconf.php',
            __DIR__ . '/../../Build',
        ],
    ]);
};
