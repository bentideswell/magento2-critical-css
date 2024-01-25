<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\PreProcessor;

use FishPig\CriticalCss\App\CriticalTags;

class CssPreProcessor extends AbstractPreProcessor
{
    /**
     *
     */
    public function preProcessContent(string $input): string
    {

#        $input = $this->removeCriticalCommentsOnSelectors($input);


        $input = $this->removeAddedNewLinesForCriticalComments($input);

        $input = $this->revertDirectiveNames($input);
        $input = $this->convertInlineTagsToRules($input);


        return $input;
    }

    /**
     *
     */
    private function removeCriticalCommentsOnSelectors(string $input): string
    {
        for ($i = 0; $i <= 5; $i++) {
            $buffer = preg_replace(
                '/(\{|\})\s+' . $this->criticalTags->getEscapedLocationComment(CriticalTags::CRITICAL_WITH_LOCATION) . '/Us',
                "$1",
                $input
            );

            if ($buffer === $input) {
                return $buffer;
            }

            $input = $buffer;
        }

        return $input;
    }

    /**
     *
     */
    private function revertDirectiveNames(string $input): string
    {
        return preg_replace(
            '/' . str_replace(
                '%s',
                '(' . $this->criticalTags->getProtectedDirectivesRegexString() . ')',
                CriticalTags::DIRECTIVE_PLACEHOLDER
            ) . '/',
            '@$1',
            $input
        );
    }

    /**
     *
     */
    private function convertInlineTagsToRules(string $input): string
    {
        // Now we move the comments from the end of the line to wrap the rule.
        // This stops the CSS rule being applied in the main CSS file and will be
        // removed when minified. Later we can extract these comments to build our critical CSS
        $lines = explode("\n", $input);

        $regex = '/^(.*)' . $this->criticalTags->getEscapedLocationComment(CriticalTags::CRITICAL_WITH_LOCATION) . '\s*$/';

        foreach ($lines as $index => $line) {
            if (preg_match($regex, $line, $lineMatch)) {
                $lines[$index] = $this->criticalTags->getCriticalRuleStartTag($lineMatch['location'])
                                    . trim($lineMatch[1])
                                    . CriticalTags::CRITICAL_RULE_END;
            }
        }

        $input = "\n" . implode("\n", $lines) . "\n";

        return $input;
    }
}
