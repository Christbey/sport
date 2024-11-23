<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CollegeFootballHypotheticalCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($hypothetical) {
                return new CollegeFootballHypotheticalResource($hypothetical);
            }),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'weeks' => $this->additional['meta']['weeks'] ?? [],
                'current_week' => $this->additional['meta']['current_week'] ?? null,
            ],
            'links' => [
                'self' => url()->current(),
            ],
        ];
    }
}