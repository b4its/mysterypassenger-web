<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\TransportModeResource;
use App\Models\TransportMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class TransportModeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TransportMode::query()->where('is_active', true)->orderBy('sort_order');

        if ($request->filled('updated_since')) {
            $since = Carbon::parse($request->input('updated_since'));
            $query->where('updated_at', '>=', $since);
        }

        return TransportModeResource::collection($query->get());
    }
}
