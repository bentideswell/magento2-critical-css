<?php
/**
 *
 */
namespace FishPig\CriticalCss\App;

use FishPig\CriticalCss\App\CriticalTags;
use FishPig\CriticalCss\App\PreProcessor\LessPreProcessor;
use FishPig\CriticalCss\App\PreProcessor\CssPreProcessor;
use FishPig\CriticalCss\App\PostProcessor\CriticalCssPostProcessor;
use Exception;

class CriticalCssTest
{
    /**
     *
     */
    const SELECTOR_CRITICAL    = '.selC{color:red;}';
    const SELECTOR_CRITICAL_B  = '.selCB{color:blue;}';
    const SELECTOR_NONCRITICAL = '.selNC{color:green;}';

    /**
     *
     */
    private $lessPreProcessor = null;

    /**
     *
     */
    private $cssPreProcessor = null;

    /**
     *
     */
    private $criticalCssPostProcessor = null;

    /**
     *
     */
    private $criticalTags = null;

    /**
     *
     */
    public function __construct(
        LessPreProcessor $lessPreProcessor,
        CssPreProcessor $cssPreProcessor,
        CriticalCssPostProcessor $criticalCssPostProcessor,
        CriticalTags $criticalTags
    ) {
        $this->lessPreProcessor = $lessPreProcessor;
        $this->cssPreProcessor = $cssPreProcessor;
        $this->criticalCssPostProcessor = $criticalCssPostProcessor;
        $this->criticalTags = $criticalTags;
    }

    /**
     *
     */
    public function doTests(): array
    {
        foreach ([
            'doLocationResolutionTests',
            'doManualTest',
            'doBasicTypeTests',
            'doSingleLocationTests',
            'doMultiLocationTests',
            'doStaticMultiLocationTests',
            'doInputOutputTests'
        ] as $method) {
            $this->$method();
        }

        return ['OK'];
    }

    /**
     *
     */
    private function getCriticalCss(string $input, $location = null, bool $debug = false): string
    {
        $processes = [
            'less-pre-process' => function ($input) {
                return $this->lessPreProcessor->preProcessContent($input);
            },
            'css-pre-process' => function ($input) {
                return $this->cssPreProcessor->preProcessContent($input);
            },
            'css-post-process' => function ($input) use ($location) {
                return $this->criticalCssPostProcessor->postProcessContent($input, $location);
            },
            'clean-whitespace' => function ($input) {
                return str_replace(["\n", ' '], '', $input);
            }
        ];

        if ($debug) {
            echo "\n\n#################\n\n";
            echo 'Location: ' . print_r($location, true) . "\n";
            echo '  Before: ' . trim($input) . "\n\n";
        }

        foreach ($processes as $processId => $process) {
            $input = $process($input);

            if ($debug) {
                echo "After '{$processId}'\n\n" . trim($input) . "\n\n";
            }
        }

        if ($debug) {
            echo "\n#################\n\n";
        }

        return $input;
    }

    /**
     *
     */
    private function doLocationResolutionTests(): void
    {
        $global = CriticalTags::GLOBAL_LOCATION;
        $product = 'product';
        $inputs = [
            [null, [$global]],
            ['', [$global]],
            [$global, [$global]],
            [[null, '', $global], [$global]],
            [$product, [$product]],
            [[$product], [$product]],
            [[null, $product], [$global, $product]],
            [[$global, $product], [$global, $product]],
            [[null, '', $global, $product], [$global, $product]],
            [[$global, $product, null, ''], [$global, $product]]
        ];

        foreach ($inputs as $input) {
            list($input, $expectedOutput) = $input;

            $actualOutput = $this->criticalTags->resolveLocations($input, $expectedOutput);

            if ($actualOutput !== $expectedOutput) {
                echo "Resolved locations error.\n";
                echo "   Input = " . print_r($input, true) . PHP_EOL;
                echo "Expected = " . print_r($expectedOutput, true) . PHP_EOL;
                echo "  Actual = " . print_r($actualOutput, true) . PHP_EOL;
                exit(1);
            }
        }
    }

    /**
     *
     */
    private function doManualTest(): void
    {
    }

