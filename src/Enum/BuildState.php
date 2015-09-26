<?php
namespace Asticode\DeploymentManager\Enum;

class BuildState
{
    const QUEUED = 1;
    const MERGED = 2;
    const DISPATCHED = 3;
    const RUNNING = 4;
    const ERROR = 5;
    const CANCELLED = 6;
    const DONE = 7;
}