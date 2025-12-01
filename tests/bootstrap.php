<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// Ensure KERNEL_CLASS is set (used by KernelTestCase to find the app kernel class)
if (!isset($_SERVER['KERNEL_CLASS'])) {
    $_SERVER['KERNEL_CLASS'] = 'App\\Kernel';
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
