<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\PreProcessor;

use Magento\Framework\View\Asset\PreProcessorInterface;
use Magento\Framework\View\Asset\PreProcessor\Chain;
use FishPig\CriticalCss\App\CriticalTags;
use FishPig\CriticalCss\App\AbstractProcessor;

abstract class AbstractPreProcessor extends AbstractProcessor implements PreProcessorInterface
{
    /**
     *
     */
    abstract public function preProcessContent(string $input): string;

    /**
     *
     */
    public function process(Chain $chain)
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $chain->setContent(
            $this->preProcessContent(
                $chain->getContent()
            )
        );
    }
}
