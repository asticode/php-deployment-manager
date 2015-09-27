<?php

// Include bootstrap
require_once '../src/bootstrap.php';

?>
<!DOCTYPE html>
<html class="full-height">
<head>
    <link rel="icon" type="image/png" href="/public/images/favicon.png"/>
    <title>Asticode Deployment</title>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8">
    <meta name="description" content="Asticode Deployment">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" media="all" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap-theme.min.css" media="all" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" media="all" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400" media="all" rel="stylesheet">
    <link href="/public/css/main.css" media="all" rel="stylesheet">
</head>
<body class="full-height">
<div class="container-fluid full-height" style="padding-top: 20px">
    <div class="row">
        <div class="col-xs-12" id="content"></div>
    </div>
</div>
<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/public/js/main.js"></script>
<script>
    $(document).ready(function () {
        asticode.deployment.init(<?php echo $oDisplayHandler->getLastBuildHistoryByProject() ?>);
    });
</script>
</body>
</html>
