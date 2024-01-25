<?php
/**
 *
 */
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use FishPig\CriticalCss\App\CriticalCssTest;

try {
    foreach ([__DIR__ . '/../../..', __DIR__ . '/../../../..'] as $path) {
        $bootstrapFile = $path . '/app/bootstrap.php';

        if (is_file($bootstrapFile)) {
            require_once $bootstrapFile;
            break;
        }
    }

    if (!defined('BP')) {
        throw new \RuntimeException('Cannot find Magento 2 installation.');
    }

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $objectManager = Bootstrap::create(BP, $_SERVER)->getObjectManager();
    $objectManager->get(State::class)->setAreaCode('frontend');

    $objectManager->get(CriticalCssTest::class)->doTests();

    echo 'OK';
} catch (\Throwable $e) {
    echo "\n\n" . $e->getMessage() . "\n\n";
    echo "\t" . str_replace("\n", "\n\t", $e->getTraceAsString()) . "\n\n";;
    exit($e->getCode() ?: 1);
}