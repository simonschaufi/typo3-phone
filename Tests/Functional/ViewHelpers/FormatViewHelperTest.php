<?php

declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class FormatViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function formatDataProvider(): array
    {
        return [
            'international' => [
                '<phone:format value="tel:+3212345678" />',
                '+32 12 34 56 78',
            ],
            'national' => [
                '<phone:format value="tel:+3212345678" format="national" />',
                '012 34 56 78',
            ],
            'local' => [
                '<phone:format value="+49(0)12-44 614038" />',
                '+49 1244614038',
            ],
            'mobile' => [
                '<phone:format value="tel:+32470123456" />',
                '+32 470 12 34 56',
            ],
            'mobile national' => [
                '<phone:format value="tel:+32470123456" format="national" />',
                '0470 12 34 56',
            ],
            'US number' => [
                '<phone:format value="tel:+12015550123" />',
                '+1 201-555-0123',
            ],
            'US number national' => [
                '<phone:format value="tel:+12015550123" format="national" />',
                '(201) 555-0123',
            ],
            'US region with local number' => [
                '<phone:format value="2015550123" defaultRegion="US" />',
                '+1 201-555-0123',
            ],
            'US region with local number national' => [
                '<phone:format value="2015550123" defaultRegion="US" format="national" />',
                '(201) 555-0123',
            ],
            'BE region with local number' => [
                '<phone:format value="0470123456" defaultRegion="BE" />',
                '+32 470 12 34 56',
            ],
            'BE region with local number national' => [
                '<phone:format value="0470123456" defaultRegion="BE" format="national" />',
                '0470 12 34 56',
            ],
            'nested notation' => [
                '<phone:format>+3212345678</phone:format>',
                '+32 12 34 56 78',
            ],
            'e164' => [
                '<phone:format value="+32 12 34 56 78" format="e164" />',
                '+3212345678',
            ],
            'e164 with tel prefix' => [
                '<phone:format value="tel:+32 12/34.56.78" format="e164" />',
                '+3212345678',
            ],
            'e164 with defaultRegion' => [
                '<phone:format value="+32 12 34 56 78" format="e164" defaultRegion="BE" />',
                '+3212345678',
            ],
            'rfc3966' => [
                '<phone:format value="+3212345678" format="rfc3966" />',
                '3212345678',
            ],
            'rfc3966 with tel prefix' => [
                '<phone:format value="tel:+3212345678" format="rfc3966" />',
                'tel:+32-12-34-56-78',
            ],
        ];
    }

    #[DataProvider('formatDataProvider')]
    #[Test]
    public function format(string $src, string $expected): void
    {
        $src = '<html xmlns:phone="http://typo3.org/ns/SimonSchaufi/TYPO3Phone/ViewHelpers" data-namespace-typo3-fluid="true">' . $src . '</html>';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($src);
        self::assertSame($expected, (string)(new TemplateView($context))->render());
    }
}
