<?php
/**
 *
 */
namespace FishPig\CriticalCss\App;

class DataProvider
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
    private $cssModifier = null;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\View\DesignInterface $viewDesign,
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider,
        \FishPig\CriticalCss\App\Css\CssModifier $cssModifier
    ) {
        $this->assetRepository = $assetRepository;
        $this->viewDesign = $viewDesign;
        $this->themeProvider = $themeProvider;
        $this->cssModifier = $cssModifier;
    }

    /**
     *
     */
    public function getCriticalCss(
        string $fileName,
        string $themeCode = '',
        string $area = 'frontend'
    ): string {
        if (!$themeCode) {
            $themeCode = $this->themeProvider->getThemeById(
                $this->viewDesign->getConfigurationDesignTheme()
            )->getCode();
        }

        $file = $this->assetRepository->createAsset(
            $fileName,
            [
                'area' => $area,
                'theme' => $themeCode
            ]
        );

        $css = $this->cssModifier->getCriticalCss($file->getContent());

        // Fix relative URLs
        if (preg_match_all('/url\(([\'"]{0,1})(\.\.\/[^\1]+)\1\)/U', $css, $urlMatches)) {
            $fileUrlPath = dirname($file->getUrl()) . '/';
            foreach ($urlMatches[0] as $index => $originalLine) {
                $relativeUrl = $urlMatches[2][$index];

                $css = str_replace(
                    $originalLine,
                    str_replace(
                        $relativeUrl,
                        $fileUrlPath . $relativeUrl,
                        $originalLine
                    ),
                    $css
                );
            }
        }

        return $css;
    }
}