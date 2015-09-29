<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use Asticode\DeploymentManager\Entity\Build\Command;
use Asticode\DeploymentManager\Enum\CommandDatasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use Asticode\Toolbox\ExtendedString;
use DateTime;

class PHPHandler extends AbstractHandler
{

    public function getCommands(array $aBuild, array $aProjectConfig)
    {
        // Initialize
        $aCommands = [];
        $oNow = new DateTime();

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

        // TODO copy dist files => conf = array[path to dist] => new path without extension dist

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

        // Composer install
        $aCommands[] = new Command(
            'Composer install',
            CommandDatasource::SHELL,
            sprintf(
                'cd %s && COMPOSER_HOME="%s" %s %s install --prefer-dist',
                $sTempDirPath,
                dirname($this->aConfig['bin']['composer']),
                $this->aConfig['bin']['php'],
                $this->aConfig['bin']['composer']
            )
        );

        // Backup project directory
        $sBackupDirPath = sprintf(
            '%s/%s/%s',
            $this->aConfig['dirs']['backups'],
            $sRepositoryName,
            $sBranchName
        );
        $aCommands[] = new Command(
            sprintf('Backup source directory'),
            CommandDatasource::PHP,
            function () use ($sBackupDirPath, $aProjectConfig, $oNow) {
                // Initialize
                $aOutput = [];

                // Source folder exists
                if ($this->oFileManager->exists($aProjectConfig['source_dir'])) {
                    // Create backups folder
                    if (!$this->oFileManager->exists($sBackupDirPath)) {
                        // Create dir
                        $this->oFileManager->createDir($sBackupDirPath);

                        // Output
                        $aOutput[] = sprintf(
                            'Created directory %s',
                            $sBackupDirPath
                        );
                    }

                    // Remove old backups
                    if ($this->aConfig['nb_backups_per_project'] > 0) {
                        // Get dirs
                        $aDirs = $this->oFileManager->explore(
                            $sBackupDirPath,
                            OrderField::BASENAME,
                            OrderDirection::DESC
                        );

                        // Dirs have to be removed
                        if (count($aDirs) > 0) {
                            do {
                                // Get last dir
                                /** @var $oDirToBeRemoved \Asticode\FileManager\Entity\File */
                                $oDirToBeRemoved = array_pop($aDirs);

                                // Delete
                                $this->oFileManager->delete($oDirToBeRemoved->getPath());

                                // Output
                                $aOutput[] = sprintf(
                                    'Deleted directory %s',
                                    $oDirToBeRemoved->getPath()
                                );
                            } while (count($aDirs) > $this->aConfig['nb_backups_per_project']);
                        }
                    }

                    // Backup current project
                    $sBackupDirPath =sprintf(
                        '%s/%s',
                        $sBackupDirPath,
                        $oNow->format('YmdHis')
                    );
                    $this->oFileManager->copy($aProjectConfig['source_dir'], $sBackupDirPath);

                    // Output
                    $aOutput[] = sprintf(
                        'Copied %s to %s',
                        $aProjectConfig['source_dir'],
                        $sBackupDirPath
                    );
                } else {
                    $aOutput[] = sprintf(
                        'Path %s doesn\'t exist, nothing to back up',
                        $aProjectConfig['source_dir']
                    );
                }

                // Return
                return $aOutput;
            }
        );

        // Move temp directory
        $aCommands[] = new Command(
            sprintf('Move temp directory'),
            CommandDatasource::PHP,
            function () use ($sTempDirPath, $aProjectConfig) {
                // Initialize
                $aOutput = [];

                // Delete source dir
                if($this->oFileManager->exists($aProjectConfig['source_dir'])) {
                    // Delete
                    $this->oFileManager->delete($aProjectConfig['source_dir']);

                    // Output
                    $aOutput[] = sprintf(
                        'Deleted %s',
                        $aProjectConfig['source_dir']
                    );
                }

                // Move temp dir
                $this->oFileManager->move($sTempDirPath, $aProjectConfig['source_dir']);

                // Output
                $aOutput[] = sprintf(
                    'Moved %s to %s',
                    $sTempDirPath,
                    $aProjectConfig['source_dir']
                );

                // Return
                return $aOutput;
            }
        );

        // Return
        return $aCommands;
    }
}
