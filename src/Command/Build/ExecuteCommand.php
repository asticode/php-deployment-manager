<?php
namespace Asticode\DeploymentManager\Command\Build;

use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\DeploymentManager\Enum\BuildState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends AbstractCommand
{
    // Attributes
    private $oDataMapper;

    // Construct
    public function __construct(
        DataMapper $oDataMapper,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'build:execute',
            'Get next DISPATCHED build',
            [
                ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Number of seconds script will run', 1],
            ],
            $oLogger,
            $aConfig
        );

        // Initialize
        $this->oDataMapper = $oDataMapper;
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
        // Merge project builds
        $this->mergeProjectBuilds();

        // Prepare build
        $this->prepareNextBuild();
    }

    private function mergeProjectBuilds()
    {
        // Get repositories
        /** @var $oDeploymentBuildRepository \Asticode\DeploymentManager\Repository\Deployment\Build */
        $oDeploymentBuildRepository = $this->oDataMapper->getRepository('Deployment\\Build');
        /** @var $oDeploymentBuildHistoryRepository \Asticode\DeploymentManager\Repository\Deployment\BuildHistory */
        $oDeploymentBuildHistoryRepository = $this->oDataMapper->getRepository('Deployment\\BuildHistory');

        // Merge project builds
        $aBuildsToBeMergedByProjects = $oDeploymentBuildRepository->getBuildsToBeMergedByProjects();

        // Loop through builds to be merged by projects
        foreach ($aBuildsToBeMergedByProjects as $sProjectName => $aBuildsToBeMerged) {
            // Split newest build
            $aNewestBuild = array_shift($aBuildsToBeMerged);

            // Loop through builds to be merged
            foreach ($aBuildsToBeMerged as $aBuildToBeMerged) {
                // Udate build
                $aBuildToBeMerged = $oDeploymentBuildRepository->updateStateId($aBuildToBeMerged, BuildState::MERGED);
                $oDeploymentBuildHistoryRepository->create($aBuildToBeMerged);

                // Log
                $this->oLogger->info(sprintf(
                    'Merged build #%s in build #%s',
                    $aBuildToBeMerged['id'],
                    $aNewestBuild['id']
                ));
            }
        }
    }

    private function prepareNextBuild()
    {
        // Get repositories
        /** @var $oDeploymentBuildRepository \Asticode\DeploymentManager\Repository\Deployment\Build */
        $oDeploymentBuildRepository = $this->oDataMapper->getRepository('Deployment\\Build');
        /** @var $oDeploymentBuildHistoryRepository \Asticode\DeploymentManager\Repository\Deployment\BuildHistory */
        $oDeploymentBuildHistoryRepository = $this->oDataMapper->getRepository('Deployment\\BuildHistory');

        // Get next build ready to be dispatched
        $aBuild = $oDeploymentBuildRepository->getNextBuildReadyToBeDispatched();

        // Build is valid
        if ($aBuild !== []) {
            // Update build
            $aBuild = $oDeploymentBuildRepository->updateStateId($aBuild, BuildState::DISPATCHED);
            $oDeploymentBuildHistoryRepository->create($aBuild);

            // Log
            $this->oLogger->info(sprintf(
                'Dispatched build #%s',
                $aBuild['id']
            ));
        }
    }
}