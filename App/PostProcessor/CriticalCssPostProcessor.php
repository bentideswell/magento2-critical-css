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
        if ($this->isOriginalFlag->__invoke() === true) {
            return $input;
        }

        $input = $this->removeNewLinesInSelectors($input);
        $input = $this->removeNonCriticalLines(
            $input,
            $this->criticalTags->resolveLocations($locations)
        );
        $input = $this->removeEmptyRules($input);

        if ($url) {
            $input = $this->fixRelativeUrls($input, $url);
        }

        return trim($input);
    }

    /**
     *
     */
    private function removeEmptyRules(string $input): string
    {
        // Remove comments
        $input = preg_replace('/\/\*.*\*\//Us', '', $input);

        // The next part we do twice. The first run removes the empty rules and the second
        // run removes the empty media queries.
        for ($i = 1; $i <= 2; $i++) {
            // Close empty brackets. This allows for easy identification of empty
            // selectors.
            $input = preg_replace('/\{\s+\}/', '{}', $input);

            // Remove trailing whitespace after closed brackets. This eases empty selector
            // identification
            $input = preg_replace('/\}[ ]{1,}/', '}', $input);

            // Remove selectors with no rules. To do this we convert new lines into double
            // new lines. This makes the regex easier. We remove all extra new lines after.
            // We also wrap input in new lines. This allows us to catch selectors
            // against the start or edge of $input
            $input = preg_replace(
                '/\n[^\n]+\{\}\n/',
                "\n",
                "\n" . str_replace("\n", "\n\n", $input) . "\n"
            );

            // Now we remove the new lines.
            $input = trim(preg_replace("/[\n]{2,}/", "\n", $input));

            if (!$input) {
                break;
            }
        }

        // Remove some empty media queries that didn't get picked up above
        $input = preg_replace('/@media[^\{]+\{\s*\}/', '', $input);

        return $input;
    }

    /**
     *
     */
    private function removeNonCriticalLines(string $input, array $locations): string
    {
        // Go through each line and see if it is a rule and then decide
        // whether it is critical or non critical
        $lines = array_values(array_filter(explode("\n", $input)));
        $criticalRuleRegex = '/' . $this->criticalTags->getEscapedLocationComment(CriticalTags::CRITICAL_RULE_START_WITH_LOCATION)
                             . '(.*)' . $this->criticalTags->getEscapedComment(CriticalTags::CRITICAL_RULE_END) . '/';

        // Remove all rules that are not critical and ave a location that is it
        // @locations. Do not remove any lines that aren't rules (eg. selectors & brackets)
        foreach ($lines as $index => $line) {
            if (preg_match($criticalRuleRegex, $line, $match) && in_array($match['location'], $locations)) {
                $lines[$index] = $match[AbstractPreProcessor::CSS_CONTENT_INDEX];
            } elseif ($this->criticalTags->isLineCssRule($line)) {
                unset($lines[$index]);
            }
        }

        // Recreate the string without the removed lines.
        return implode("\n", $lines);
    }

    /**
     *
     */
    private function fixRelativeUrls(string $input, string $url): string
    {
        // Fix relative URLs
        if (preg_match_all('/url\(([\'"]{0,1})(\.\.\/[^\1]+)\1\)/U', $input, $urlMatches)) {
            $fileUrlPath = dirname($url) . '/';
            foreach ($urlMatches[0] as $index => $originalLine) {
                $relativeUrl = $urlMatches[2][$index];

                $input = str_replace(
                    $originalLine,
                    str_replace(
                        $relativeUrl,
                        $fileUrlPath . $relativeUrl,
                        $originalLine
                    ),
                    $input
                );
            }
        }

        return $input;
    }
}
