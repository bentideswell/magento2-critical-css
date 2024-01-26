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
    public function getCriticalCss(string $css, $locations = null): string
    {
        $this->criticalCssProvider->getFromString($css, $locations);
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

    }
}