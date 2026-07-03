<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtmController extends Controller
{
    public function locateNearby(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'numeric|min:1|max:100',
            'city' => 'nullable|string|max:100',
            'limit' => 'integer|min:1|max:50',
        ]);

        $radius = $data['radius'] ?? 5; // Default 5 km
        $limit = $data['limit'] ?? 10;

        $query = Atm::where('status', 'active');

        // Filter by city if provided
        if ($request->filled('city')) {
            $query->where('city', $data['city']);
        }

        // If coordinates provided, calculate distance (simplified)
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = $data['latitude'];
            $lng = $data['longitude'];

            // Haversine formula for distance (simplified, exact formula would need DB functions)
            $atms = $query->get()
                ->map(function ($atm) use ($lat, $lng) {
                    $atm->distance = $this->calculateDistance(
                        $lat, $lng,
                        $atm->latitude, $atm->longitude
                    );
                    return $atm;
                })
                ->where('distance', '<=', $radius)
                ->sortBy('distance')
                ->take($limit);
        } else {
            $atms = $query->take($limit)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $atms->values(),
            'count' => $atms->count(),
        ]);
    }

    public function listByCity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city' => 'required|string|max:100',
        ]);

        $atms = Atm::where('status', 'active')
            ->where('city', $data['city'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $atms,
            'count' => $atms->count(),
        ]);
    }

    public function listByBranch(Request $request, $branchId): JsonResponse
    {
        $atms = Atm::where('status', 'active')
            ->where('branch_id', $branchId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $atms,
            'count' => $atms->count(),
        ]);
    }

    public function detail($atmId): JsonResponse
    {
        $atm = Atm::where('atm_id', $atmId)
            ->with('branch')
            ->first();

        if (!$atm) {
            return response()->json(['success' => false, 'message' => 'ATM not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $atm,
        ]);
    }

    public function searchByPostalCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'postal_code' => 'required|string|max:20',
        ]);

        $atms = Atm::where('status', 'active')
            ->where('postal_code', $data['postal_code'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $atms,
            'count' => $atms->count(),
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }
}
