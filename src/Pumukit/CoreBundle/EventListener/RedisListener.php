<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Pumukit\CoreBundle\Event\FileRemovedEvent;
use Pumukit\CoreBundle\Utils\RedisUtils;

class RedisListener
{
    private const INBOX_UPLOAD_REDIS_PREFIX = 'tus:server:*';

    public function findAndCleanKeyOfFile(FileRemovedEvent $event): void
    {
        $filePath = $event->getFilePath();

        foreach ($this->redisElementsByPattern() as $element) {
            $elementData = RedisUtils::valuesFromKey($element);
            if ($this->isSamePath($elementData, 'file_path', $filePath)) {
                RedisUtils::removeKey($element);
            }
        }
    }

    private function redisElementsByPattern(): ?array
    {
        return RedisUtils::elementsByKeyPattern(self::INBOX_UPLOAD_REDIS_PREFIX);
    }

    private function isSamePath(array $element, string $key, string $value): bool
    {
        $elementPath = str_replace('\\', '', $element[$key]);

        return $elementPath === $value;
    }
}
