<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function index()
    {
        $sites = \App\Models\Site::where('is_active', true)->get();

        // Laatste sync per site per type
        $lastSyncs = \App\Models\SyncLog::whereIn('site_id', $sites->pluck('id'))
            ->whereIn('status', ['completed', 'failed'])
            ->orderByDesc('finished_at')
            ->get()
            ->groupBy('site_id')
            ->map(fn($logs) => $logs->groupBy('type')->map->first());

        $types = \App\Models\SyncLog::$typeLabels;

        return view('admin.syncs.index', compact('sites', 'lastSyncs', 'types'));
    }

    public function run(Request $request, \App\Models\Site $site, string $type)
    {
        $allowed = array_keys(\App\Models\SyncLog::$typeLabels);
        abort_unless(in_array($type, $allowed), 404);

        // Artisan command per type
        $commands = [
            'sitemap'   => 'import:sitemap',
            'channable' => 'import:channable',
            'pagespeed' => 'scan:cwv',
            'gsc'       => 'sync:gsc',
            'ga4'       => 'sync:ga4',
            'google_ads'=> 'sync:google-ads',
            'bing_ads'  => 'sync:bing-ads',
        ];

        $command = $commands[$type] ?? null;

        if (!$command) {
            return back()->with('error', 'Onbekend sync type.');
        }

        // Check of command al bestaat
        $registered = collect(\Artisan::all())->keys();
        if (!$registered->contains($command)) {
            return back()->with('error', "'{$command}' is nog niet beschikbaar — kom binnenkort.");
        }

        $log = \App\Models\SyncLog::create([
            'site_id'    => $site->id,
            'type'       => $type,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            \Artisan::call($command, ['--site' => $site->domain]);
            $output = \Artisan::output();

            // Haal records_new en records_updated uit de output
            preg_match('/(\d+) nieuw/', $output, $newMatch);
            preg_match('/(\d+) bijgewerkt/', $output, $updatedMatch);

            $log->update([
                'status'          => 'completed',
                'records_new'     => (int) ($newMatch[1] ?? 0),
                'records_updated' => (int) ($updatedMatch[1] ?? 0),
                'message'         => trim($output),
                'finished_at'     => now(),
            ]);

            return back()->with('success', \App\Models\SyncLog::$typeLabels[$type] . ' succesvol uitgevoerd voor ' . $site->name . '.');
        } catch (\Exception $e) {
            $log->update([
                'status'      => 'failed',
                'message'     => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return back()->with('error', 'Sync mislukt: ' . $e->getMessage());
        }
    }
}
