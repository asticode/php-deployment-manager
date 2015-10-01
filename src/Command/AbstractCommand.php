<?php
namespace Asticode\DeploymentManager\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

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

    // Ask
    protected function ask(
        QuestionHelper $oQuestionHelper,
        InputInterface $oInput,
        OutputInterface $oOutput,
        $sLabel,
        $sDefault = null,
        $bMandatory = true,
        $aChoices = []
    ) {
        // Update label
        $sLabel = sprintf(
            '%s%s: ',
            $sLabel,
            !is_null($sDefault) ? sprintf(' [%s]', $sDefault) : ''
        );

        // Create question
        if ($aChoices === []) {
            $oQuestion = new Question($sLabel, $sDefault);
        } else {
            $oQuestion = new ChoiceQuestion($sLabel, $aChoices, $sDefault);
        }

        // Ask
        do {
            // Initialize
            $bTerminate = true;

            // Ask
            $sValue = $oQuestionHelper->ask($oInput, $oOutput, $oQuestion);

            // Mandatory
            if ($bMandatory and empty($sValue)) {
                // Output
                $oOutput->writeln('Value can\'t be blank');

                // Update terminate
                $bTerminate = false;
            }
        } while (!$bTerminate);

        // Return
        return $sValue;
    }
}