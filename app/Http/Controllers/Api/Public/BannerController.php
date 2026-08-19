<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $bannerService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->filled('position')) {
            return response()->json($this->bannerService->getActiveByPosition($request->string('position')));
        }

        return response()->json($this->bannerService->getAllActive());
    }
}