    /**
     *
     */
    private function doBasicTypeTests(): void
    {
        $inputs = [
            '' => [
                self::SELECTOR_NONCRITICAL . CriticalTags::CRITICAL_START . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                self::SELECTOR_CRITICAL . CriticalTags::CRITICAL
            ],
            CriticalTags::GLOBAL_LOCATION => [
                CriticalTags::CRITICAL_START . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                self::SELECTOR_CRITICAL . CriticalTags::getCriticalTag(CriticalTags::GLOBAL_LOCATION)
            ],
            'product' => [
                CriticalTags::getCriticalStartTag('product') . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                self::SELECTOR_CRITICAL . CriticalTags::getCriticalTag('product')
            ]
        ];

        foreach ($inputs as $location => $patterns) {
            foreach ($patterns as $input) {
                $criticalCssResult = $this->getCriticalCss($input, $location);

                if (strpos($criticalCssResult, self::SELECTOR_CRITICAL) === false) {
                    throw new Exception("Missing critical CSS on " . __LINE__);
                } elseif (strpos($criticalCssResult, self::SELECTOR_NONCRITICAL) !== false) {
                    throw new Execption("Unexpected non-critical selector.");
                }
            }
        }
    }


    /**
     *
     */
    private function doSingleLocationTests(): void
    {
        $locations = [
            '',
            CriticalTags::GLOBAL_LOCATION,
            'product',
            'cms_page',
            'id_with-1234'
        ];

        $targetSelector = self::SELECTOR_CRITICAL;
        $wrongSelector = self::SELECTOR_NONCRITICAL;

        $inputLocationWrong = 'wrong-input-location';

        foreach ($locations as $inputLocation) {
            $inputs = [
                CriticalTags::getCriticalStartTag($inputLocation) . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP
                . CriticalTags::getCriticalStartTag() . self::SELECTOR_CRITICAL_B . CriticalTags::CRITICAL_STOP,
                $targetSelector . CriticalTags::getCriticalTag($inputLocation)
            ];

            if (!$inputLocation) {
                $inputs[] = CriticalTags::CRITICAL_START . $targetSelector . CriticalTags::CRITICAL_STOP;
                $inputs[] = $targetSelector . CriticalTags::CRITICAL;
                $inputs[] = $targetSelector . CriticalTags::getCriticalTag(CriticalTags::GLOBAL_LOCATION);
            }

            $outputLocation = $inputLocation ?: CriticalTags::GLOBAL_LOCATION;
            $outputLocationWrong = 'wrong-location';

            foreach ($inputs as $input) {
                $criticalCssResult = $this->getCriticalCss($input, $outputLocation);

                if (strpos($criticalCssResult, self::SELECTOR_CRITICAL) === false) {
                    throw new Exception("Missing critical CSS!");
                } elseif (strpos($criticalCssResult, self::SELECTOR_NONCRITICAL) !== false) {
                    throw new Execption("Unexpected non-critical selector.");
                }

                if ($criticalWithWrongOutputLocation = $this->getCriticalCss($input, $outputLocationWrong)) {
                    echo "Unexpected critical output using wrong location.";
                    exit(1);
                }
            }
        }
    }

    /**
     *
     */
    private function doMultiLocationTests(): void
    {
        $globalLocation = '';
        $location = 'product';
        $selectors = [self::SELECTOR_CRITICAL, self::SELECTOR_CRITICAL_B];
        $inputs = [
            [
                $location => '',
                $globalLocation => self::SELECTOR_CRITICAL . CriticalTags::getCriticalTag()
            ],
            [
                $location => self::SELECTOR_CRITICAL . CriticalTags::getCriticalTag($location),
                $globalLocation => self::SELECTOR_CRITICAL_B . CriticalTags::getCriticalTag(),
            ],
            [
                $location => CriticalTags::getCriticalStartTag($location) . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                $globalLocation => CriticalTags::getCriticalStartTag() . self::SELECTOR_CRITICAL_B . CriticalTags::CRITICAL_STOP
            ],
            [
                $location => CriticalTags::getCriticalStartTag($location) . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                $globalLocation => ''
            ]
        ];

        foreach ($inputs as $input) {
            $inputCss = implode("\n", $input);

            $input['wrong-location-no-exist'] = '';

            foreach ($input as $location => $locationInput) {
                $outputCss = $this->getCriticalCss($inputCss, $location);

                if (!$locationInput) {
                    if ($outputCss) {
                        echo "Input was empty but we got output: " . $outputCss . "\n";
                        exit(1);
                    }
                    // Input and output were empty, so we are happy
                } elseif (!$outputCss) {
                    echo "We expected output but found none in " . __LINE__ . PHP_EOL;
                    exit(1);
                } else {
                    foreach ($selectors as $selector) {
                        if (strpos($locationInput, $selector) !== false && strpos($outputCss, $selector) === false) {
                            echo "Input has selector " . $selector . " but this is not in output: " . $outputCss . PHP_EOL;
                            exit(1);
                        } elseif (strpos($locationInput, $selector) === false && strpos($outputCss, $selector) !== false) {
                            echo "Input does not have selector " . $selector . " but this is in output: " . $outputCss . PHP_EOL;
                            exit(1);
                        }
                    }
                }
            }

            // Now test with both keys to check we get all
            $locations = array_keys($input);
            $outputCss = $this->getCriticalCss($inputCss, $locations);

            if (!$outputCss) {
                echo "We expected output but found none in " . __LINE__ . PHP_EOL;
                exit(1);
            } else {
                foreach ($selectors as $selector) {
                    if (strpos($inputCss, $selector) !== false && strpos($outputCss, $selector) === false) {
                        echo "Input has selector " . $selector . " but this is not in output: " . $outputCss . PHP_EOL;
                        exit(1);
                    } elseif (strpos($inputCss, $selector) === false && strpos($outputCss, $selector) !== false) {
                        echo "Input does not have selector " . $selector . " but this is in output: " . $outputCss . PHP_EOL;
                        exit(1);
                    }
                }
            }
        }
    }

