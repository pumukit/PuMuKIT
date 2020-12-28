<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Symfony\Component\Serializer\SerializerInterface;

class SerializerService
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function dataSerialize(array $data, string $format = 'html'): string
    {
        return $this->serializer->serialize($data, $format);
    }
}
