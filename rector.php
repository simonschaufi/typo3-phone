<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\FileProcessor\Composer\Rector\ExtensionComposerRector;
use Ssch\TYPO3Rector\FileProcessor\TypoScript\Rector\v10\v0\ExtbasePersistenceTypoScriptRector;
use Ssch\TYPO3Rector\Rector\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\Rector\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $parameters = $rectorConfig->parameters();

    $rectorConfig->import(Typo3LevelSetList::UP_TO_TYPO3_12);

    // In order to have a better analysis from phpstan we teach it here some more things
    $rectorConfig->phpstanConfig(Typo3Option::PHPSTAN_FOR_RECTOR_PATH);

    // FQN classes are not imported by default. If you don't do it manually after every Rector run, enable it by:
    $rectorConfig->importNames();

    // this will not import root namespace classes, like \DateTime or \Exception
    $rectorConfig->disableImportShortClasses();

    // Define your target version which you want to support
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    // If you have an editorconfig and changed files should keep their format enable it here
    $parameters->set(Option::ENABLE_EDITORCONFIG, true);

    // If you only want to process one/some TYPO3 extension(s), you can specify its path(s) here.
    // If you use the option --config change __DIR__ to getcwd()
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Tests',
        __DIR__ . '/composer.json',
        __DIR__ . '/ext_emconf.php',
    ]);

    // Add some general TYPO3 rules
    $rectorConfig->rule(ConvertImplicitVariablesToExplicitGlobalsRector::class);
    $rectorConfig->ruleWithConfiguration(ExtEmConfRector::class, [
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => ['createDirs', 'uploadfolder'],
    ]);
    $rectorConfig->ruleWithConfiguration(ExtensionComposerRector::class, [
        ExtensionComposerRector::TYPO3_VERSION_CONSTRAINT => '^11.5',
    ]);
    $rectorConfig->services()->remove(ExtbasePersistenceTypoScriptRector::class);
};
