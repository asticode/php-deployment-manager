<?php
namespace Asticode\DeploymentManager\Mapper\Deployment;

use Asticode\DataMapper\Mapper\AbstractMapper;
use Asticode\DeploymentManager\Enum\BuildState;
use Aura\Sql\ExtendedPdoInterface;

class Build extends AbstractMapper
{
    /**
     * @param ExtendedPdoInterface $oPdo
     */
    public function __construct(ExtendedPdoInterface $oPdo)
    {
        parent::__construct($oPdo);
        $this->set('entity', 'build');
        $this->aJsonColumns = ['execute_log'];
    }

    public function getBuildsToBeMerged()
    {
        return $this->fetchAllQuery('
            SELECT *
            FROM build b
            WHERE b.project IN (
                SELECT b2.project
                FROM build b2
                WHERE b2.build_state_id = :build_state_id
                GROUP BY b2.project
                HAVING COUNT(*) > 1
            )
            ORDER BY project ASC, created_at DESC
        ', [
            'build_state_id' => BuildState::QUEUED,
        ]);
    }

    public function getNextBuildReadyToBeDispatched()
    {
        return $this->fetchOneQuery('
            SELECT *
            FROM build b
            WHERE b.created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            AND build_state_id = :build_state_id
            ORDER BY created_at ASC
            LIMIT 1
        ', [
            'build_state_id' => BuildState::QUEUED,
        ]);
    }
}