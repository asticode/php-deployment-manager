<?php
namespace Asticode\DeploymentManager\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    // Attributes
    protected $oLogger;
    protected $aConfig;

    // Construct
    public function __construct(
        $sName,
        $sDescription,
        array $aOptions,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct($sName);

        // Configure
        $this->configureCommand($sDescription, $aOptions);

        // Initialization
        $this->oLogger = $oLogger;
        $this->aConfig = $aConfig;
    }

    // Configure
    protected function configureCommand($sDescription, array $aOptions)
    {
        // Set description
        $this->setDescription($sDescription);

        // Set options
        foreach ($aOptions as $aOption) {
            call_user_func_array([
                $this,
                'addOption'
            ], $aOption);
        }
    }

    // Execute
    protected function executeAndLoopCommand(InputInterface $oInput, OutputInterface $oOutput, array $aClosure)
    {
        // Initialize
        $iStartTime = microtime(true);
        $bTerminate = false;
        declare(ticks = 1);
        pcntl_signal(SIGTERM, function () use (&$bTerminate) {
            $bTerminate = true;
        });

        // Loop
        do {
            // Specific execution
            $aParams = array_merge(
                [$oInput, $oOutput],
                isset($aClosure['params']) ? $aClosure['params'] : []
            );
            call_user_func_array($aClosure['callback'], $aParams);

            // Sleep
            sleep(1);

            // Update terminate
            $bTerminate = $oInput->getOption('timeout') < (microtime(true) - $iStartTime);
        } while (!$bTerminate);
    }
}