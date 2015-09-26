<?php
namespace Asticode\DeploymentManager\Command\Manual;

use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\FileManager;
use Aura\Sql\ConnectionLocatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand
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
            'manual:init',
            'Initialize the project',
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
        // Execute SQL
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

        // Log
        $this->oLogger->info('The project has been initialized successfully');
    }
}