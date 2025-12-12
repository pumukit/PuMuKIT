<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\View;

final class ViewSeriesRequest
{
    public function __construct(public string $id, public string $tab)
    {
        ViewSeriesValidator::validate($this);
    }
}
