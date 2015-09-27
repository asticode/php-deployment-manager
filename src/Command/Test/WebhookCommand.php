<?php
namespace Asticode\DeploymentManager\Command\Test;

use Asticode\DeploymentManager\Command\AbstractCommand;
use Asticode\DeploymentManager\Service\Webhook\WebhookHandler;
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
            'test:webhook',
            'Test the webhook',
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