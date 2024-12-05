<?php
/**
 *
 */
namespace FishPig\CriticalCss\App;

use Magento\Framework\View\Asset\PreProcessorInterface;
use Magento\Framework\View\Asset\PreProcessor\Chain;
use FishPig\CriticalCss\App\CriticalTags;

abstract class AbstractProcessor
{
    /**
     *
     */
    const CSS_CONTENT_INDEX = 2;

    /**
     *
     */
    protected $config = null;

    /**
     *
     */
    protected $criticalTags = null;

    /**
     * 
     */
    protected $isOriginalFlag = null;

    /**
     *
     */
    public function __construct(
        \FishPig\CriticalCss\Model\Config $config,
        CriticalTags $criticalTags,
        \FishPig\CriticalCss\App\Flag\IsOriginal $isOriginalFlag
    ) {
        $this->config = $config;
        $this->criticalTags = $criticalTags;
        $this->isOriginalFlag = $isOriginalFlag;
    }

    /**
     *
     */
    protected function removeNewLinesInSelectors(string $input): string
    {
        return preg_replace('/,\n/', ', ', $input);
    }

    /**
     * PreProcessors move comments onto their own lines, which breaks our
     * functionality. This detects this and puts the comment back on the end
     * of the previous line
     */
    protected function removeAddedNewLinesForCriticalComments(string $input): string
    {
        return preg_replace(
            '/\n\s*(' . $this->criticalTags->getEscapedLocationComment(CriticalTags::CRITICAL_WITH_LOCATION) . '\s*\n)/',
            '    $1',
            $input
        );
    }

    /**
     *
     */
    protected function isLineCssRule(string $cssLine): bool
    {
        return $this->criticalTags->isLineCssRule($cssLine);
    }
}
