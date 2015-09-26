<?php
namespace Asticode\DeploymentManager\Repository\Deployment;

use Asticode\DataMapper\Repository\AbstractRepository;

class BuildHistory extends AbstractRepository
{
    public function create(array $aBuild)
    {
        // Create build history
        $aBuildHistory = [
            'build_id' => $aBuild['id'],
            'project' => $aBuild['project'],
            'build_state_id' => $aBuild['build_state_id'],
            'commit_node' => $aBuild['commit_node'],
            'commit_author' => $aBuild['commit_author'],
            'commit_message' => $aBuild['commit_message'],
            'nb_of_commands' => $aBuild['nb_of_commands'],
        ];

        // Insert
        $this->oMapper->insert($aBuildHistory);

        // Return
        return $aBuildHistory;
    }
}