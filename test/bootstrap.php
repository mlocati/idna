<?php

require_once dirname(__DIR__).'/autoload.php';

spl_autoload_register(
    function ($class) {
        if (strpos($class, 'MLocati\\IDNA\\Tests') !== 0) {
            return;
        }
        $file = __DIR__.DIRECTORY_SEPARATOR.'tests'.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('MLocati\\IDNA\\Tests'))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
);
