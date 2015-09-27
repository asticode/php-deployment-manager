<?php
namespace Asticode\DeploymentManager\Service\Build;

use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Enum\CommandDatasource;
use Asticode\FileManager\FileManager;
use Asticode\Toolbox\ExtendedShell;
use Asticode\Toolbox\ExtendedString;
use Psr\Log\LoggerInterface;
use RuntimeException;

class BuildHandler
{
    // Attributes
    private $oDataMapper;
    private $aHandlers;
    private $oFileManager;
    private $oLogger;
    private $aConfig;

    // Construct
    public function __construct(DataMapper $oDataMapper, FileManager $oFileManager, LoggerInterface $oLogger, array $aConfig)
    {
        // Initialize
        $this->oDataMapper = $oDataMapper;
        $this->aHandlers = [];
        $this->oFileManager = $oFileManager;
        $this->oLogger = $oLogger;
        $this->aConfig = $aConfig;
    }

    /**
     * @param $sHandlerName
     * @return \Asticode\DeploymentManager\Service\Build\Handler\HandlerInterface
     */
    private function getHandler($sHandlerName)
    {
        if (empty($this->aHandlers[$sHandlerName])) {
            // Get class name
            $sClassName = sprintf(
                '\\Asticode\\DeploymentManager\\Service\\Build\\Handler\\%1$sHandler',
                ExtendedString::toCamelCase($sHandlerName, '_', true)
            );

            // Check class exists
            if (!class_exists($sClassName)) {
                throw new RuntimeException(sprintf(
                    'Invalid class name %s',
                    $sClassName
                ));
            }

            // Create repository
            $this->aHandlers[$sHandlerName] = new $sClassName(
                $this->oFileManager,
                $this->aConfig
            );
        }
        return $this->aHandlers[$sHandlerName];
    }

    public function getCommands(array $aBuild, array $aProjectConfig)
    {
        // Get handler
        $oHandler = $this->getHandler($aProjectConfig['handler']);

        // Get commands
        return $oHandler->getCommands($aBuild, $aProjectConfig);
    }

    public function build(array &$aBuild, array $aCommands)
    {
        // Loop through commands
        /** @var $oCommand \Asticode\DeploymentManager\Entity\Handler\Command */
        foreach ($aCommands as $iIndex => $oCommand) {
            // Log
            $sLogMessage = sprintf(
                '%s.%s',
                ($iIndex + 1),
                $oCommand->getLabel()
            );
            $this->buildExecuteLog($aBuild, $sLogMessage);

            // PHP
            if ($oCommand->getDatasource() === CommandDatasource::PHP) {
                $this->buildExecuteLog($aBuild, call_user_func($oCommand->getContent()));
            } else {
                // Log
                $this->buildExecuteLog($aBuild, $oCommand->getContent());

                // Execute
                list ($aStdOut, $aStdErr, $iExitStatus) = ExtendedShell::exec(
                    $oCommand->getContent(),
                    $oCommand->getTimeout(),
                    false
                );

                // Log
                $this->buildExecuteLog($aBuild, array_merge($aStdOut, $aStdErr));

                // Error
                if ($iExitStatus !== 0) {
                    throw new RuntimeException(sprintf(
                        'Invalid exit status %s',
                        $iExitStatus
                    ));
                }
            }
        }
    }

    private function buildExecuteLog(array &$aBuild, $aLogMessages)
    {
        if (!is_array($aLogMessages)) {
            $aLogMessages = [$aLogMessages];
        }

        foreach ($aLogMessages as $sLogMessage) {
            if (trim($sLogMessage) !== '') {
                $this->oLogger->info(trim($sLogMessage));
                $aBuild['execute_log'][] = trim($sLogMessage);
            }
        }
    }
}