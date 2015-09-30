<?php
namespace Asticode\DeploymentManager\Service\Build\Handler;

use Asticode\DeploymentManager\Entity\Build\Step;
use Asticode\DeploymentManager\Enum\StepDatasource;
use Asticode\FileManager\Enum\OrderDirection;
use Asticode\FileManager\Enum\OrderField;
use Asticode\FileManager\Enum\WriteMethod;
use Asticode\FileManager\FileManager;
use Asticode\Toolbox\ExtendedString;
use DateTime;

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

    protected function parseProjectName(array $aBuild)
    {
        return explode(':', $aBuild['project']);
    }

    public function getSteps(array $aBuild, array $aProjectConfig)
    {
        // Initialize
        $aSteps = [];
        $oNow = new DateTime();

        // Parse project name
        list($sRepositoryName, $sBranchName) = $this->parseProjectName($aBuild);

        // Initialize temp directory
        $sTempDirPath = sprintf(
            '%s/%s/%s',
            $this->aConfig['dirs']['tmp'],
            $sRepositoryName,
            $sBranchName
        );

        // Initialize git directory
        $sGitDirPath = sprintf(
            '%s/%s.git',
            $this->aConfig['dirs']['gits'],
            $sRepositoryName
        );

        // Initialize backup directory
        $sBackupDirPath = sprintf(
            '%s/%s/%s',
            $this->aConfig['dirs']['backups'],
            $sRepositoryName,
            $sBranchName
        );

        // Get prepare steps
        $this->getPrepareSteps(
            $aSteps,
            $sTempDirPath,
            $sGitDirPath,
            $sBackupDirPath,
            $aProjectConfig['source_dir'],
            $sBranchName,
            $oNow
        );

        // Get specific steps
        $this->getSpecificSteps(
            $aSteps,
            $aProjectConfig,
            $sTempDirPath,
            $sGitDirPath,
            $sBackupDirPath,
            $aProjectConfig['source_dir'],
            $sBranchName,
            $oNow
        );

        // Get finish steps
        $this->getFinishSteps(
            $aSteps,
            $sTempDirPath,
            $aProjectConfig['source_dir']
        );

        // Return
        return $aSteps;
    }

    protected function getPrepareSteps(
        array &$aSteps,
        $sTempDirPath,
        $sGitDirPath,
        $sBackupDirPath,
        $sSourceDirPath,
        $sBranchName,
        DateTime $oNow
    ) {
        // Create temp directory
        $aSteps[] = $this->stepCreateTempDir($sTempDirPath);

        // Git fetch
        $aSteps[] = $this->stepGitFetch($sGitDirPath);

        // Git checkout
        $aSteps[] = $this->stepGitCheckout($sGitDirPath, $sBranchName);

        // Git reset
        $aSteps[] = $this->stepGitReset($sGitDirPath, $sTempDirPath);

        // Backup project
        $aSteps[] = $this->stepBackupSourceDir($sBackupDirPath, $sSourceDirPath, $oNow);
    }

    protected function stepCreateTempDir($sTempDirPath)
    {
        return new Step(
            'Create temp directory',
            StepDatasource::PHP,
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
    }

    protected function stepGitFetch($sGitDirPath)
    {
        return new Step(
            'Git fetch',
            StepDatasource::SHELL,
            sprintf(
                '%s --git-dir="%s" fetch origin',
                $this->aConfig['bin']['git'],
                $sGitDirPath
            )
        );
    }

    protected function stepGitCheckout($sGitDirPath, $sBranchName)
    {
        return new Step(
            'Git checkout',
            StepDatasource::SHELL,
            sprintf(
                '%s --git-dir="%s" --work-tree="%s" checkout -f %s',
                $this->aConfig['bin']['git'],
                $sGitDirPath,
                $sGitDirPath,
                $sBranchName
            )
        );
    }

    protected function stepGitReset($sGitDirPath, $sTempDirPath)
    {
        return new Step(
            'Git reset in tmp dir',
            StepDatasource::SHELL,
            sprintf(
                '%s --git-dir="%s" --work-tree="%s" reset --hard',
                $this->aConfig['bin']['git'],
                $sGitDirPath,
                $sTempDirPath
            )
        );
    }

    protected function stepBackupSourceDir($sBackupDirPath, $sSourceDirPath, DateTime $oNow)
    {
        return new Step(
            'Backup source directory',
            StepDatasource::PHP,
            function () use ($sBackupDirPath, $sSourceDirPath, $oNow) {
                // Initialize
                $aOutput = [];

                // Source folder exists
                if ($this->oFileManager->exists($sSourceDirPath)) {
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
                    $this->oFileManager->copy($sSourceDirPath, $sBackupDirPath);

                    // Output
                    $aOutput[] = sprintf(
                        'Copied %s to %s',
                        $sSourceDirPath,
                        $sBackupDirPath
                    );
                } else {
                    $aOutput[] = sprintf(
                        'Path %s doesn\'t exist, nothing to back up',
                        $sSourceDirPath
                    );
                }

                // Return
                return $aOutput;
            }
        );
    }

    protected function stepCopy($sTempDirPath, $aCopyConfig)
    {
        return new Step(
            'Copy',
            StepDatasource::PHP,
            function () use ($sTempDirPath, $aCopyConfig) {
                // Initialize
                $aOutputs = [];

                // Loop through files to copy
                foreach ($aCopyConfig as $sFilePath => $sFilePathAfterCopy) {
                    // Update paths
                    $sFilePath = sprintf('%s%s', $sTempDirPath, $sFilePath);
                    $sFilePathAfterCopy = sprintf('%s%s', $sTempDirPath, $sFilePathAfterCopy);

                    // File exists
                    if ($this->oFileManager->exists($sFilePath)) {
                        // Copy
                        $this->oFileManager->copy($sFilePath, $sFilePathAfterCopy);

                        // Add output
                        $aOutputs[] = sprintf(
                            'Copied %s to %s',
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
    }

    protected function stepReplace($sTempDirPath, $aReplacementConfig)
    {
        return new Step(
            'Replace',
            StepDatasource::PHP,
            function () use ($sTempDirPath, $aReplacementConfig) {
                // Initialize
                $aOutputs = [];

                // Loop through files to replace
                foreach ($aReplacementConfig as $sFilePath => $aStringsToReplace) {
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
    }

    protected function getFinishSteps(array &$aSteps, $sTempDirPath, $sSourceDirPath)
    {
        // Move temp dir
        $aSteps[] = $this->stepMoveTempDir($sTempDirPath, $sSourceDirPath);
    }

    protected function stepMoveTempDir($sTempDirPath, $sSourceDirPath)
    {
        return new Step(
            'Move temp directory',
            StepDatasource::PHP,
            function () use ($sTempDirPath, $sSourceDirPath) {
                // Initialize
                $aOutput = [];

                // Delete source dir
                if ($this->oFileManager->exists($sSourceDirPath)) {
                    // Delete
                    $this->oFileManager->delete($sSourceDirPath);

                    // Output
                    $aOutput[] = sprintf(
                        'Deleted %s',
                        $sSourceDirPath
                    );
                }

                // Move temp dir
                $this->oFileManager->move($sTempDirPath, $sSourceDirPath);

                // Output
                $aOutput[] = sprintf(
                    'Moved %s to %s',
                    $sTempDirPath,
                    $sSourceDirPath
                );

                // Return
                return $aOutput;
            }
        );
    }
}