    /**
     *
     */
    private function doStaticMultiLocationTests(): void
    {
        $glue = "";
        $inputCss = implode(
            $glue,
            [
                CriticalTags::getCriticalStartTag('product') . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                CriticalTags::getCriticalStartTag() . self::SELECTOR_CRITICAL_B . CriticalTags::CRITICAL_STOP,
                self::SELECTOR_NONCRITICAL
            ]
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL,
            $this->getCriticalCss($inputCss, ['product']),
            'Critical selector for single location. Should not include global.'
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL_B,
            $this->getCriticalCss($inputCss),
            'Critical selector b with no (global) location.'
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL . $glue . self::SELECTOR_CRITICAL_B,
            $this->getCriticalCss($inputCss, ['', 'product']),
            'Critical selector with 1 location and implied global'
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL . $glue . self::SELECTOR_CRITICAL_B,
            $this->getCriticalCss($inputCss, [CriticalTags::GLOBAL_LOCATION, 'product']),
            'Critical selector with 1 location and implied global B'
        );
    }

    /**
     *
     */
    private function doInputOutputTests(): void
    {
        $inputs = [
            "/* @critical:start */@font-face{font-weight:@_weight_normal;font-style:normal;}/* @critical:stop */" => "@font-face{font-weight:@_weight_normal;font-style:normal;}",
            "@font-face{font-weight:@_weight_normal;/* @critical */ font-style:normal;}" => "@font-face{font-weight:@_weight_normal;}",
            "& when (@media-common = true) {
    /* @critical:start */
    *, *::before, *::after {
        .lib-css(box-sizing, @-all--before-after__box-sizing);
    }

    * {
        .lib-css(margin, @-all__margin);
        .lib-css(padding, @-all__padding);
    }

    html, body {
        .lib-css(height, @html-body__height);
    }

    html {
        .lib-css(-ms-text-size-adjust, @html__-ms-text-size-adjust);
    }

    body {
        .lib-css(-moz-osx-font-smoothing, @body__-moz-osx-font-smoothing);
    }
    /* @critical:stop */
}
" => "& when (@media-common = true) {
    *, *::before, *::after {
        .lib-css(box-sizing, @-all--before-after__box-sizing);
    }

    * {
        .lib-css(margin, @-all__margin);
        .lib-css(padding, @-all__padding);
    }

    html, body {
        .lib-css(height, @html-body__height);
    }

    html {
        .lib-css(-ms-text-size-adjust, @html__-ms-text-size-adjust);
    }

    body {
        .lib-css(-moz-osx-font-smoothing, @body__-moz-osx-font-smoothing);
    }
}
",
".selector{content: \"{..}\"; " . CriticalTags::CRITICAL . "}" => ".selector{content: \"{..}\";}",
CriticalTags::CRITICAL_START . "&[data-percent=\"@{index}\"] {color:red;}" . CriticalTags::CRITICAL_STOP => '&[data-percent="@{index}"]{color:red;}'

        ];

        foreach ($inputs as $input => $expectedOutput) {
            $actualOutput = $this->getCriticalCss($input);

            $this->doVariablesMatch(
                str_replace(["\n", ' '], '', $expectedOutput),
                str_replace(["\n", ' '], '', $actualOutput),
                sprintf(
                    "Font face test failed with:\n   Input = %s\n  Actual = %s\nExpected = %s",
                    $input,
                    $actualOutput,
                    $expectedOutput
                )
            );
        }
    }

    /**
     *
     */
    private function doVariablesMatch(string $a, string $b, string $msg): void
    {
        if ($a !== $b) {
            throw new \RuntimeException($msg);
        }
    }
}