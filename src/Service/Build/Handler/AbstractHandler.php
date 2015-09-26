<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use Asticode\FileManager\FileManager;

abstract class AbstractHandler implements HandlerInterface
{
    // Attributes
    protected $oFileManager;
    protected $aConfig;

    // Construct
    public function __construct(FileManager $oFileManager, array $aConfig)
    {
        // Initialize
        $this->oFileManager = $oFileManager;
        $this->aConfig = $aConfig;
    }

    protected function parseProjectName($sProjectName)
    {
        return explode(':', $sProjectName);
    }
}