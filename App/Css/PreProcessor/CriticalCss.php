<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\Css\PreProcessor;

use Magento\Framework\View\Asset\PreProcessorInterface;

class CriticalCss implements PreProcessorInterface
{
    /**
     *
     */
    private $config = null;

    /**
     *
     */
    private $cssModifier = null;

    /**
     *
     */
    public function __construct(
        \FishPig\CriticalCss\Model\Config $config,
        \FishPig\CriticalCss\App\Css\CssModifier $cssModifier
    ) {
        $this->config = $config;
        $this->cssModifier = $cssModifier;
    }

    /**
     * {@inheritdoc}
     */
    public function process(\Magento\Framework\View\Asset\PreProcessor\Chain $chain)
    {
        if ($this->config->isEnabled()) {
            $chain->setContent(
                $this->cssModifier->getNonCriticalCss(
                    $chain->getContent()
                )
            );
        }
    }
}