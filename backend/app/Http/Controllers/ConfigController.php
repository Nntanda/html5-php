<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Get system configuration
     * GET /api/config
     */
    public function index(): JsonResponse
    {
        $config = SystemConfig::getAllConfig();

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Update system configuration
     * PUT /api/config
     */
    public function update(Request $request): JsonResponse
    {
        // Check if user is Super Admin
        if (!$request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can update configuration.',
            ], 403);
        }

        $validated = $request->validate([
            'configurations' => 'required|array',
            'configurations.*.key' => 'required|string',
            'configurations.*.value' => 'required',
            'configurations.*.type' => 'required|in:string,number,boolean,json',
            'configurations.*.description' => 'nullable|string',
        ]);

        $updated = [];

        foreach ($validated['configurations'] as $config) {
            $systemConfig = SystemConfig::setValue(
                $config['key'],
                $config['value'],
                $config['type'],
                $config['description'] ?? null
            );

            $updated[] = $systemConfig;
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'data' => $updated,
        ]);
    }

    /**
     * Get specific configuration value
     * GET /api/config/{key}
     */
    public function show(string $key): JsonResponse
    {
        $config = SystemConfig::where('key', $key)->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $config->key,
                'value' => SystemConfig::getValue($key),
                'type' => $config->type,
                'description' => $config->description,
            ],
        ]);
    }

    /**
     * Update specific configuration value
     * PUT /api/config/{key}
     */
    public function updateOne(Request $request, string $key): JsonResponse
    {
        // Check if user is Super Admin
        if (!$request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can update configuration.',
            ], 403);
        }

        $validated = $request->validate([
            'value' => 'required',
            'type' => 'required|in:string,number,boolean,json',
            'description' => 'nullable|string',
        ]);

        $config = SystemConfig::setValue(
            $key,
            $validated['value'],
            $validated['type'],
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'data' => $config,
        ]);
    }
}
