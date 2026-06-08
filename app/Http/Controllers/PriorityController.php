<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $sites = \App\Models\Site::where('is_active', true)->get();

        $query = \App\Models\Page::with(['site', 'latestGscMetric', 'latestCwvMetric', 'channableData', 'categoryOwner'])
            ->when(!$user->hasRole('admin'), fn($q) => $q->where('category_owner_id', $user->id))
            ->when($request->site, fn($q) => $q->where('site_id', $request->site))
            ->when($request->owner, fn($q) => $q->where('category_owner_id', $request->owner))
            ->get()
            ->map(function ($page) {
                $score = $page->priority_score;
                $page->priority_label = $score >= 70 ? 'Hoog' : ($score >= 40 ? 'Middel' : 'Laag');
                return $page;
            })
            ->sortByDesc('priority_score');

        $owners = \App\Models\User::role('specialist')->get();

        return view('priorities.index', compact('query', 'sites', 'owners'));
    }
}
