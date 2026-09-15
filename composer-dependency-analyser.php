<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration;

return $config
    ->addPathRegexToExclude('~Test(Cases)?\.php$~');
