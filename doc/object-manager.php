<?php

$loader = require __DIR__.'/../app/autoload.php';

$kernel = new AppKernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
