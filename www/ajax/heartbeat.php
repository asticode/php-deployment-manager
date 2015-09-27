<?php

// Include bootstrap
require_once '../../src/bootstrap.php';

header('Content-Type: application/json');
echo $oDisplayHandler->getLastBuildHistoryByProject();
die;