<?php
namespace Asticode\DeploymentManager\Entity\Handler;

class Command
{
    // Attributes
    private $sLabel;
    private $iDatasource;
    private $fContent;
    private $aIgnoreErrorPatterns;
    private $iTimeout;

    // Construct
    public function __construct($sLabel, $iDatasource, $fContent, array $aIgnoreErrorPatterns = [], $iTimeout = 0)
    {
        // Initialize
        $this->sLabel = $sLabel;
        $this->iDatasource = $iDatasource;
        $this->fContent = $fContent;
        $this->aIgnoreErrorPatterns = $aIgnoreErrorPatterns;
        $this->iTimeout = $iTimeout;
    }

    public function getLabel()
    {
        return $this->sLabel;
    }

    public function getDatasource()
    {
        return $this->iDatasource;
    }

    public function getContent()
    {
        return $this->fContent;
    }

    public function getIgnoreErrorPatterns()
    {
        return $this->aIgnoreErrorPatterns;
    }

    public function getTimeout()
    {
        return $this->iTimeout;
    }
}