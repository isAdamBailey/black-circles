<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CollectionIndexResource extends ResourceCollection
{
    public $collects = ReleaseSummaryResource::class;

    public function __construct($resource, private readonly array $meta = [])
    {
        parent::__construct($resource);
    }

    public function with(Request $request): array
    {
        return $this->meta;
    }
}
