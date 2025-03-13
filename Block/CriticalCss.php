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
    private $cache;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \FishPig\CriticalCss\App\FileDataProvider $criticalCssProvider,
        \FishPig\CriticalCss\Model\Config $config,
        \tubalmartin\CssMin\Minifier $cssMinifier,
        StorageInterface $deploymentVersionStorage,
        \FishPig\CriticalCss\App\Cache $cache,
        array $data = []
    ) {
        $this->criticalCssProvider = $criticalCssProvider;
        $this->config = $config;
        $this->cssMinifier = $cssMinifier;
        $this->deploymentVersionStorage = $deploymentVersionStorage;
        $this->cache = $cache;
        parent::__construct($context, $data);
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
        if ($css = $this->getCss()) {
            return sprintf(
                '<style type="text/css">%s</style>',
                $css
            );
        }

        return '';
    }

    /**
     * 
     */
    private function getCss()
    {
        if (!$this->isCriticalCssEnabled()) {
            return '';
        }

        if (!($targetFile = $this->getTargetFile())) {
            return '';
        }

        $cacheId = null;

        if ($this->isCacheEnabled()) {
            if ($cacheId = $this->getCacheId()) {
                if ($css = $this->cache->load($cacheId)) {
                    return $css;
                }
            }
        }

        if (!($css = $this->criticalCssProvider->get($targetFile, $this->getLocations()))) {
            return '';
        }
        
        if ($this->isMinifyEnabled()) {
            $css = $this->cssMinifier->run($css);
        }

        if ($cacheId) {
            $this->cache->save($css, $cacheId);
        }

        return $css;
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
    public function getCacheId(): ?string
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }
        return md5(implode('::', [
            $this->getNameInLayout(),
            $this->getTargetFile(),
            implode(',', $this->getLocations()),
            (int)$this->isCriticalCssEnabled(),
            (int)$this->isMinifyEnabled(),
            $this->deploymentVersionStorage->load(),
        ]));
    }
}