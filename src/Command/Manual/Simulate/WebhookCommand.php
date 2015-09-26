<?php
namespace Asticode\DeploymentManager\Command\Manual\Simulate;

use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\DeploymentManager\Service\WebhookHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookCommand extends AbstractCommand
{
    // Attributes
    private $oWebhookHandler;

    // Construct
    public function __construct(
        WebhookHandler $oWebhookHandler,
        LoggerInterface $oLogger,
        array $aConfig
    ) {
        // Parent construct
        parent::__construct(
            'manual:simulate:webhook',
            'Simulate a webhook',
            [],
            $oLogger,
            $aConfig
        );

        // Initialize
        $this->oWebhookHandler = $oWebhookHandler;
    }

    // Execute
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        // Handle webhook
        $this->oWebhookHandler->handle(true);
    }
}