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
}