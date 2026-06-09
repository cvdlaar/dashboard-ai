<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $sites = \App\Models\Site::where('is_active', true)->get();

        $siteId = $request->site
            ?? session('current_site_id')
            ?? $sites->first()?->id;

        $query = \App\Models\Page::with([
                'site', 'latestCwvMetric', 'latestGscMetric', 'latestGeoMetric', 'channableData',
            ])
            ->when($siteId,         fn($q) => $q->where('site_id', $siteId))
            ->when($request->type,  fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('url',   'like', '%'.$request->search.'%')
                   ->orWhere('title', 'like', '%'.$request->search.'%');
            }))
            ->orderBy('url')
            ->paginate(50)
            ->withQueryString();

        return view('pages.index', compact('sites', 'query', 'siteId'));
    }

    public function show(\App\Models\Page $page)
    {
        $page->load([
            'site', 'latestGscMetric', 'latestCwvMetric',
            'latestGeoMetric', 'channableData', 'categoryOwner',
        ]);

        return view('pages.show', compact('page'));
    }
}
