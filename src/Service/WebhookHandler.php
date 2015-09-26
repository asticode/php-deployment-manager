<?php
namespace Asticode\DeploymentManager\Service;

use Asticode\DataMapper\DataMapper;
use Asticode\DeploymentManager\Entity\Webhook\Payload;
use Asticode\Toolbox\ExtendedString;
use Psr\Log\LoggerInterface;
use RuntimeException;

class WebhookHandler
{
    // Attributes
    private $oDataMapper;
    private $oLogger;
    private $aConfig;

    // Construct
    public function __construct(
        DataMapper $oDataMapper,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Initialize
        $this->oDataMapper = $oDataMapper;
        $this->oLogger = $oLogger;
        $this->aConfig = $aConfig;
    }

    public function handle($bMockPayload = false)
    {
        /** @var $oPayload \Asticode\DeploymentManager\Entity\Webhook\Payload */
        if ($bMockPayload) {
            // Mock payload
            $oPayload = new Payload($this->mockPayload());
        } else {
            // Handle request
            $oPayload = $this->handleRequest();
        }

        // Payload is valid
        if (!is_null($oPayload)) {
            // Create build
            $this->processPayload($oPayload);
        }
    }

    private function handleRequest()
    {
        // Log.
        $this->oLogger->info(sprintf(
            'IP %s is accessing the page',
            $_SERVER['REMOTE_ADDR']
        ));

        if (isset($_POST['payload']) === false) {
            // Log
            $this->oLogger->error('No payload detected');

            // Return
            return null;
        } else {
            // Parse json
            $aJson = json_decode($_POST['payload'], true);

            // Json is invalid
            if (!$aJson) {
                // Log
                $this->oLogger->error(sprintf('Unparseable payload %s', $_POST['payload']));

                // Return
                return null;
            }

            // Create payload
            try {
                $oPayload = new Payload($aJson);
            } catch(RuntimeException $oException) {
                // Log
                $this->oLogger->error(sprintf(
                    'Error while parsing payload => %s',
                    $oException->getMessage()
                ));

                // Return
                return null;
            }

            // Log
            $this->oLogger->info('Payload parsed successfully', $aJson);

            // Return
            return $oPayload;
        }
    }

    private function processPayload(Payload $oPayload)
    {
        // Loop through commits
        /** @var $oCommit \Asticode\DeploymentManager\Entity\Webhook\Commit */
        foreach ($oPayload->getCommits() as $oCommit) {
            // Initialize
            $sProjectName = $oPayload->getProjectName($oCommit);

            // Project exists
            if (array_key_exists($sProjectName, $this->aConfig['projects'])) {
                // Get repositories
                /** @var $oDeploymentBuildRepository \Asticode\DeploymentManager\Repository\Deployment\Build */
                $oDeploymentBuildRepository = $this->oDataMapper->getRepository('Deployment\\Build');
                /** @var $oDeploymentBuildHistoryRepository \Asticode\DeploymentManager\Repository\Deployment\BuildHistory */
                $oDeploymentBuildHistoryRepository = $this->oDataMapper->getRepository('Deployment\\BuildHistory');

                // Create build
                $aBuild = $oDeploymentBuildRepository->create($sProjectName, $oCommit);

                // Create build history
                $aBuildHistory = $oDeploymentBuildHistoryRepository->create($aBuild);

                // Log
                $this->oLogger->info(sprintf(
                    'Build queued for %s',
                    $sProjectName
                ));
            } else {
                // Log
                $this->oLogger->error(sprintf(
                    'Invalid project name %s',
                    $sProjectName
                ));
            }
        }
    }

    private function mockPayload()
    {
        return [
            'repository' => [
                'name' => 'test',
            ],
            'commits' => [
                [
                    'branch' => 'master',
                    'author' => 'test@asticode.com',
                    'raw_node' => '1234',
                    'message' => 'Added the best commit ever',
                ]
            ]
        ];
    }
}