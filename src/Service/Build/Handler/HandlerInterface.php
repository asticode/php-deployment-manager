<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use DateTime;

interface HandlerInterface
{
    public function getSteps(
        array $aBuild,
        array $aProjectConfig
    );
    public function getSpecificSteps(
        array &$aSteps,
        array $aProjectConfig,
        $sTempDirPath,
        $sGitDirPath,
        $sBackupDirPath,
        $sSourceDirPath,
        $sBranchName,
        DateTime $oNow
    );
}
