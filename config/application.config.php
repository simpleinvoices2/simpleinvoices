<?php
return [
    'database' => [
        'adapter' => 'pdo_mysql',
        'utf8' => 1,
        'params' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'dbname' => 'simple_invoices',
            'port' => 3306,
        ],
    ],
    'authentication' => [
        'enabled' => false,
        'http' => '',
    ],
    'export' => [
        'spreadsheet' => 'xls',
        'wordprocessor' => 'doc',
        'pdf' => [
            'screensize' => 800,
            'papersize' => 'A4',
            'leftmargin' => 15,
            'rightmargin' => 15,
            'topmargin' => 15,
            'bottommargin' => 15,
        ],
    ],
    'local' => [
        'locale' => 'en_GB',
        'precision' => 2,
    ],
    'email' => [
        'host' => 'localhost',
        'smtp_auth' => false,
        'username' => '',
        'password' => '',
        'smtpport' => 25,
        'secure' => '',
        'ack' => false,
        'use_local_sendmail' => false,
    ],
    'encryption' => [
        'default' => [
            'key' => 'this_is_the_encryption_key_change_it',
        ],
    ],
    'nonce' => [
        'key' => 'this_should_be_random_and_secret_so_change_it',
        'timelimit' => 3600,
    ],
    'version' => [
        'name' => '2013.1.beta.8',
    ],
    'debug' => [
        /**
         * Sets blackbird debugging.
         */
        'level' => 'All',
        /**
         * Sets PHP error reporting.
         */
        'error_reporting' => E_ERROR,
    ],
    'phpSettings' => [
        'date' => [
            'timezone' => 'Europe/London',
        ],
        'display_startup_errors' => 1,
        'display_errors' => 1,
        'log_errors' => 0,
        'error_log' => 'tmp/log/php.log',
    ],
    'confirm' => [
        'deleteLineItem' => '',
    ],
];