<?php
namespace Asticode\DeploymentManager\Mapper\Deployment;

use Asticode\DataMapper\Mapper\AbstractMapper;
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
    }
}