<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

class DeliveryProbe
{
    public function __construct(
        private readonly ?string $connectTo = null,
        private readonly bool $insecure = false,
        private readonly int $timeout = 10,
    ) {}

    /**
     * @param list<string> $headers
     */
    public function fetch(string $url, array $headers = []): DeliveryResponse
    {
        $responseHeaders = [];
        $handle = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PATH_AS_IS => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (2 === count($parts)) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ];

        if ($this->insecure) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        if (null !== $this->connectTo && '' !== $this->connectTo) {
            $target = str_contains($this->connectTo, ':') ? $this->connectTo : $this->connectTo.':';
            $options[CURLOPT_CONNECT_TO] = ['::'.$target];
        }

        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);

        return new DeliveryResponse(
            (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            $responseHeaders,
            is_string($body) ? $body : '',
            curl_error($handle),
        );
    }
}
