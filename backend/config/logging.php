<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'formatter_with' => [
                'format' => null,
                'date_format' => null,
                'allow_inline_line_breaks' => true,
                'ignore_empty_context_and_extra' => true,
            ],
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'domain_auth' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/domain_auth.log'),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class],
        ],
        'domain_navigation' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/domain_navigation.log'),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class],
        ],
        'domain_module_access' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/domain_module_access.log'),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class],
        ],
        'domain_profile' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/domain_profile.log'),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class],
        ],
        'domain_audit' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/domain_audit.log'),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class],
        ],
        'security_core' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/security_core.log'),
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class],
        ],
    ],

];
