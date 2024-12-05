<?php
/**
 *
 */
namespace FishPig\CriticalCss\Plugin\Magento\Framework\View\Asset;

use Magento\Framework\View\Asset\Source;
use Magento\Framework\View\Asset\LocalInterface;

class SourcePlugin
{
    /**
     * 
     */
    private $isOiginalFlag;

    /**
     * 
     */
    public function __construct(
        \FishPig\CriticalCss\App\Flag\IsOriginal $isOriginalFlag
    ) {
        $this->isOiginalFlag = $isOriginalFlag;
    }
    
    /**
     * 
     */
    public function aroundGetFile(
        Source $subject,
        callable $proceed,
        LocalInterface $asset
    ) {
        if ($this->isOriginalFile($asset)) {
            $this->isOiginalFlag->__invoke(true);
        }

        $result = $proceed($asset);

        if ($this->isOiginalFlag->__invoke()) {
            $this->isOiginalFlag->__invoke(false);
        }

        return $result;
    }

    /**
     * 
     */
    private function isOriginalFile(LocalInterface $asset): bool
    {
        if (preg_match('/^(.*)--original(\.css)$/', $asset->getFilePath(), $match)) {
            return true;
        }
        return false;
    }
}