<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DiscogsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => [
                'discogs_username' => Setting::get('discogs_username', ''),
                'collection_last_synced' => Setting::get('collection_last_synced', null),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'discogs_username' => 'required|string|max:255|alpha_dash',
        ]);

        Setting::set('discogs_username', $request->discogs_username);

        return redirect()->back()->with('success', 'Settings saved.');
    }

    public function sync(Request $request, DiscogsService $discogs): RedirectResponse
    {
        $username = Setting::get('discogs_username');

        if (!$username) {
            return redirect()->back()->with('error', 'Please save a Discogs username first.');
        }

        try {
            $result = $discogs->syncCollection($username);
            return redirect()->back()->with('success', "Synced {$result['synced']} records from Discogs.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
