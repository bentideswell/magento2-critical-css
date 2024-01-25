<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\PreProcessor;

use FishPig\CriticalCss\App\CriticalTags;
use FishPig\CriticalCss\App\AbstractProcessor;

class LessPreProcessor extends AbstractPreProcessor
{
    /**
     *
     */
    public function preProcessContent(string $input): string
    {
        // Does not contain a critical tag so give it back
        if (strpos($input, '@critical') === false) {
            return $input;
        }

        $input = $this->fixMissingWhitespace($input);

        $input = $this->removeNewLinesInSelectors($input);
        $input = $this->spitCommentsOntoNewLines($input);
        $input = $this->removeNonCriticalBlockComments($input);
        $input = $this->addGlobalLocationToRootTags($input);
        $input = $this->removeAddedNewLinesForCriticalComments($input);
        $input = $this->convertDirectiveNames($input);
        $input = $this->moveAllCriticalCommentsInline($input);

        return $input;
    }

    /**
     *
     */
    private function fixMissingWhitespace(string $input): string
    {
        $input = preg_replace('/;\s*\}/', ";\n}", $input);
        return $input;
    }

    /**
     *
     */
    private function spitCommentsOntoNewLines(string $input): string
    {
        return preg_replace('/(\/\*.*\*\/)/Us', '$1' . "\n" . '$2', $input);
    }

    /**
     *
     */
    private function removeNonCriticalBlockComments(string $input): string
    {
        return preg_replace_callback(
            '/\/\*.*\*\//Us',
            function ($matches) {
                return strpos($matches[0], '@critical') !== false ? $matches[0] : '';
            },
            $input
        );
    }

    /**
     *
     */
    private function addGlobalLocationToRootTags(string $input): string
    {
        $input = str_replace(
            CriticalTags::CRITICAL,
            $this->criticalTags->getCriticalTag(CriticalTags::GLOBAL_LOCATION),
            $input
        );

        $input = str_replace(
            CriticalTags::CRITICAL_START,
            $this->criticalTags->getCriticalStartTag(CriticalTags::GLOBAL_LOCATION),
            $input
        );

        return $input;
    }

    /**
     *
     */
    private function convertDirectiveNames(string $input): string
    {
        return preg_replace(
            '/@(' . $this->criticalTags->getProtectedDirectivesRegexString() . ')/',
            str_replace(
                '%s',
                '$1',
                CriticalTags::DIRECTIVE_PLACEHOLDER
            ),
            $input
        );
    }

    /**
     *
     */
    private function moveAllCriticalCommentsInline(string $css): string
    {
        // Convert the start/end comments into single line comments.
        // This makes processing easier. This now includes locations
        $regex = '/' . $this->criticalTags->getEscapedLocationComment(CriticalTags::CRITICAL_START_WITH_LOCATION)
                 . '(.*)'
                 . $this->criticalTags->getEscapedComment(CriticalTags::CRITICAL_STOP) . '/Us';

        if (preg_match_all($regex, $css, $matches)) {

            // This may change if more capture groups are added to regex
            $captureIndexForCss = AbstractProcessor::CSS_CONTENT_INDEX;

            foreach ($matches[$captureIndexForCss] as $index => $cssMatch) {
                $originalCssMatch = $matches[0][$index];
                $location = $matches['location'][$index];

                $cssMatchLines = explode("\n", $cssMatch);

                foreach ($cssMatchLines as $line => $cssMatchLine) {
                    if ($this->isLineCssRule($cssMatchLine)) {
                        $cssMatchLines[$line] .= ' ' . $this->criticalTags->getCriticalTag($location);
                    }
                }

                $css = str_replace($originalCssMatch, implode("\n", $cssMatchLines), $css);
            }
        }

        return $css;
    }
}
