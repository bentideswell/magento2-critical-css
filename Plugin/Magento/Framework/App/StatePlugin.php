<?php
/**
 * 
 */
namespace FishPig\CriticalCss\Plugin\Magento\Framework\App;

use Magento\Framework\App\State;

class StatePlugin
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
    public function afterGetMode(
        State $subject,
        $mode
    ) {
        return $this->appStateModeOverrider->getMode() ?? $mode;
    }
}