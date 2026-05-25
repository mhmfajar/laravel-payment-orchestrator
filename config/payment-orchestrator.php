<?php

// Reuse the core default config so Laravel stays aligned with framework-free usage.
$coreConfigPaths = array(
    __DIR__ . '/../../core/config/payment-orchestrator.php',
    __DIR__ . '/../../php-payment-orchestrator/config/payment-orchestrator.php',
);

if (function_exists('base_path')) {
    $coreConfigPaths[] = base_path('vendor/mhmfajar/php-payment-orchestrator/config/payment-orchestrator.php');
}

foreach ($coreConfigPaths as $coreConfigPath) {
    if (is_file($coreConfigPath)) {
        return require $coreConfigPath;
    }
}

throw new RuntimeException('Unable to locate the php-payment-orchestrator core configuration file.');
