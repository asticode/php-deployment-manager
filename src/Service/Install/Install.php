<?php
namespace Asticode\DeploymentManager\Service\Install;

use Asticode\Toolbox\ExtendedString;
use Composer\Script\Event;
use Composer\IO\ConsoleIO;

class Install
{

    public static function postInstall(Event $oEvent)
    {
        // Initialize
        $oInputOutput = $oEvent->getIO();

        // Copy dist config
        copy(__DIR__ . '/../../../app/install/local.php.dist', __DIR__ . '/../../../app/config/local.php');

        // Explain
        $oInputOutput->write("\nTo install the manager, you need a valid UTF-8 database as well as a user with ".
            "read/write privileges on it. Once you have it please fill in the information below :\n");

        // Get database information
        self::askAndReplace($oInputOutput, 'database host (e.g. "localhost"): ', '', '%DATASOURCE_HOSTNAME%', true);
        self::askAndReplace($oInputOutput, 'database name (e.g. "deployment"): ', '', '%DATASOURCE_DATABASE%', true);
        self::askAndReplace($oInputOutput, 'database username: ', '', '%DATASOURCE_USERNAME%', true);
        self::askAndReplace($oInputOutput, 'database password: ', '', '%DATASOURCE_PASSWORD%', true);

        // Conclude
        $oInputOutput->write("\nRun 'sudo chmod +x ./app/console' to make sure ./app/console is executable\n" .
            "\nAnd then run './app/console manager:install' to install the manager.\n");
    }

    private static function askAndReplace(
        ConsoleIO $oInputOutput,
        $sQuestion,
        $sDefaultValue,
        $sPattern,
        $bMandatory = false,
        $sPath = __DIR__ . '/../../../app/config/local.php'
    ) {
        // Get value
        do {
            $sValue = $oInputOutput->ask($sQuestion, $sDefaultValue);

            if ($bMandatory and $sValue === '') {
                $oInputOutput->writeError('This value is mandatory');
                $bTerminate = false;
            } else {
                $bTerminate = true;
            }
        } while (!$bTerminate);

        // Replace
        file_put_contents(
            $sPath,
            preg_replace(
                sprintf(
                    '/%s/',
                    ExtendedString::pregQuote($sPattern)
                ),
                $sValue,
                file_get_contents($sPath)
            )
        );
    }

}