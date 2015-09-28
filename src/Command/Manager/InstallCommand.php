<?php
namespace Asticode\DeploymentManager\Command\Manager;

use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\FileManager;
use Aura\Sql\ConnectionLocatorInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InstallCommand extends AbstractCommand
{
    // Attributes
    private $oFileManager;
    private $oDbConnectionLocator;

    // Construct
    public function __construct(
        FileManager $oFileManager,
        ConnectionLocatorInterface $oDbConnectionLocator,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'manager:install',
            'Install the deployment manager',
            [],
            $oLogger,
            $aConfig
        );

        // Initialize
        $this->oFileManager = $oFileManager;
        $this->oDbConnectionLocator = $oDbConnectionLocator;
    }

    // Execute
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        // Initialize
        /** @var $oQuestionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $oQuestionHelper = $this->getHelper('question');

        // Get confirmation question message
        if ($this->isAlreadyInstalled()) {
            $sQuestion = "\nThis will overwrite your current installation, do you want to continue ? [y|n]: ";
        } else {
            $sQuestion = "\nYou're about to install the deployment manager, do you want to continue ? [y|n]: ";
        }

        // Create confirmation question
        $oQuestion = new ConfirmationQuestion($sQuestion);

        // Ask for confirmation
        if ($oQuestionHelper->ask($oInput, $oOutput, $oQuestion)) {
            // Clean old install
            if ($this->isAlreadyInstalled()) {
                // Output
                $oOutput->writeln("\nCleaning old install.");

                // TODO
            }

            // Output
            $oOutput->write("\nExecuting SQL commands");

            // Execute SQL
            $sErrorMessage = '';
            try {
                $aSQLFiles = $this->oFileManager->explore(__DIR__ . '/../../../sql', OrderField::BASENAME);
                /** @var $oSQLFile \Asticode\FileManager\Entity\File */
                foreach ($aSQLFiles as $oSQLFile) {
                    // Split statements
                    $aStatements = explode(';', $this->oFileManager->read($oSQLFile->getPath()));

                    // Loop through statements
                    foreach ($aStatements as $sStatement) {
                        if ($sStatement !== '') {
                            $this->oDbConnectionLocator->getWrite('deployment')->exec($sStatement);
                        }
                    }
                }
            } catch (Exception $oException) {
                // Get error message
                $sErrorMessage = $oException->getMessage();
            }

            // No errors
            if ($sErrorMessage === '') {
                // Output
                $oOutput->write(": OK\n");

                // TODO Create dirs
                // TODO ask for binaries
                // TODO ask for nb of backups per projects

                // Output
                $oOutput->writeln("\nThe project has been installed successfully.\n");
            } else {
                // Output
                $oOutput->write(": KO\n");

                // Output
                $oOutput->writeln(sprintf(
                    "\n%s\n\nIt looks like your database information is wrong!!\n\nPlease correct it in " .
                    "./app/config/local.php (or re-run 'composer install') and re-run the script\n",
                    $sErrorMessage
                ));
            }
        } else {
            // Output
            $oOutput->writeln("\nInstallation has been cancelled.\n");
        }
    }

    private function isAlreadyInstalled()
    {
        return $this->oFileManager->exists(__DIR__ . '/../../../gits');
    }
}