<?php
namespace Asticode\DeploymentManager\Entity\Webhook;

use Asticode\Toolbox\ExtendedArray;

class Payload
{
    // Attributes
    private $oRepository;
    private $aCommits;

    // Construct
    public function __construct(array $aPayload)
    {
        // Check required keys
        ExtendedArray::checkRequiredKeys($aPayload, [
            'repository',
            'commits',
        ]);

        // Initialize
        $this->oRepository = new Repository($aPayload['repository']);
        foreach ($aPayload['commits'] as $aCommit) {
            $this->aCommits[] = new Commit($aCommit);
        }
    }

    public function getRepository()
    {
        return $this->oRepository;
    }

    public function getCommits()
    {
        return $this->aCommits;
    }
}