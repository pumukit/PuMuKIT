<?php

namespace Pumukit\EncoderBundle\Executor;

use Symfony\Component\Process\Process;

class RemoteHTTPExecutor
{

  public function execute($command, array $cpu)
  {
        //TODO TEST
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Curl is required to execute remote commands.');
        }

        if (false === $curl = curl_init()) {
            throw new \RuntimeException('Unable to create a new curl handle.');
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'http://'.$cpu['host'].'/webserver.php');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic ".base64_encode($cpu['user'].':'.$cpu['password'])));
        curl_setopt($curl, CURLOPT_POST, 1);
        // TODO - nombre 'ruta'
        curl_setopt($curl, CURLOPT_POSTFIELDS, "command=$command");

        $response = curl_exec($curl);

        if (false === $response) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new \RuntimeException(sprintf('An error occurred: %s.', $error));
        }


        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (200 != $statusCode) {
            curl_close($curl);

            throw new \RuntimeException(sprintf('The web service failed for an unknown reason (HTTP %s).', $statusCode));
        }

        curl_close($curl);

        return $response;
  }
}