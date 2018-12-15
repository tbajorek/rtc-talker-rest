<?php

// Load environment variables
try {
    $dotenv = new \Dotenv\Dotenv(dirname(__DIR__));
    $dotenv->load();
    $dotenv->required('DEV_MODE')->isBoolean();
    $dotenv->required('DB_PORT')->isInteger();
    $dotenv->required(['DB_DRIVER', 'DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET']);
} catch (\Dotenv\Exception\ValidationException $e) {
    die($e->getMessage());
} catch (\Dotenv\Exception\InvalidPathException $e) {}

return [
    'settings' => [
        'displayErrorDetails' => getenv('DEV_MODE'), // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'doctrine' => [
            // if true, metadata caching is forcefully disabled
            'dev_mode' => getenv('DEV_MODE'),
            // path where the compiled metadata info will be cached
            // make sure the path exists and it is writable
            'cache_dir' => __DIR__ . '/../var/doctrine',
            // you should add any other path containing annotated entity classes
            'metadata_dirs' => [__DIR__ . '/Model'],
            'connection' => [
                'driver' => getenv('DB_DRIVER'),
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT'),
                'dbname' => getenv('DB_NAME'),
                'user' => getenv('DB_USERNAME'),
                'password' => getenv('DB_PASSWORD'),
                'charset' => getenv('DB_CHARSET')
            ]
        ]
    ],
];

function PMA_isRememberSortingOrder($analyzed_sql_results)
{
    return $GLOBALS['cfg']['RememberSorting']
        && ! ($analyzed_sql_results['is_count']
            || $analyzed_sql_results['is_export']
            || $analyzed_sql_results['is_func']
            || $analyzed_sql_results['is_analyse'])
        && $analyzed_sql_results['select_from']
        && ((empty($analyzed_sql_results['select_expr']))
            || ((count($analyzed_sql_results['select_expr']) == 1)
            && ($analyzed_sql_results['select_expr'][0] == '*')))
        && count($analyzed_sql_results['select_tables']) == 1;
}
