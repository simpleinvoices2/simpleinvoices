<?php
return [
    'debug' => [
        /**
         * Sets blackbird debugging.
         */
        'level' => 'All',
        /**
         * Sets PHP error reporting.
         */
        'error_reporting' => E_ALL,
    ],
    'phpSettings' => [
        'display_startup_errors' => 1,
        'display_errors' => 1,
        'log_errors' => 1,
        'error_log' => 'tmp/log/php.log',
    ],
    'confirm' => [
        'deleteLineItem' => '',
    ],
];