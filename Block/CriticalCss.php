<?php
/**
 *
 */
namespace FishPig\CriticalCss\Block;

class CriticalCss extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     *
     */
    private $dataProvider = null;

    /**
     *
     */
    private $config = null;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \FishPig\CriticalCss\App\DataProvider $dataProvider,
        \FishPig\CriticalCss\Model\Config $config
    ) {
        $this->dataProvider = $dataProvider;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     *
     */
    protected function _toHtml()
    {
        if (!$this->config->isEnabled()) {
            return '';
        }

        if ($targetFile = $this->getData('target_file')) {
            if ($css = $this->dataProvider->getCriticalCss($targetFile)) {
                return sprintf(
                    '<style type="text/css">%s</style>',
                    $css
                );
            }
        }
    }
}