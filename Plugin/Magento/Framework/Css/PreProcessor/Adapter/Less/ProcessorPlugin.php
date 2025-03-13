<?php
/**
 * 
 */
namespace FishPig\CriticalCss\Plugin\Magento\Framework\Css\PreProcessor\Adapter\Less;

use Magento\Framework\Css\PreProcessor\Adapter\Less\Processor;
use Magento\Framework\View\Asset\File;
use Magento\Framework\App\State;
class ProcessorPlugin
{
    /**
     * 
     */   
    private $appStateModeOverrider;

    /**
     * 
     */
    public function __construct(
        \FishPig\CriticalCss\App\State\Mode $appStateModeOverrider
    ) {
        $this->appStateModeOverrider = $appStateModeOverrider;
    }

    /**
     * 
     */   
    public function aroundProcessContent(
        Processor $subject,
        \Closure $proceed,
        File $asset
    ) {
        $this->appStateModeOverrider->setMode(State::MODE_DEVELOPER);

        try {
            return $proceed($asset);
        } finally {
            $this->appStateModeOverrider->setMode(null);
        }
    }
}