<?php
namespace Asticode\DeploymentManager\Command\Build;

use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Command\AbstractCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends AbstractCommand
{
    // Construct
    public function __construct(
        DataMapper $oDataMapper,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'provider:watch',
            'Loop through replay providers and create a READY_TO_BE_DISPATCHED replay in the Database with both the ' .
            'paths to the metadata and the video file',
            [
                ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Number of seconds script will run', 1],
            ],
            $oDataMapper,
            $oLogger,
            $aConfig
        );
    }

    // Execute
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        // Execute
        $this->executeAndLoopCommand($oInput, $oOutput, [
            'callback' => [
                $this,
                'executeSpecific',
            ],
            'params' => [],
        ]);
    }

    // Specific execute
    protected function executeSpecific(InputInterface $oInput, OutputInterface $oOutput)
    {

    }
}