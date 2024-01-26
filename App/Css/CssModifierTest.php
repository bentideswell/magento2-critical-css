<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\Css;

use FishPig\CriticalCss\App\Css\CssModifier;

class CssModifierTest
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
    private $cssModifier = null;

    /**
     *
     */
    public function __construct(CssModifier $cssModifier)
    {
        $this->cssModifier = $cssModifier;
    }

    /**
     *
     */
    public function doTests(): array
    {
        $output = [];

        foreach ([
            'doManualTests',
            'doBasicTypeTests',
            'doSingleLocationTests',
            'doMultiLocationTests',
            'doStaticMultiLocationTests'
        ] as $method) {
            $output = array_merge($output, $this->$method());
        }

        return ['OK'];
    }

    private function doManualTests(): void
    {
        $input = "//
//
//
@std-margin-bottom: 2rem;
@std-margin-top: 4rem;

& when (@media-common = true) {
    .std {
        line-height: 1.8em;

        & > *:last-child {
            margin-bottom: 0;
        }

        h2 {
            margin-top: @std-margin-bottom * 2;
            margin-bottom: @std-margin-bottom;
        }

        h3, h4, h5 {
            margin-top: @std-margin-bottom;
            margin-bottom: @std-margin-bottom * .5;
        }

        p {
            margin-bottom: @std-margin-bottom;

            & + h2, & + h3, & + h4, & + h5 {
                margin-top: @std-margin-top;
            }
        }

        /* @critical:start */
        & ul.bullets {
            list-style-position: outside;
            margin-bottom: @std-margin-bottom;
            margin-left: 30px;
            & li {
                margin-bottom: 2px;
                padding-left: 10px;

                &::marker {
                    content: \"{..}\";
                    font-weight: bold;
                }

                &:last-child {
                    margin-bottom: 0;
                }
            }
        }
        /* @critical:stop */

        & ul.keypoints,
        & ul.pts {
            display: grid;
            grid-gap: @std-margin-bottom;
            margin: @std-margin-top auto @std-margin-bottom;
        }
    }
}

.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__s) {
    .std {
        & .keypoints {
            grid-template-columns: repeat(1, 1fr);
        }
    }
}


.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    .section.text {
        & .keypoints {
            grid-template-columns: repeat(2, 1fr);
        }
    }
}

.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .std {
        & .keypoints {
            grid-template-columns: repeat(2, 1fr);
        }
    }
}


