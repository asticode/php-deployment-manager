<?php
namespace Asticode\DeploymentManager\Service\Install;

use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\FileManager;
use Asticode\Toolbox\ExtendedArray;
use Asticode\Toolbox\ExtendedComposer;
use Asticode\Toolbox\ExtendedShell;
use Asticode\Toolbox\ExtendedString;
use Aura\Sql\ExtendedPdo;
use Composer\Script\Event;
use Exception;
use RuntimeException;

class Install
{

    public static function postInstall(Event $oEvent)
    {
        // Initialize
        $sAppDirPath = __DIR__ . '/../../../app';
        $sLocalDistConfigPath = sprintf('%s/%s', $sAppDirPath, 'install/local.php.dist');
        $sLocalConfigPath = sprintf('%s/%s', $sAppDirPath, 'config/local.php');
        $oFileManager = new FileManager([]);
        $oFileManager->addHandler('local', 'UNIX', []);

        // Make sure console is executable
        $sOutput = 'Make sure the deployment manager console is executable';
        $sErrorMessage = '';
        try {
            ExtendedShell::exec(sprintf(
                'chmod +x %s',
                sprintf('%s/%s', $sAppDirPath, 'console')
            ));
        } catch (Exception $oException) {
            // Update error message
            $sErrorMessage = $oException->getMessage();
        }

        // No error
        if ($sErrorMessage === '') {
            // Output
            $oEvent->getIO()->write(sprintf("\n%s: OK", $sOutput));

            // Copy dist config
            $sOutput = 'Copy the local dist config';
            $sErrorMessage = '';
            try {
                $oFileManager->copy(
                    $sLocalDistConfigPath,
                    $sLocalConfigPath
                );
            } catch (Exception $oException) {
                // Update error message
                $sErrorMessage = $oException->getMessage();
            }

            // Error
            if ($sErrorMessage === '') {
                // Output
                $oEvent->getIO()->write(sprintf("\n%s: OK", $sOutput));

                // Explain
                $oEvent->getIO()->write(
                    "\nTo install the manager, you need a valid UTF-8 database as well as a user with " .
                    "read/write privileges on it. Once you have it, please fill in the information below:\n"
                );
                $sOutput = 'Update local config parameters';

                // Get local config content
                $sLocalConfigContent = file_get_contents($sLocalConfigPath);

                // Get values to ask
                $aValuesToAsk = [
                    '%DATASOURCE_HOSTNAME%' => [
                        'label' => 'database host',
                        'default' => 'localhost',
                        'mandatory' => true,
                    ],
                    '%DATASOURCE_DATABASE%' => [
                        'label' => 'database name',
                        'default' => 'deployment',
                        'mandatory' => true,
                    ],
                    '%DATASOURCE_USERNAME%' => [
                        'label' => 'database user name',
                        'mandatory' => true,
                    ],
                    '%DATASOURCE_PASSWORD%' => [
                        'label' => 'database user password',
                        'mandatory' => false,
                    ],
                    '%BUILD_nb_backups_per_project%' => [
                        'label' => 'number of backups kept per project',
                        'default' => '2',
                        'mandatory' => true,
                    ],
                    '%BUILD_BIN_COMPOSER%' => [
                        'label' => 'full path to composer binary',
                        'default' => '/usr/bin/composer',
                        'mandatory' => true,
                        'binary_check_command' => '%s -v',
                    ],
                    '%BUILD_BIN_GIT%' => [
                        'label' => 'full path to git binary',
                        'default' => '/usr/local/bin/git',
                        'mandatory' => true,
                        'binary_check_command' => '%s --version',
                    ],
                    '%BUILD_BIN_PHP%' => [
                        'label' => 'full path to php binary',
                        'default' => '/usr/bin/php',
                        'mandatory' => true,
                        'binary_check_command' => '%s -v',
                    ],
                ];

                // Loop through values to ask
                foreach ($aValuesToAsk as $sKeyToReplace => $aValueToAsk) {
                    // Loop
                    do {
                        // Initialize
                        $bTerminate = true;

                        // Get value
                        $sValue = ExtendedComposer::askValue(
                            $oEvent,
                            $aValueToAsk['label'],
                            isset($aValueToAsk['default']) ? $aValueToAsk['default'] : null,
                            $aValueToAsk['mandatory']
                        );

                        // Check binary
                        if (isset($aValueToAsk['binary_check_command'])) {
                            // Execute
                            $aOutput = [];
                            try {
                                ExtendedShell::exec(sprintf(
                                    $aValueToAsk['binary_check_command'],
                                    $sValue
                                ), $aOutput);
                            } catch (RuntimeException $oException) {
                                // Ouput
                                $oEvent->getIO()->writeError(sprintf(
                                    "\n/!\\ Invalid path %s with error %s\n",
                                    $sValue,
                                    implode(', ', ExtendedArray::clean($aOutput))
                                ));

                                // Update terminate
                                $bTerminate = false;
                            }
                        }
                    } while (!$bTerminate);

                    // Replace config
                    $sLocalConfigContent = preg_replace(
                        sprintf(
                            '/%s/',
                            ExtendedString::pregQuote($sKeyToReplace)
                        ),
                        $sValue,
                        $sLocalConfigContent
                    );
                }

                // Put local config content
                file_put_contents($sLocalConfigPath, $sLocalConfigContent);

                // Output
                $oEvent->getIO()->write(sprintf("\n%s: OK", $sOutput));

                // Get config
                $aConfig = ExtendedArray::extendWithDefaultValues(
                    require __DIR__ . '/../../../app/config/local.php',
                    require __DIR__ . '/../../../app/config/global.php'
                );

                // Build extended PDO
                $aDatasourceConfig = $aConfig['datasources']['write']['deployment'];
                $oExtendedPDO = new ExtendedPdo(
                    "mysql:host={$aDatasourceConfig['hostname']};" .
                    "dbname={$aDatasourceConfig['database']};",
                    $aDatasourceConfig['username'],
                    $aDatasourceConfig['password'],
                    $aConfig['pdo_options']
                );

                // Execute SQL commands
                $sOutput = 'Execute SQL commands';
                $sErrorMessage = '';
                try {
                    // Get SQL files
                    $aSQLFiles = $oFileManager->explore(__DIR__ . '/../../../sql', OrderField::BASENAME);

                    // Loop through SQL files
                    /** @var $oSQLFile \Asticode\FileManager\Entity\File */
                    foreach ($aSQLFiles as $oSQLFile) {
                        // Split statements
                        $aStatements = explode(';', $oFileManager->read($oSQLFile->getPath()));

                        // Loop through statements
                        foreach ($aStatements as $sStatement) {
                            if ($sStatement !== '') {
                                $oExtendedPDO->exec($sStatement);
                            }
                        }
                    }
                } catch (Exception $oException) {
                    // Get error message
                    $sErrorMessage = $oException->getMessage();
                }

                // Error
                if ($sErrorMessage === '') {
                    // Output
                    $oEvent->getIO()->write(sprintf("\n%s: OK", $sOutput));

                    // Create dirs
                    $sOutput = 'Create directories';
                    $aDirsToCreate = [
                        'backups',
                        'gits',
                        'tmp',
                    ];

                    // Check keys exist
                    ExtendedArray::checkRequiredKeys($aConfig['build']['dirs'], $aDirsToCreate);

                    // Loop through dirs to create
                    foreach ($aDirsToCreate as $sDirToCreate) {
                        // Create dir
                        $oFileManager->createDir($aConfig['build']['dirs'][$sDirToCreate]);
                    }

                    // Output
                    $oEvent->getIO()->write(sprintf("\n%s: OK\n", $sOutput));

                    // Conclude
                    $oEvent->getIO()->write(
                        "\nInstallation successful!\n\nYou can now add a new project with\n\n" .
                        "    $ ./app/console project:add\n\nOr remove a project with\n\n" .
                        "    $ ./app/console project:remove\n"
                    );
                } else {
                    // Output
                    $oEvent->getIO()->write(sprintf("\n%s: KO\n", $sOutput));

                    // Throw exception
                    throw new RuntimeException($sErrorMessage);
                }
            } else {
                // Output
                $oEvent->getIO()->write(sprintf("\n%s: KO\n", $sOutput));

                // Throw exception
                throw new RuntimeException($sErrorMessage);
            }
        } else {
            // Output
            $oEvent->getIO()->write(sprintf("\n%s: KO\n", $sOutput));

            // Throw exception
            throw new RuntimeException($sErrorMessage);
        }
    }
}
