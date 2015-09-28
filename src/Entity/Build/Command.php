<?php
namespace Asticode\DeploymentManager\Entity\Build;

class Command
{
    // Attributes
    private $sLabel;
    private $iDatasource;
    private $fContent;
    private $iTimeout;

    // Construct
    public function __construct($sLabel, $iDatasource, $fContent, $iTimeout = 0)
    {
        // Initialize
        $this->sLabel = $sLabel;
        $this->iDatasource = $iDatasource;
        $this->fContent = $fContent;
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

    public function getTimeout()
    {
        return $this->iTimeout;
    }
}