<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use Asticode\DeploymentManager\Entity\Handler\Command;
use Asticode\DeploymentManager\Enum\CommandDatasource;

class PHPHandler extends AbstractHandler
{

    public function getCommands(array $aBuild, array $aProjectConfig)
    {
        // Initialize
        $aCommands = [];

        // Parse project name
        list($sRepositoryName, $sBranchName) = $this->parseProjectName($aBuild['project']);

        // Create temp directory
        $sTempDirPath = sprintf(
            '%s/%s/%s',
            $this->aConfig['dirs']['tmp'],
            $sRepositoryName,
            $sBranchName
        );
        $aCommands[] = new Command(
            sprintf('Create temp directory'),
            CommandDatasource::PHP,
            function () use ($sTempDirPath) {
                if ($this->oFileManager->exists($sTempDirPath)) {
                    return sprintf(
                        'Path %s already exists',
                        $sTempDirPath
                    );
                } else {
                    $this->oFileManager->createDir($sTempDirPath);
                    return sprintf(
                        'Created %s',
                        $sTempDirPath
                    );
                }
            }
        );

        // Git Fetch
        $sGitDirPath = sprintf(
            '%s/%s.git',
            $this->aConfig['dirs']['gits'],
            $sRepositoryName
        );
        $aCommands[] = new Command(
            'Git fetch',
            CommandDatasource::SHELL,
            sprintf(
                '%s --git-dir="%s" fetch origin',
                $this->aConfig['bin']['git'],
                $sGitDirPath
            )
        );

        // Git checkout
        $aCommands[] = new Command(
            'Git checkout',
            CommandDatasource::SHELL,
            sprintf(
                '%s --git-dir="%s" --work-tree="%s" checkout -f %s',
                $this->aConfig['bin']['git'],
                $sGitDirPath,
                $sGitDirPath,
                $sBranchName
            ),
            ['/^Already on /']
        );

        // Return
        return $aCommands;
    }
}
