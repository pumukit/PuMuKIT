<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\BulkDelete;

final class BulkDeleteGroupValidator
{
    public static function validate(BulkDeleteGroupRequest $request): void
    {
        if (empty($request->ids)) {
            throw new \InvalidArgumentException('group.bulk_delete.error.no_ids');
        }
        if (!is_array($request->ids)) {
            throw new \InvalidArgumentException('IDs must be an array');
        }
    }
}
