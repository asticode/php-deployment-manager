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
            'nb_of_commands' => null,
        ];

        // Insert
        $aBuild['id'] = $this->oMapper->insert($aBuild);

        // Return
        return $aBuild;
    }
}