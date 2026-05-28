<?php

use App\Kernel;

require dirname(__DIR__).'/config/bootstrap.php';
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine_mongodb.odm.document_manager');
