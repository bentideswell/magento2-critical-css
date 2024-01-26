<?php
/**
 *
 */
namespace FishPig\CriticalCss\Plugin\Magento\Framework\View\Asset;

use Magento\Framework\View\Asset\File;

class FilePlugin
{
    /**
     *
     */
    /*
    public function afterGetContentt(
        File $subject,
        $content
    ) {

        if (strpos($subject->getPath(), '.less') !== false) {
echo $subject->getPath();exit;
            $cssModifier = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \FishPig\CriticalCss\App\Css\CssModifier::class
            );

            $content = $cssModifier->prepareLess($content);
        }

        return $content;
    }
    */
}