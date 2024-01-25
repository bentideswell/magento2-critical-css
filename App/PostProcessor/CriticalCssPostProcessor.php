<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\PostProcessor;

use FishPig\CriticalCss\App\CriticalTags;
use FishPig\CriticalCss\App\PreProcessor\AbstractPreProcessor;
use FishPig\CriticalCss\App\AbstractProcessor;

class CriticalCssPostProcessor extends AbstractProcessor
{
    /**
     *
     */
    public function postProcessContent(
        string $input,
        $locations = null,
        ?string $url = null
    ): string {
        $locations = $this->criticalTags->resolveLocations($locations);

        $input = $this->removeNewLinesInSelectors($input);
        $input = $this->removeNonCriticalLines($input, $locations);
        $input = $this->removeEmptyRules($input);

        if ($url) {
            $input = $this->fixRelativeUrls($input, $url);
        }

        return trim($input);
    }

    /**
     *
     */
    private function removeEmptyRules(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*.*\*\//Us', '', $css);

        // The next part we do twice. The first run removes the empty rules and the second
        // run removes the empty media queries.
        for ($i = 1; $i <= 2; $i++) {
            // Close empty brackets. This allows for easy identification of empty
            // selectors.
            $css = preg_replace('/\{\s+\}/', '{}', $css);

            // Remove trailing whitespace after closed brackets. This eases empty selector
            // identification
            $css = preg_replace('/\}[ ]{1,}/', '}', $css);

            // Remove selectors with no rules. To do this we convert new line sinto double
            // new lines. This makes the regex easier. We remove all extra new lines after.
            $css = preg_replace('/\n[^\n]+\{\}\n/', "\n", str_replace("\n", "\n\n", $css));
            // Now we remove the new lines.
            $css = preg_replace("/[\n]{2,}/", "\n", $css);
        }

        return $css;
    }

    /**
     *
     */
    private function removeNonCriticalLines(string $css, array $locations): string
    {
        // Go through each line and see if it is a rule and then decide
        // whether it is critical or non critical
        $cssLines = array_values(array_filter(explode("\n", $css)));
        $criticalRuleRegex = '/' . $this->criticalTags->getEscapedLocationComment(CriticalTags::CRITICAL_RULE_START_WITH_LOCATION)
                             . '(.*)' . $this->criticalTags->getEscapedComment(CriticalTags::CRITICAL_RULE_END) . '/';

        // Remove all rules that are not marked as critical. Do not remove selectors
        // and media queries
        $debugLines = false;
        foreach ($cssLines as $line => $cssLine) {
            if (!$this->criticalTags->isLineCssRule($cssLine)) {
                echo $debugLines ? '+   ' . $cssLine  . PHP_EOL : '';
                continue;
            }


            if (preg_match($criticalRuleRegex, $cssLine, $match) && in_array($match['location'], $locations)) {
                echo $debugLines ? '++  ' . $cssLine  . PHP_EOL : '';
                $cssLines[$line] = $match[AbstractPreProcessor::CSS_CONTENT_INDEX];
            } else {
                echo $debugLines ? '-   ' . $cssLine  . PHP_EOL : '';
#                $lineBuffer = trim($cssLines[$line]);

#                if (strlen($lineBuffer) > 1 && substr($lineBuffer, -1) === '}') {
#                    $cssLines[$line] = '}';
#                } else {
                    unset($cssLines[$line]);
#                }
            }
        }
if ($debugLines) {
    #exit;
}
        #print_r($cssLines);exit;
        // Recreate the string without the removed lines. We pad the string with
        // new lines to make the regex easier
        $css = "\n" . implode("\n", $cssLines) . "\n";

        return $css;
    }

    /**
     *
     */
    private function fixRelativeUrls(string $css, string $url): string
    {
        // Fix relative URLs
        if (preg_match_all('/url\(([\'"]{0,1})(\.\.\/[^\1]+)\1\)/U', $css, $urlMatches)) {
            $fileUrlPath = dirname($url) . '/';
            foreach ($urlMatches[0] as $index => $originalLine) {
                $relativeUrl = $urlMatches[2][$index];

                $css = str_replace(
                    $originalLine,
                    str_replace(
                        $relativeUrl,
                        $fileUrlPath . $relativeUrl,
                        $originalLine
                    ),
                    $css
                );
            }
        }

        return trim($css);
    }
}
