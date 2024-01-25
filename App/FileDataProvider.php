<?php
/**
 *
 */
namespace FishPig\CriticalCss\App;

use Magento\Framework\View\Asset\File;
use FishPig\CriticalCss\App\PostProcessor\CriticalCssPostProcessor;

class FileDataProvider
{
    /**
     *
     */
    private $assetRepository = null;

    /**
     *
     */
    private $viewDesign = null;

    /**
     *
     */
    private $themeProvider = null;

    /**
     *
     */
    private $criticalCssPostProcessor = null;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\View\DesignInterface $viewDesign,
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider,
        CriticalCssPostProcessor $criticalCssPostProcessor
    ) {
        $this->assetRepository = $assetRepository;
        $this->viewDesign = $viewDesign;
        $this->themeProvider = $themeProvider;
        $this->criticalCssPostProcessor = $criticalCssPostProcessor;
    }

    /**
     *
     */
    public function get(
        string $fileName,
        $locations = null,
        ?string $themeCode = null,
        ?string $area = null
    ): string {
        $file = $this->resolveAssetFile(
            $fileName,
            $area ?? 'frontend',
            $this->resolveThemeCode($themeCode)
        );

        return $this->criticalCssPostProcessor->postProcessContent(
            $file->getContent(),
            $locations,
            $file->getUrl()
        );
    }

    /**
     *
     */
    private function resolveThemeCode(?string $themeCode = null): string
    {
        return $themeCode ?: $this->themeProvider->getThemeById(
            $this->viewDesign->getConfigurationDesignTheme()
        )->getCode();
    }

    /**
     *
     */
    private function resolveAssetFile(string $file, string $area, string $theme): File
    {
        return $this->assetRepository->createAsset(
            $file,
            [
                'area' => $area,
                'theme' => $theme
            ]
        );
    }
}