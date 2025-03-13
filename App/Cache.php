<?php
/**
 *
 */
namespace FishPig\CriticalCss\App;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class Cache extends TagScope
{
    /**
     *
     */
    const TYPE_IDENTIFIER = 'fishpig_critical_css';

    /**
     *
     */
    const CACHE_TAG = 'CRITICAL_CSS';

    /**
     *
     */
    public function __construct(
        FrontendPool $cacheFrontendPool,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\Cache\State $cacheState
    ) {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );

        try {
            if ($appState->getMode() === \Magento\Framework\App\State::MODE_PRODUCTION) {
                if (!$cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
                    $cacheState->setEnabled(self::TYPE_IDENTIFIER, true);
                    $cacheState->persist();
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
