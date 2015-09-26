<?php
namespace Asticode\DeploymentManager\Entity\Webhook;

use Asticode\Toolbox\ExtendedArray;
use RuntimeException;

class Repository
{
    // Attributes
    private $sName;

    // Construct
    public function __construct(array $aRepository)
    {
        // Check required keys
        ExtendedArray::checkRequiredKeys($aRepository, [
            'name',
        ]);

        // Initialize
        $this->sName = $aRepository['name'];
    }

    public function getName()
    {
        return $this->sName;
    }


}