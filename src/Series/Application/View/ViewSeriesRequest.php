<?php

namespace App\Series\Application\View;

final readonly class ViewSeriesRequest
{
    public function __construct(public string $id, public string $tab) {}
}
