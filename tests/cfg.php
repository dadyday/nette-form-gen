<?php
define('PATH_APP', __DIR__.'/app/');
define('PATH_TEMP', __DIR__.'/temp/');
define('PATH_LOG', __DIR__.'/log/');

@mkdir(PATH_TEMP, 0777, true);
@mkdir(PATH_LOG, 0777, true);

