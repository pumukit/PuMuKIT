<?php

namespace App\Series\Application\Find;

final class FindSeriesRequest
{
    public function __construct(public readonly string $id) {}
}
