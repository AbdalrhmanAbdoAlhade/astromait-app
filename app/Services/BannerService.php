<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

class BannerService
{
    public function getActiveByPosition(string $position): Collection
    {
        return Banner::active()
            ->where('position', $position)
            ->orderBy('order')
            ->get();
    }

    public function getAllActive(): Collection
    {
        return Banner::active()->orderBy('order')->get();
    }
}
