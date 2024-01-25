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
    const SELECTOR_CRITICAL    = '.selC { color: red;   }';
    const SELECTOR_CRITICAL_B  = '.selCB { color: blue;  }';
    const SELECTOR_NONCRITICAL = '.selNC { color: green; }';

    /**
     *
     */
    const TYPE_CRITICAL = 'critical';
    const TYPE_NONCRITICAL = 'noncritical';
    const TYPE_MIXED = 'mixed';

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
    private function getCriticalCss(string $input, $location = null): string
    {
#        echo __LINE__ . ': ' . $input  . "\n\n\n";
        $input = $this->lessPreProcessor->preProcessContent($input);
        $input = $this->cssPreProcessor->preProcessContent($input);;
#        echo __LINE__ . ': ' . $input  . "\n\n\n";
        $input = $this->criticalCssPostProcessor->postProcessContent($input, $location);
#        echo __LINE__ . ': ' . $input  . "\n\n\n";
#echo __LINE__ . ': ' . $input  . "\n\n\n";
#exit;
        return $input;
    }

    /**
     *
     */
    public function doTests(): array
    {
        $output = [];

        foreach ([
#            'doBasicTypeTests',
#            'doSingleLocationTests',
#            'doMultiLocationTests',
#            'doStaticMultiLocationTests',
            'doInputOutputTests'
        ] as $method) {
            $this->$method();
        }

        return ['OK'];
    }

    /**
     *
     */
    private function doBasicTypeTests(): void
    {
        $inputs = [
            '' => [
                self::SELECTOR_NONCRITICAL . CriticalTags::CRITICAL_START . '%s' . CriticalTags::CRITICAL_STOP,
                '%s' . CriticalTags::CRITICAL
            ],
            CriticalTags::GLOBAL_LOCATION => [
                CriticalTags::CRITICAL_START . '%s' . CriticalTags::CRITICAL_STOP,
                '%s' . CriticalTags::CRITICAL
            ],
            'product' => [
                CriticalTags::getCriticalStartTag('product') . '%s' . CriticalTags::CRITICAL_STOP,
                '%s' . CriticalTags::getCriticalTag('product')
            ]
        ];

        foreach ($inputs as $location => $patterns) {
            foreach ($patterns as $pattern) {
                $input = sprintf($pattern, self::SELECTOR_CRITICAL);
                $criticalCssResult = $this->getCriticalCss($input, $location);

                if (strpos($criticalCssResult, self::SELECTOR_CRITICAL) === false) {
                    echo $location . PHP_EOL;
                    echo $input . PHP_EOL;
                    echo $criticalCssResult;exit;
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
            'global',
            'product',
            'cms_page',
            'id_with-1234'
        ];

        $targetSelector = self::SELECTOR_CRITICAL;
        $wrongSelector = self::SELECTOR_NONCRITICAL;

        $inputLocationWrong = 'wrong-input-location';

        foreach ($locations as $inputLocation) {
            $inputs = [
                CriticalTags::getCriticalStartTag($inputLocation) . $targetSelector . CriticalTags::CRITICAL_STOP,
                $targetSelector . CriticalTags::getCriticalTag($inputLocation)
            ];

            if (!$inputLocation) {
                $inputs[] = CriticalTags::CRITICAL_START . $targetSelector . CriticalTags::CRITICAL_STOP;
                $inputs[] = $targetSelector . CriticalTags::CRITICAL;
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

echo $outputLocationWrong . PHP_EOL;
echo $input . PHP_EOL;
                    echo $criticalWithWrongOutputLocation;exit;
                    echo "Unexpected critical output using wrong location.";
                    exit(1);
                }

                if ($inputLocation) {
                    $modifiedInput = $input . str_replace($outputLocation, $outputLocationWrong, str_replace($targetSelector, $wrongSelector, $input));

                    $criticalCssWithWrongInputLocation = trim(
                        $this->getCriticalCss($modifiedInput, $outputLocationWrong)
                    );

                    $hasError = strpos($criticalCssWithWrongInputLocation, $wrongSelector) === false
                                || strpos($criticalCssWithWrongInputLocation, $targetSelector) !== false;

                    if ($hasError) {
                        echo "Unexpected result with modified input.";
                        exit(1);
                    }
                }
            }
        }
    }

    /**
     *
     */
    private function doMultiLocationTests(): void
    {
        $globalLocation = CriticalTags::GLOBAL_LOCATION;
        $location = 'product';
        $critical = self::TYPE_CRITICAL;
        $nonCritical = self::TYPE_NONCRITICAL;

        $inputs = [
            [
                $critical => [
                    $globalLocation => self::SELECTOR_CRITICAL . CriticalTags::getCriticalTag() . self::SELECTOR_CRITICAL_B . CriticalTags::getCriticalTag(),
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL,
                ]
            ],
            [
                $critical => [
                    $location => self::SELECTOR_CRITICAL . CriticalTags::getCriticalTag($location),
                    $globalLocation => self::SELECTOR_CRITICAL_B . CriticalTags::getCriticalTag(),
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL
                ]
            ],
            [
                $critical => [
                    $location => CriticalTags::getCriticalStartTag($location) . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP,
                    $globalLocation => CriticalTags::getCriticalStartTag() . self::SELECTOR_CRITICAL_B . CriticalTags::CRITICAL_STOP
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL
                ]
            ],
            [
                $critical => [
                    $location => CriticalTags::getCriticalStartTag($location) . self::SELECTOR_CRITICAL . CriticalTags::CRITICAL_STOP
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL
                ]
            ]
        ];

        foreach ($inputs as $input) {
            $inputCss = '';
            foreach ($input as $type => $lines) {
                $inputCss .= implode('', (array)$lines);
            }

            $shouldHaveCritical = !empty($input[$critical]);

            $criticalResults = array_filter(array_unique([
                $this->getCriticalCss($inputCss, ['', $location]),
                $this->getCriticalCss($inputCss, [$globalLocation, $location])
            ]));

            if (!$shouldHaveCritical) {
                echo 'should not have critical so add code to test';
                exit;
            }

            if (count($criticalResults) > 1) {
                throw new \Exception(
                    sprintf(
                        "CSS results differ when using empty string and global as location.\n\nInput = %s\nOutput = %s\n\n",
                        $inputCss,
                        print_r($criticalResults, true)
                    )
                );
            } elseif (count($criticalResults) === 0) {
                throw new \Exception(
                    sprintf(
                        "No critical CSS found from input: %s",
                        $inputCss
                    )
                );
            }

            $criticalCssResult = $criticalResults[0];
            $criticalCssBuffer = $criticalCssResult;

            foreach ([self::SELECTOR_CRITICAL, self::SELECTOR_CRITICAL_B] as $criticalSelector) {
                $inputCssContainsCriticalSelector = strpos($criticalCssResult, $criticalSelector) !== false;
                $outputCriticalCssContainsCriticalSelector = strpos($criticalCssResult, $criticalSelector) !== false;

                if ($inputCssContainsCriticalSelector !== $outputCriticalCssContainsCriticalSelector) {
                    throw new \RuntimeException(
                        sprintf(
                            "Inconsistency with critical CSS selector '%s'.\nExpected = %s\nActual = %s\nInput = %s\nOutput = %s",
                            $criticalSelector,
                            (int)$inputCssContainsCriticalSelector,
                            (int)$outputCriticalCssContainsCriticalSelector,
                            $inputCss,
                            $criticalCssResult
                        )
                    );
                }

                $criticalCssBuffer = trim(str_replace($criticalSelector, '', $criticalCssBuffer));
            }

            if ($criticalCssBuffer) {
                throw new \RuntimeException(
                    sprintf(
                        "Critical CSS not all removed.\nInput = %s\nOutput = %s",
                        $inputCss,
                        $criticalCssBuffer
                    )
                );
            }
        }
    }

    /**
     *
     */
    private function doStaticMultiLocationTests(): void
    {
        $glue = "\n";
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
            'Critical selector with 1 location and implied global'
        );
    }

    /**
     *
     */
    private function doInputOutputTests(): void
    {
        $inputs = [
#            "/* @critical:start */@font-face{font-weight:@_weight_normal;font-style:normal;}/* @critical:stop */" => "@font-face{font-weight:@_weight_normal;font-style:normal;}",
#            "@font-face{font-weight:@_weight_normal;/* @critical */ font-style:normal;}" => "@font-face{font-weight:@_weight_normal;}",
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
"
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