<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\Css;

class CssModifier
{
    /**
     *
     */
    const CRITICAL = '/* @critical */';
    const CRITICAL_START = '/* @critical:start */';
    const CRITICAL_STOP = '/* @critical:start */';
    const CRITICAL_RULE_START = '/* @critical:rule:start:';
    const CRITICAL_RULE_END   = ':@critical:rule:end */';


    /**
     *
     */
    public function getCriticalCss(string $css): string
    {

        $css = $this->prepareCss($css);
        $css = $this->normalizeCriticalCssComments($css);

        // Go through each line and see if it is a rule and then decide
        // whether it is critical or non critical
        $cssLines = explode("\n", $css);
        $criticalRuleRegex = '/' . $this->getEscapedComment(self::CRITICAL_RULE_START) . '(.*)' . $this->getEscapedComment(self::CRITICAL_RULE_END) . '/';

        // Remove all rules that are not marked as critical. Do not remove selectors
        // and media queries
        foreach ($cssLines as $line => $cssLine) {
            if (!$this->isLineCssRule($cssLine)) {
                continue;
            }

            if (preg_match($criticalRuleRegex, $cssLine, $match)) {
                $cssLines[$line] = $match[1];
            } else {
                unset($cssLines[$line]);
            }
        }

        // Recreate the string without the removed lines. We pad the string with
        // new lines to make the regex easier
        $css = "\n" . implode("\n", $cssLines) . "\n";

        $css = $this->removeEmptyRules($css);

        return $css;
    }

    /**
     * This method does not remove critical CSS. Instead it wraps all critical
     * css rules in a comment. This will be ignored by the browser and will be
     * removed when the file is minified. This allows the critical CSS to be
     * retrieved where required without it showing up as a duplicate in the main
     * CSS file.
     */
    public function getNonCriticalCss(string $css): string
    {
        $css = $this->prepareCss($css);
        $css = $this->normalizeCriticalCssComments($css);

        return $css;
    }

    /**
     *
     */
    private function isLineCssRule(string $cssLine): bool
    {
        return strpos($cssLine, ':') !== false && strpos($cssLine, ';') !== false;
    }

    /**
     *
     */
    private function prepareCss(string $css): string
    {
        // Remove new lines in selectors that contain multiple comma separated selectors
        $css = preg_replace('/,\n/', ', ', $css);

        return $css;
    }

    /**
     *
     */
    private function normalizeCriticalCssComments(string $css): string
    {
        // PreProcessors put comments on a new line. This returns critical comments
        // onto the correct line
        $css = preg_replace('/\n\s+(' . $this->getEscapedComment(self::CRITICAL) . '\n)/', ' $1', $css);

        // Convert the start/end comments into single line comments.
        // This makes processing easier
        $regex = '/' . $this->getEscapedComment(self::CRITICAL_START) . '(.*)' . $this->getEscapedComment(self::CRITICAL_STOP) . '/Us';

        if (preg_match_all($regex, $css, $matches)) {
            foreach ($matches[1] as $index => $cssMatch) {
                $originalCssMatch = $matches[0][$index];

                $cssMatchLines = explode("\n", $cssMatch);

                foreach ($cssMatchLines as $line => $cssMatchLine) {
                    if ($this->isLineCssRule($cssMatchLine)) {
                        $cssMatchLines[$line] .= ' ' . self::CRITICAL;
                    }
                }

                $css = str_replace($originalCssMatch, implode("\n", $cssMatchLines), $css);
            }
        }

        // Now we move the comments from the end of the line to wrap the rule.
        // This stops the CSS rule being applied in the main CSS file and will be
        // removed when minified. Later we can extract these comments to build our critical CSS
        $cssLines = explode("\n", $css);

        foreach ($cssLines as $index => $cssLine) {
            if (preg_match('/^(.*)' . $this->getEscapedComment(self::CRITICAL) . '\s*$/', $cssLine, $cssLineMatch)) {
                $cssLines[$index] = self::CRITICAL_RULE_START . trim($cssLineMatch[1]) . self::CRITICAL_RULE_END;
            }
        }

        $css = "\n" . implode("\n", $cssLines) . "\n";

        return $css;
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
    private function getEscapedComment(string $comment): string
    {
        return preg_quote($comment, '/');
    }
}