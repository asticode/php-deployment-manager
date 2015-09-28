<?php
namespace Asticode\DeploymentManager\Command\Manager;

use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\Toolbox\ExtendedShell;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends AbstractCommand
{
    // Construct
    public function __construct(
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'manager:check',
            'Sanity check of the deployment manager',
            [],
            $oLogger,
            $aConfig
        );
    }

    // Execute
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        // Initialize
        $sCheck = '';

        // Check composer
        $this->checkBinary($sCheck, 'Composer', $this->aConfig['build']['bin']['composer'], '%s -v');

        // Check git
        $this->checkBinary($sCheck, 'Git', $this->aConfig['build']['bin']['git'], '%s --version');

        // Check php
        $this->checkBinary($sCheck, 'PHP', $this->aConfig['build']['bin']['php'], '%s -v');

        // Display number of projects
        $sCheck .= sprintf(
            '%s %s configured%s',
            count($this->aConfig['projects']),
            count($this->aConfig['projects']) <= 1 ? 'project' : 'projects',
            PHP_EOL
        );

        // Display
        echo $sCheck;
    }

    private function checkBinary(&$sCheck, $sLabel, $sPath, $sVersionCommand)
    {
        if ($sPath !== '') {
            // Check version
            list ($aStdOut, $aStdErr, $iExitStatus) = ExtendedShell::exec(sprintf(
                $sVersionCommand,
                $sPath
            ), 0, false);

            // Path is valid
            if ($iExitStatus === 0) {
                $sCheck .= sprintf(
                    '%s binary is OK%s',
                    $sLabel,
                    PHP_EOL
                );
            } else {
                $sCheck .= sprintf(
                    '%s binary is not OK at path %s with error %s%s',
                    $sLabel,
                    $sPath,
                    implode("\n", $aStdErr),
                    PHP_EOL
                );
            }
        } else {
            $sCheck .= sprintf(
                '! %s binary is not set !%s',
                $sLabel,
                PHP_EOL
            );
        }
    }
}