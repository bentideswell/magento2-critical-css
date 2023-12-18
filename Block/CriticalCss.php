<?php
/**
 *
 */
namespace FishPig\CriticalCss\Block;

use Magento\Framework\App\View\Deployment\Version\StorageInterface;

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
    private $cssMinifier = null;

    /**
     *
     */
    private $deploymentVersionStorage = null;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \FishPig\CriticalCss\App\DataProvider $dataProvider,
        \FishPig\CriticalCss\Model\Config $config,
        \tubalmartin\CssMin\Minifier $cssMinifier,
        StorageInterface $deploymentVersionStorage,
        array $data = []
    ) {
        $this->dataProvider = $dataProvider;
        $this->config = $config;
        $this->cssMinifier = $cssMinifier;
        $this->deploymentVersionStorage = $deploymentVersionStorage;

        parent::__construct($context, $data);

        $this->setCacheLifetime(
            $this->getTargetFile() && $this->isCriticalCssEnabled() ? 31449600 : null
        );
    }

    /**
     *
     */
    protected function _toHtml()
    {
        if (!$this->isCriticalCssEnabled()) {
            return '';
        }

        if (!($targetFile = $this->getTargetFile())) {
            return '';
        }

        if (!($css = $this->dataProvider->getCriticalCss($targetFile))) {
            return '';
        }

        return sprintf(
            '<style type="text/css">%s</style>',
            $this->cssMinifier->run($css)
        );
    }

    /**
     *
     */
    public function getTargetFile(): ?string
    {
        return $this->getData('target_file') ?: null;
    }

    /**
     *
     */
    public function isCriticalCssEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     *
     */
    public function getCacheKeyInfo()
    {
        return [
            $this->getNameInLayout(),
            $this->getTargetFile(),
            (int)$this->isCriticalCssEnabled(),
            $this->deploymentVersionStorage->load()
        ];
    }
}