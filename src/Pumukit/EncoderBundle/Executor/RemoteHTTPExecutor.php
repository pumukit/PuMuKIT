<?php

namespace Pumukit\EncoderBundle\Executor;

class RemoteHTTPExecutor
{
    public function execute($command, array $cpu)
    {
        if (!function_exists('curl_init')) {
            throw new ExecutorException('Curl is required to execute remote commands.');
        }

        if (false === $curl = curl_init()) {
            throw new ExecutorException('Unable to create a new curl handle.');
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'http://'.$cpu['host'].'/webserver.php');
        if (isset($cpu['user'], $cpu['password'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic '.base64_encode($cpu['user'].':'.$cpu['password'])]);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['command' => $command]));

        $response = curl_exec($curl);

        if (false === $response) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new ExecutorException(sprintf('An error occurred: %s.', $error));
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (200 != $statusCode) {
            throw new ExecutorException(sprintf('The web service failed for an unknown reason (HTTP %s).', $statusCode));
        }

        return $response;
    }
}
