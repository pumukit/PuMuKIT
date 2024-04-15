<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

final class UploadEvents
{
    public const UPLOAD_FROM_INBOX = 'upload.inbox';
    public const UPLOAD_FROM_SERVER = 'upload.server';
}
