<?php
namespace Asticode\DeploymentManager\Mapper\Deployment;

use Asticode\DataMapper\Mapper\AbstractMapper;
use Aura\Sql\ExtendedPdoInterface;

class BuildHistory extends AbstractMapper
{
    /**
     * @param ExtendedPdoInterface $oPdo
     */
    public function __construct(ExtendedPdoInterface $oPdo)
    {
        parent::__construct($oPdo);
        $this->set('entity', 'build_history');
        $this->aJsonColumns = ['execute_log'];
    }

    public function getLastBuildHistoryByProject()
    {
        return $this->fetchAllQuery('
        SELECT bh1.*
        FROM build_history as bh1
        JOIN (
            SELECT project, MAX(created_at) as created_at
            FROM build_history as bh3
            GROUP BY project
        ) bh2 ON bh1.project = bh2.project AND bh1.created_at = bh2.created_at
        ');
    }
}