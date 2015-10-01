<?php
namespace Asticode\DeploymentManager\Command\Project;

use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\FileManager\FileManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends AbstractCommand
{
    // Attributes
    private $oFileManager;

    // Construct
    public function __construct(
        FileManager $oFileManager,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'job:add',
            'Add a new job',
            [],
            $oLogger,
            $aConfig
        );

        // Initialize
        $this->oFileManager = $oFileManager;
    }

    // Execute
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        // Initialize
        /** @var $oQuestionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $oQuestionHelper = $this->getHelper('question');

        // Output
        $oOutput->writeln(
            "\nWelcome the Job Creation Wizard! Fill in the information below and you'll be able to deploy your ".
            "new project in minutes.\n"
        );

        // Get repository url
        $sRepositoryUrl = $this->ask(
            $oQuestionHelper,
            $oInput,
            $oOutput,
            'Repository URL - SSH or HTTPS'
        );

        var_dump($sRepositoryUrl);die;
    }
}