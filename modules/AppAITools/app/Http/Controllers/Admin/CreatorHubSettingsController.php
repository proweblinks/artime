<?php

namespace Modules\AppAITools\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CreatorHubSettingsController extends Controller
{
    public function index()
    {
        $youtubeKeys = json_decode(get_option('creator_hub_youtube_api_keys', '[]'), true) ?: [];
        $rotationMode = get_option('creator_hub_youtube_rotation_mode', 'round-robin');
        $defaultPlatform = get_option('creator_hub_default_platform', 'youtube');

        return view('appaitools::admin.settings', compact('youtubeKeys', 'rotationMode', 'defaultPlatform'));
    }

    public function saveYouTubeKeys(Request $request)
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*.key' => 'required|string|min:10',
            'keys.*.label' => 'required|string|max:50',
            'keys.*.active' => 'boolean',
        ]);

        $keys = collect($request->input('keys'))->map(function ($item) {
            return [
                'key' => trim($item['key']),
                'label' => trim($item['label']),
                'active' => (bool) ($item['active'] ?? true),
            ];
        })->values()->toArray();

        update_option('creator_hub_youtube_api_keys', json_encode($keys));
        update_option('creator_hub_youtube_api_key_index', 0);

        return redirect()->route('admin.creator-hub.settings')
            ->with('success', 'YouTube API keys saved successfully.');
    }

    public function testYouTubeKey(Request $request)
    {
        $request->validate([
            'key' => 'required|string|min:10',
        ]);

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet',
                'id' => 'dQw4w9WgXcQ',
                'key' => $request->input('key'),
            ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'API key is valid and working.']);
            }

            $error = $response->json('error.message', 'Unknown error');
            return response()->json(['success' => false, 'message' => "API key test failed: {$error}"], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => "Connection error: {$e->getMessage()}"], 500);
        }
    }

    public function saveGeneral(Request $request)
    {
        $request->validate([
            'rotation_mode' => 'required|in:round-robin,random',
            'default_platform' => 'required|in:youtube,tiktok,instagram,linkedin,general',
        ]);

        update_option('creator_hub_youtube_rotation_mode', $request->input('rotation_mode'));
        update_option('creator_hub_default_platform', $request->input('default_platform'));

        return redirect()->route('admin.creator-hub.settings')
            ->with('success', 'General settings saved successfully.');
    }
}
