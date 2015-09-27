<?php

use Symfony\Component\Console\Input\ArgvInput;
use Psr\Log\LogLevel;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Asticode\FileManager\FileManager;
use Asticode\Toolbox\ExtendedArray;
use Aura\Sql\ExtendedPdo;
use Aura\Sql\ConnectionLocator;
use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Service;

// Set options
set_time_limit(0);
date_default_timezone_set('UTC');

// Include Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Get config
$aConfig = call_user_func(function () {
    $aGlobalConfig = require __DIR__ . '/../app/config/global.php';
    $aLocalConfig = file_exists(__DIR__ . '/../app/config/local.php') ? require __DIR__ . '/../app/config/local.php' : [];
    return ExtendedArray::extendWithDefaultValues($aLocalConfig, $aGlobalConfig);
});

// Get inputs
$oInput = new ArgvInput();

// Build logger
/** @var \Psr\Log\LoggerInterface $oLogger */
$oLogger = call_user_func(function () use ($aConfig, $oInput) {
    // Get min level message
    if ($oInput->hasParameterOption(['-v', '-vv', '-vvv', '--verbose'])) {
        $iMinMsgLevel = LogLevel::DEBUG;
    } else {
        $iMinMsgLevel = LogLevel::INFO;
    }

    // Build handlers
    $oHandler = new StreamHandler(
        'php://stdout',
        $iMinMsgLevel
    );
    $oSyslogHandler = new SyslogHandler(
        $aConfig['logger']['syslog']['ident'],
        $aConfig['logger']['syslog']['facility'],
        $aConfig['logger']['syslog']['level']
    );

    // Set formatter
    $oLineFormatter = new LineFormatter(
        $aConfig['logger']['line_format'],
        $aConfig['logger']['date_format'],
        false,
        true
    );
    $oHandler->setFormatter($oLineFormatter);

    // Build logger
    return new Logger(
        $aConfig['logger']['name'],
        [
            $oHandler,
            $oSyslogHandler
        ]
    );
});

// Build Connection Locator
/* @var $oDbConnectionLocator \Aura\Sql\ConnectionLocator */
$oDbConnectionLocator = call_user_func(function () use ($aConfig) {
    // Initialize
    $aWrite = $aRead = [];

    // Read & Write configurations
    foreach ($aConfig['datasources'] as $sAdapterVisibility => $aAdapters) {
        foreach ($aAdapters as $sAdapterName => $aAdapterConfig) {
            ${'a'.ucfirst($sAdapterVisibility)}[$sAdapterName] = function () use ($aAdapterConfig, $aConfig) {
                return new ExtendedPdo(
                    "mysql:host={$aAdapterConfig['hostname']};dbname={$aAdapterConfig['database']};",
                    $aAdapterConfig['username'],
                    $aAdapterConfig['password'],
                    $aConfig['pdo_options']
                );
            };
        }
    }

    return new ConnectionLocator(null, $aRead, $aWrite);
});

// Build File Handler
$oFileManager = new FileManager([]);
$oFileManager->addHandler('local', 'UNIX', [], true);

// Build data mapper
$oDataMapper = new DataMapper(
    $oDbConnectionLocator,
    'Asticode\\DeploymentManager'
);

// Build services
$oWebhookHandler = new Service\Webhook\WebhookHandler(
    $oDataMapper,
    $oLogger,
    $aConfig
);
$oBuildHandler = new Service\Build\BuildHandler(
    $oDataMapper,
    $oFileManager,
    $oLogger,
    $aConfig['build']
);
$oDisplayHandler = new Service\Display\DisplayHandler(
    $oDataMapper,
    $oLogger,
    $aConfig
);