<?php
namespace Asticode\DeploymentManager\Service\Display;

use Asticode\DataMapper\DataMapper;
use Psr\Log\LoggerInterface;

class DisplayHandler
{
    // Attributes
    private $oDataMapper;
    private $oLogger;
    private $aConfig;

    // Construct
    public function __construct(
        DataMapper $oDataMapper,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Initialize
        $this->oDataMapper = $oDataMapper;
        $this->oLogger = $oLogger;
        $this->aConfig = $aConfig;
    }

    public function handle()
    {
        echo 'caca';
    }

}