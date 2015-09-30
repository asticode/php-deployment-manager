<?php
namespace Asticode\DeploymentManager\Service\Build;

use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Enum\StepDatasource;
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
    public function __construct(
        DataMapper $oDataMapper,
        FileManager $oFileManager,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
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

    public function getSteps(array $aBuild, array $aProjectConfig)
    {
        // Get handler
        $oHandler = $this->getHandler($aProjectConfig['handler']);

        // Get steps
        return $oHandler->getSteps($aBuild, $aProjectConfig);
    }

    public function build(array &$aBuild, array $aSteps)
    {
        // Loop through steps
        /** @var $oStep \Asticode\DeploymentManager\Entity\Build\Step */
        foreach ($aSteps as $iIndex => $oStep) {
            // Log
            $this->log($aBuild, sprintf(
                '%s.%s',
                ($iIndex + 1),
                $oStep->getLabel()
            ));

            if ($oStep->getDatasource() === StepDatasource::PHP) {
                // Log
                $this->log($aBuild, call_user_func($oStep->getContent()));
            } else {
                // Log
                $this->log($aBuild, $oStep->getContent());

                // Execute
                $aOutput = [];
                $iExitStatus = ExtendedShell::exec($oStep->getContent(), $aOutput, $oStep->getTimeout(), false);

                // Log
                $this->log($aBuild, $aOutput);

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

    private function log(array &$aBuild, $aLogMessages)
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