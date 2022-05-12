<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

final class SemaphoreUtils
{
    public static function acquire(int $key, int $max_acquire = 1, int $permissions = 0666, bool $auto_release = true)
    {
        $semaphore = sem_get($key, $max_acquire, $permissions, -1);

        if (!sem_acquire($semaphore)) {
            throw new \Exception('Semaphore cannot be acquired');
        }

        return $semaphore;
    }

    public static function release($semaphore)
    {
        sem_release($semaphore);
    }
}
