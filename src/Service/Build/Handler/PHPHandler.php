<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use Asticode\DeploymentManager\Entity\Build\Step;
use Asticode\DeploymentManager\Enum\StepDatasource;
use DateTime;

class PHPHandler extends AbstractHandler
{

    public function getSpecificSteps(
        array &$aSteps,
        array $aProjectConfig,
        $sTempDirPath,
        $sGitDirPath,
        $sBackupDirPath,
        $sSourceDirPath,
        $sBranchName,
        DateTime $oNow
    ) {
        // Copy
        $aSteps[] = $this->stepCopy(
            $sTempDirPath,
            $aProjectConfig['copy']
        );

        // Replace
        $aSteps[] = $this->stepReplace(
            $sTempDirPath,
            $aProjectConfig['replace']
        );

        // Composer install
        $aSteps[] = $this->stepComposerInstall(
            $sTempDirPath,
            $this->aConfig['bin']['composer'],
            $this->aConfig['bin']['php']
        );

        // Return
        return $aSteps;
    }

    private function stepComposerInstall($sTempDirPath, $sComposerBinaryPath, $sPHPBinaryPath)
    {
        return new Step(
            'Composer install',
            StepDatasource::SHELL,
            sprintf(
                'cd %s && COMPOSER_HOME="%s" %s %s install --prefer-dist',
                $sTempDirPath,
                dirname($sComposerBinaryPath),
                $sPHPBinaryPath,
                $sComposerBinaryPath
            )
        );
    }
}
