<?php
$oDi = require __DIR__.'/../app/bootstrap.php';
$oApp = $oDi->getService('application');
$oApp->run();