<?php

namespace Pumukit\EncoderBundle\Executor;

use Symfony\Component\Process\Process;

class RemoteHTTPExecutor
{

  public function execute($command, array $cpu)
  {
        //TODO TEST
        $ch = curl_init('http://'.$cpu['host'].'/webserver.php'); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic ".base64_encode($cpu['user'].':'.$cpu['password'])));
        curl_setopt($ch, CURLOPT_POST, 1);
        // TODO - nombre 'ruta'
        curl_setopt($ch, CURLOPT_POSTFIELDS, "ruta=$command");

        $var = curl_exec($ch); 
        $error = curl_error($ch);
        
        return $var;
  }
}