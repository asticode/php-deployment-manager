<?php
namespace Asticode\DeploymentManager\Entity\Webhook;

use Asticode\Toolbox\ExtendedArray;

class Commit
{
    // Attributes
    private $sBranch;
    private $sAuthor;
    private $sNode;
    private $sMessage;

    // Construct
    public function __construct(array $aCommit)
    {
        // Check required keys
        ExtendedArray::checkRequiredKeys($aCommit, [
            'branch',
            'author',
            'raw_node',
            'message',
        ]);

        // Initialize
        $this->sBranch = $aCommit['branch'];
        $this->sAuthor = $aCommit['author'];
        $this->sNode = $aCommit['raw_node'];
        $this->sMessage = $aCommit['message'];
    }

    public function getBranch()
    {
        return $this->sBranch;
    }

    public function getAuthor()
    {
        return $this->sAuthor;
    }

    public function getNode()
    {
        return $this->sNode;
    }

    public function getMessage()
    {
        return $this->sMessage;
    }
}