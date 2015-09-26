<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

interface HandlerInterface
{
    public function getCommands(array $aBuild, array $aProject);
}