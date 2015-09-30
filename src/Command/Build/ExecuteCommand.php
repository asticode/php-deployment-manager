<?php
namespace Asticode\DeploymentManager\Command\Build;

use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\DeploymentManager\Enum\BuildState;
use Asticode\DeploymentManager\Service\Build\BuildHandler;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends AbstractCommand
{
    // Attributes
    private $oDataMapper;
    private $oBuildHandler;

    // Construct
    public function __construct(
        DataMapper $oDataMapper,
        BuildHandler $oBuildHandler,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'build:execute',
            'Get next DISPATCHED build, update its status to running, execute it and finish it',
            [
                ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Number of seconds script will run', 1],
            ],
            $oLogger,
            $aConfig
        );

        // Initialize
        $this->oDataMapper = $oDataMapper;
        $this->oBuildHandler = $oBuildHandler;
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
        // Get repositories
        /** @var $oDeploymentBuildRepository \Asticode\DeploymentManager\Repository\Deployment\Build */
        $oDeploymentBuildRepository = $this->oDataMapper->getRepository('Deployment\\Build');
        /** @var $oDeploymentBuildHistoryRepository \Asticode\DeploymentManager\Repository\Deployment\BuildHistory */
        $oDeploymentBuildHistoryRepository = $this->oDataMapper->getRepository('Deployment\\BuildHistory');

        // Get next dispatched
        $aBuild = $oDeploymentBuildRepository->getNextBuildDispatched();

        // Build is valid
        if ($aBuild !== []) {
            // Project is valid
            if (array_key_exists($aBuild['project'], $this->aConfig['projects'])) {
                // Get project
                $aProject = $this->aConfig['projects'][$aBuild['project']];

                // Get steps
                $aSteps = $this->oBuildHandler->getSteps($aBuild, $aProject);

                // Update build
                $aBuild = $oDeploymentBuildRepository->updateNumberOfSteps($aBuild, count($aSteps));
                $oDeploymentBuildHistoryRepository->create($aBuild);

                try {
                    // Build project
                    $this->oBuildHandler->build($aBuild, $aSteps);

                    // Update build
                    $aBuild = $oDeploymentBuildRepository->updateStateId($aBuild, BuildState::DONE);
                    $oDeploymentBuildHistoryRepository->create($aBuild);
                } catch (RuntimeException $oException) {
                    // Error
                    $this->updateBuildError($aBuild, $oException->getMessage());
                }
            } else {
                // Log
                $this->updateBuildError($aBuild, sprintf(
                    'Invalid project name %s',
                    $aBuild['project']
                ));
            }
        }
    }

    private function updateBuildError(array $aBuild, $sErrorMessage)
    {
        // Get repositories
        /** @var $oDeploymentBuildRepository \Asticode\DeploymentManager\Repository\Deployment\Build */
        $oDeploymentBuildRepository = $this->oDataMapper->getRepository('Deployment\\Build');
        /** @var $oDeploymentBuildHistoryRepository \Asticode\DeploymentManager\Repository\Deployment\BuildHistory */
        $oDeploymentBuildHistoryRepository = $this->oDataMapper->getRepository('Deployment\\BuildHistory');

        // Log
        $this->oLogger->error($sErrorMessage);

        // Update build
        $aBuild['execute_log'][] = $sErrorMessage;
        $aBuild = $oDeploymentBuildRepository->updateStateId($aBuild, BuildState::ERROR);
        $oDeploymentBuildHistoryRepository->create($aBuild);
    }
}