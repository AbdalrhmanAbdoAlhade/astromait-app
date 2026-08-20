<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->vendorProfile->settlements()->with('items')->latest()->paginate($request->integer('per_page', 15)));
    }
}
