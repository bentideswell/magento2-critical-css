<?php
/**
 *
 */
namespace FishPig\CriticalCss\Block;

use Magento\Framework\App\View\Deployment\Version\StorageInterface;
use FishPig\CriticalCss\App\CriticalTags;

class CriticalCss extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     *
     */
    private $criticalCssProvider = null;

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
    private $locations = [
        CriticalTags::GLOBAL_LOCATION
    ];

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \FishPig\CriticalCss\App\FileDataProvider $criticalCssProvider,
        \FishPig\CriticalCss\Model\Config $config,
        \tubalmartin\CssMin\Minifier $cssMinifier,
        StorageInterface $deploymentVersionStorage,
        array $data = []
    ) {
        $this->criticalCssProvider = $criticalCssProvider;
        $this->config = $config;
        $this->cssMinifier = $cssMinifier;
        $this->deploymentVersionStorage = $deploymentVersionStorage;

        parent::__construct($context, $data);

        $this->setCacheLifetime(
            $this->isCacheEnabled() ? 31449600 : null
        );
    }

    /**
     *
     */
    public function addLocation(string $location): self
    {
        $this->locations[$location] = $location;
        return $this;
    }

    /**
     *
     */
    public function getLocations(): ?array
    {
        return array_values(
            array_unique($this->locations)
        ) ?: null;
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

        if (!($css = $this->criticalCssProvider->get($targetFile, $this->getLocations()))) {
            return '';
        }

        if ($this->isMinifyEnabled()) {
            $css = $this->cssMinifier->run($css);
        }

        return sprintf(
            '<style type="text/css">%s</style>',
            $css
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
    public function isCacheEnabled(): bool
    {
        return $this->getTargetFile() && $this->isCriticalCssEnabled();
    }

    /**
     *
     */
    public function isMinifyEnabled(): bool
    {
        return true;
    }

    /**
     *
     */
    public function getCacheKeyInfo()
    {
        return [
            $this->getNameInLayout(),
            $this->getTargetFile(),
            implode('::', $this->getLocations()),
            (int)$this->isCriticalCssEnabled(),
            (int)$this->isMinifyEnabled(),
            $this->deploymentVersionStorage->load()
        ];
    }
}