<?php
$sRootDir = __DIR__ . '/../..';
return [

    'build' => [
        'bin' => [
            'composer' => '',
            'git' => '',
            'php' => '',
        ],
        'dirs' => [
            'backups' => sprintf('%s/backups', $sRootDir),
            'gits' => sprintf('%s/gits', $sRootDir),
            'root' => $sRootDir,
            'tmp' => sprintf('%s/tmp', $sRootDir),
        ],
        'number_of_backups_per_project' => 1,
    ],

    'logger' => [
        'name' => 'replay-platform',
        'line_format' => "\033[1;30m%datetime%, %level_name%:\033[0m %message% %context% %extra%\n",
        'date_format' => 'H:i:s',
        'syslog' => [
            'ident' => 'deployment-manager',
            'facility' => LOG_USER,
            'level'    => LOG_INFO
        ],
    ],

    'pdo_options' => [
        PDO::ATTR_PERSISTENT         => false,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8', sql_mode=TRADITIONAL, time_zone=\"+00:00\", wait_timeout=600"
    ],

    'projects' => [],

];