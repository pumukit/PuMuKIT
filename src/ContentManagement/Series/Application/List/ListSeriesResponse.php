<?php

namespace App\ContentManagement\Series\Application\List;

final class ListSeriesResponse
{
    public function __construct(public array $series, public int $total) {}
}
