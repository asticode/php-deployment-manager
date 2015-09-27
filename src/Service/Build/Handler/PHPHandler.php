<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use Asticode\DeploymentManager\Entity\Handler\Command;
use Asticode\DeploymentManager\Enum\CommandDatasource;
use Asticode\FileManager\Enum\WriteMethod;
use Asticode\Toolbox\ExtendedString;

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
            )
        );

        // Git reset in tmp dir
        $aCommands[] = new Command(
            'Git reset in tmp dir',
            CommandDatasource::SHELL,
            sprintf(
                '%s --git-dir="%s" --work-tree="%s" reset --hard',
                $this->aConfig['bin']['git'],
                $sGitDirPath,
                $sTempDirPath
            )
        );

        // Replace
        $aCommands[] = new Command(
            'Replace',
            CommandDatasource::PHP,
            function () use ($sTempDirPath, $aProjectConfig) {
                // Initialize
                $aOutputs = [];

                // Loop through files to replace
                foreach ($aProjectConfig['replace'] as $sFilePath => $aStringsToReplace) {
                    // Update filepath
                    $sFilePath = sprintf('%s%s', $sTempDirPath, $sFilePath);

                    // File exists
                    if ($this->oFileManager->exists($sFilePath)) {
                        // Get content
                        $sContent = $this->oFileManager->read($sFilePath);

                        // Loop through strings to replace
                        foreach ($aStringsToReplace as $sStringToReplace => $sReplacement) {
                            $sContent = preg_replace(sprintf(
                                '/%s/',
                                ExtendedString::pregQuote($sStringToReplace)
                            ), $sReplacement, $sContent);
                        }

                        // Overwrite
                        $this->oFileManager->write($sContent, $sFilePath, WriteMethod::OVERWRITE);

                        // Add output
                        $aOutputs[] = sprintf(
                            'Replaced values in path %s',
                            $sFilePath
                        );
                    } else {
                        // Add output
                        $aOutputs[] = sprintf(
                            'Path %s is invalid',
                            $sFilePath
                        );
                    }
                }

                // Return
                return $aOutputs;
            }
        );

        // Return
        return $aCommands;
    }
}
