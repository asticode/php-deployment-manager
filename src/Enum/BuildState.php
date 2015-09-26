<?php
namespace Asticode\DeploymentManager\Enum;

class BuildState
{
    const QUEUED = 1;
    const MERGED = 2;
    const RUNNING = 3;
    const ERROR = 4;
    const CANCELLED = 5;
}