.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__l) {
    .std {
        & .keypoints {

        }
    }
}
";

        echo $this->cssModifier->getCriticalCss($input, ['global', 'product']);
        exit;
    }
    /**
     *
     */
    private function doBasicTypeTests(): array
    {
        $inputs = [
            self::TYPE_NONCRITICAL => [
                '' => ['%s']
            ],
            self::TYPE_CRITICAL => [
                '' => [
                    CssModifier::CRITICAL_START . '%s' . CssModifier::CRITICAL_STOP,
                    '%s' . CssModifier::CRITICAL
                ],
                CssModifier::GLOBAL_LOCATION => [
                    CssModifier::CRITICAL_START . '%s' . CssModifier::CRITICAL_STOP,
                    '%s' . CssModifier::CRITICAL
                ],
                'product' => [
                    CssModifier::getCriticalStartTag('product') . '%s' . CssModifier::CRITICAL_STOP,
                    '%s' . CssModifier::getCriticalTag('product')
                ]
            ]
        ];

        $output = $this->initTestOuput(__METHOD__);

        foreach ($inputs as $type => $groupedPatterns) {
            foreach ($groupedPatterns as $location => $patterns) {
                foreach ($patterns as $pattern) {
                    $input = sprintf(
                        $pattern,
                        $type === self::TYPE_CRITICAL ? self::SELECTOR_CRITICAL : self::SELECTOR_NONCRITICAL
                    );

                    $critical = trim($this->cssModifier->getCriticalCss($input, $location));
                    $nonCritical = $this->removeComments(trim($this->cssModifier->getNonCriticalCss($input)));
                    $output = $this->addResultToOutput($output, $input, $critical, $nonCritical);

                    $this->assertTypedResultValid($type, $input, $critical, $nonCritical);

                    $output[] = "\n";
                }
            }
        }

        return $output;
    }

    /**
     *
     */
    private function doSingleLocationTests(): array
    {
        $locations = [
            '',
            'global',
            'product',
            'cms_page',
            'id_with-1234'
        ];

        $output = $this->initTestOuput(__METHOD__);
        $targetSelector = self::SELECTOR_CRITICAL;
        $wrongSelector = self::SELECTOR_NONCRITICAL;

        $inputLocationWrong = 'wrong-input-location';

        foreach ($locations as $inputLocation) {
            $inputs = [
                CssModifier::getCriticalStartTag($inputLocation) . $targetSelector . CssModifier::CRITICAL_STOP,
                $targetSelector . CssModifier::getCriticalTag($inputLocation)
            ];

            if (!$inputLocation) {
                $inputs[] = CssModifier::CRITICAL_START . $targetSelector . CssModifier::CRITICAL_STOP;
                $inputs[] = $targetSelector . CssModifier::CRITICAL;
            }

            $outputLocation = $inputLocation ?: CssModifier::GLOBAL_LOCATION;
            $outputLocationWrong = 'wrong-location';

            foreach ($inputs as $input) {
                $critical = trim($this->cssModifier->getCriticalCss($input, $outputLocation));
                $nonCritical = $this->removeComments(trim($this->cssModifier->getNonCriticalCss($input)));
                $output = $this->addResultToOutput($output, $input, $critical, $nonCritical);
                $output[] = ' Location: ' . $outputLocation;

                $this->assertTypedResultValid(self::TYPE_CRITICAL, $input, $critical, $nonCritical);

                $criticalWithWrongOutputLocation = trim($this->cssModifier->getCriticalCss($input, $outputLocationWrong));

                if ($criticalWithWrongOutputLocation) {
                    throw new \RuntimeException(
                        sprintf(
                            'Css error with critical CSS and wrong location. Location was "%s" but we gave "%s". CSS should be empty but output was "%s"',
                            $outputLocation,
                            $outputLocationWrong,
                            $criticalWithWrongOutputLocation
                        )
                    );
                }

                if ($inputLocation) {
                    $modifiedInput = $input . str_replace($outputLocation, $outputLocationWrong, str_replace($targetSelector, $wrongSelector, $input));

                    $criticalCssWithWrongInputLocation = trim(
                        $this->cssModifier->getCriticalCss($modifiedInput, $outputLocationWrong)
                    );

                    $output[] = '';
                    $output = $this->addResultToOutput($output, $modifiedInput, $criticalCssWithWrongInputLocation, '');
                    $output[] = ' Location: ' . $outputLocationWrong;

                    $hasError = strpos($criticalCssWithWrongInputLocation, $wrongSelector) === false
                                || strpos($criticalCssWithWrongInputLocation, $targetSelector) !== false;

                    if ($hasError) {
                        throw new \RuntimeException(
                            sprintf(
                                "CSS error with critical CSS and wrong input location.\n\n    Expected = %s\nNot Expected = %s\n    Location = %s\n       Input = %s\n      Output = %s",
                                $wrongSelector,
                                $targetSelector,
                                $outputLocationWrong,
                                $modifiedInput,
                                $criticalCssWithWrongInputLocation
                            )
                        );
                    }
                }

                $output[] = "\n";
            }
        }

        return $output;
    }

    /**
     *
     */
    private function doMultiLocationTests(): array
    {
        $output = $this->initTestOuput(__METHOD__);

        $globalLocation = CssModifier::GLOBAL_LOCATION;
        $location = 'product';
        $critical = self::TYPE_CRITICAL;
        $nonCritical = self::TYPE_NONCRITICAL;

        $inputs = [
            [
                $critical => [
                    $globalLocation => self::SELECTOR_CRITICAL . CssModifier::getCriticalTag() . self::SELECTOR_CRITICAL_B . CssModifier::getCriticalTag(),
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL,
                ]
            ],
            [
                $critical => [
                    $location => self::SELECTOR_CRITICAL . CssModifier::getCriticalTag($location),
                    $globalLocation => self::SELECTOR_CRITICAL_B . CssModifier::getCriticalTag(),
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL
                ]
            ],
            [
                $critical => [
                    $location => CssModifier::getCriticalStartTag($location) . self::SELECTOR_CRITICAL . CssModifier::CRITICAL_STOP,
                    $globalLocation => CssModifier::getCriticalStartTag() . self::SELECTOR_CRITICAL_B . CssModifier::CRITICAL_STOP
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL
                ]
            ],
            [
                $critical => [
                    $location => CssModifier::getCriticalStartTag($location) . self::SELECTOR_CRITICAL . CssModifier::CRITICAL_STOP
                ],
                $nonCritical => [
                    self::SELECTOR_NONCRITICAL
                ]
            ]
        ];

        foreach ($inputs as $input) {
            $notes = $input['_notes'] ?? '';
            unset($input['_notes']);

            $inputCss = '';
            foreach ($input as $type => $lines) {
                $inputCss .= implode('', (array)$lines);
            }

            $shouldHaveCritical = !empty($input[$critical]);

            $criticalResults = array_filter(array_unique([
                $this->cssModifier->getCriticalCss($inputCss, ['', $location]),
                $this->cssModifier->getCriticalCss($inputCss, [$globalLocation, $location])
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

            $nonCriticalCss = $this->removeComments($this->cssModifier->getNonCriticalCss($inputCss));

            foreach ([self::SELECTOR_CRITICAL, self::SELECTOR_CRITICAL_B] as $criticalSelector) {
                if (strpos($nonCriticalCss, $criticalSelector) !== false) {
                    throw new \RuntimeException(
                        sprintf(
                            'NonCritical CSS contains a critical selector "%s". Input was %s',
                            $criticalSelector,
                            $inputCss
                        )
                    );
                }
            }

            if (strpos($nonCriticalCss, self::SELECTOR_NONCRITICAL) === false) {
                throw new \RuntimeException(
                    sprintf(
                        'NonCritical CSS does not contain a non-critical selector "%s". Input was %s',
                        self::SELECTOR_NONCRITICAL,
                        $inputCss
                    )
                );
            }
        }

        return $output;
    }

    /**
     *
     */
    private function doStaticMultiLocationTests(): array
    {
        $glue = "\n";
        $output = $this->initTestOuput(__METHOD__);
        $inputCss = implode(
            $glue,
            [
                CssModifier::getCriticalStartTag('product') . self::SELECTOR_CRITICAL . CssModifier::CRITICAL_STOP,
                CssModifier::getCriticalStartTag() . self::SELECTOR_CRITICAL_B . CssModifier::CRITICAL_STOP,
                self::SELECTOR_NONCRITICAL
            ]
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL,
            $this->cssModifier->getCriticalCss($inputCss, ['product']),
            'Critical selector for single location. Should not include global.'
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL_B,
            $this->cssModifier->getCriticalCss($inputCss),
            'Critical selector b with no (global) location.'
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL . $glue . self::SELECTOR_CRITICAL_B,
            $this->cssModifier->getCriticalCss($inputCss, ['', 'product']),
            'Critical selector with 1 location and implied global'
        );

        $this->doVariablesMatch(
            self::SELECTOR_CRITICAL . $glue . self::SELECTOR_CRITICAL_B,
            $this->cssModifier->getCriticalCss($inputCss, [CssModifier::GLOBAL_LOCATION, 'product']),
            'Critical selector with 1 location and implied global'
        );

        return $output;
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

    /**
     *
     */
    private function initTestOuput(string $title): array
    {
        $bar = '###############';
        return [
            $bar,
            '# ' . $title,
            $bar,
            ''
        ];
    }

    /**
     *
     */
    private function addResultToOutput(array $output, string $input, string $critical, string $nonCritical): array
    {
        $output[] = '    Input: ' . $input;
        $output[] = ' Critical: ' . $critical;
        $output[] = 'nCritical: ' . $nonCritical;

        return $output;
    }

    /**
     *
     */
    private function removeComments(string $input): string
    {
        return trim(preg_replace('/\/\*.*\*\//U', '', $input));
    }

    /**
     *
     */
    private function assertTypedResultValid(
        string $type,
        string $input,
        string $critical,
        string $nonCritical
    ): void {
        $hasError = ($type === self::TYPE_CRITICAL && (!$critical || $nonCritical))
            || ($type === self::TYPE_NONCRITICAL && ($critical || !$nonCritical));

        if ($hasError) {
            $this->doCssException(
                $type,
                $input,
                $critical,
                $nonCritical
            );
        }
    }

    /**
     *
     */
    private function doCssException(
        string $type,
        string $input,
        string $critical,
        string $nonCritical
    ): void {
        throw new \RuntimeException(
            sprintf(
                "CSS error with %s CSS.\n\n       \$input = %s\n    \$critical = %s\n \$nonCritical = %s",
                $type === self::TYPE_CRITICAL ? 'critical' : 'non-critical',
                $input,
                $critical,
                $nonCritical
            )
        );
    }
}