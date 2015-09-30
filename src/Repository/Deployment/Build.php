<?php
namespace Asticode\DeploymentManager\Repository\Deployment;

use Asticode\DataMapper\Repository\AbstractRepository;
use Asticode\DeploymentManager\Entity\Webhook\Commit;
use Asticode\DeploymentManager\Enum\BuildState;

class Build extends AbstractRepository
{
    public function create($sProjectName, Commit $oCommit)
    {
        // Create build
        $aBuild = [
            'project' => $sProjectName,
            'build_state_id' => BuildState::QUEUED,
            'commit_node' => $oCommit->getNode(),
            'commit_author' => $oCommit->getAuthor(),
            'commit_message' => $oCommit->getMessage(),
            'nb_of_steps' => null,
            'execute_log' => [],
        ];

        // Insert
        $aBuild['id'] = $this->oMapper->insert($aBuild);

        // Return
        return $aBuild;
    }

    public function getBuildsToBeMergedByProjects()
    {
        // Get builds to be merged
        /** @var $oMapper \Asticode\DeploymentManager\Mapper\Deployment\Build */
        $oMapper = $this->oMapper;
        $aResults = $oMapper->getBuildsToBeMerged();

        // Store builds by projects
        $aBuildsToBeMerged = [];
        foreach ($aResults as $aBuild) {
            // Create array
            if (!isset($aBuildsToBeMerged[$aBuild['project']])) {
                $aBuildsToBeMerged[$aBuild['project']] = [];
            }

            // Store build
            $aBuildsToBeMerged[$aBuild['project']][] = $aBuild;
        }

        // Return
        return $aBuildsToBeMerged;
    }

    public function getNextBuildReadyToBeDispatched()
    {
        // Get next build ready to be dispatched
        /** @var $oMapper \Asticode\DeploymentManager\Mapper\Deployment\Build */
        $oMapper = $this->oMapper;
        return $oMapper->getNextBuildReadyToBeDispatched();
    }

    public function updateStateId(array $aBuild, $iBuildStateId)
    {
        // Update build
        $aBuild['build_state_id'] = $iBuildStateId;

        // Execute
        $this->oMapper->update([
            'id' => $aBuild['id'],
        ], [
            'build_state_id' => $iBuildStateId,
        ]);

        // Delete
        if (in_array(
            $iBuildStateId,
            [BuildState::MERGED, BuildState::CANCELLED, BuildState::ERROR, BuildState::DONE]
        )) {
            $this->oMapper->delete([
                'id' => $aBuild['id'],
            ]);
        }

        // Return
        return $aBuild;
    }

    public function getNextBuildDispatched()
    {
        return $this->oMapper->fetchOne([
            'build_state_id' => BuildState::DISPATCHED,
        ], 'created_at ASC');
    }

    public function updateNumberOfSteps(array $aBuild, $iNumberOfSteps)
    {
        // Update build
        $aBuild['nb_of_steps'] = $iNumberOfSteps;
        $aBuild['build_state_id'] = BuildState::RUNNING;

        // Execute
        $this->oMapper->update([
            'id' => $aBuild['id'],
        ], [
            'nb_of_commands' => $aBuild['nb_of_steps'],
            'build_state_id' => $aBuild['build_state_id'],
        ]);

        // Return
        return $aBuild;
    }
}