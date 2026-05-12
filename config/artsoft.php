<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ARTSOFT Host
    |--------------------------------------------------------------------------
    | The hostname or IP address of the ARTSOFT server.
    */
    'host' => (getenv('ARTSOFT_HOST') ?: 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | ARTSOFT Credentials
    |--------------------------------------------------------------------------
    */
    'username' => (getenv('ARTSOFT_USERNAME') ?: 'admin'),
    'password' => (getenv('ARTSOFT_PASSWORD') ?: ''),

    /*
    |--------------------------------------------------------------------------
    | Default Company
    |--------------------------------------------------------------------------
    | The default company key to use when none is specified.
    */
    'default_company' => (getenv('ARTSOFT_DEFAULT_COMPANY') ?: 'FranciscoCardoso' . date('Y')),

    /*
    |--------------------------------------------------------------------------
    | Connection Options
    |--------------------------------------------------------------------------
    */
    'options' => [
        'encrypt'        => (getenv('ARTSOFT_ENCRYPT') !== false ? filter_var(getenv('ARTSOFT_ENCRYPT'), FILTER_VALIDATE_BOOLEAN) : true),
        'gzip'           => (getenv('ARTSOFT_GZIP') !== false ? filter_var(getenv('ARTSOFT_GZIP'), FILTER_VALIDATE_BOOLEAN) : false),
        'indent'         => (getenv('ARTSOFT_INDENT') !== false ? (int) getenv('ARTSOFT_INDENT') : 4),
        'formation'      => (getenv('ARTSOFT_FORMATION') !== false ? filter_var(getenv('ARTSOFT_FORMATION'), FILTER_VALIDATE_BOOLEAN) : false),
        'hash'           => (getenv('ARTSOFT_HASH') ?: 'SHA1'),
        'timeout'        => (getenv('ARTSOFT_TIMEOUT') !== false ? (int) getenv('ARTSOFT_TIMEOUT') : 30),
        'retry_attempts' => (getenv('ARTSOFT_RETRY_ATTEMPTS') !== false ? (int) getenv('ARTSOFT_RETRY_ATTEMPTS') : 3),
        'retry_delay'    => (getenv('ARTSOFT_RETRY_DELAY') !== false ? (int) getenv('ARTSOFT_RETRY_DELAY') : 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    | Each entry key is the company identifier used when resolving a connection.
    | - db:      The database name on the ARTSOFT server.
    | - port:    The TCP port the ARTSOFT service listens on.
    | - enabled: Set to false to temporarily disable the company.
    */
    'companies' => [
        'FranciscoCardoso' . date('Y') => [
            'db'      => (getenv('ARTSOFT_DB') ?: 'FS28'),
            'port'    => (getenv('ARTSOFT_PORT') ?: '2026'),
            'enabled' => true,
        ],
    ],

];
