<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $sites = \App\Models\Site::where('is_active', true)->get();
        $query = \App\Models\Page::with(['site', 'latestCwvMetric', 'channableData'])
            ->when($request->site, fn($q) => $q->where('site_id', $request->site))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where(function($q2) use ($request) {
                $q2->where('url', 'like', '%'.$request->search.'%')
                   ->orWhere('title', 'like', '%'.$request->search.'%');
            }))
            ->orderBy('url')
            ->paginate(50)
            ->withQueryString();

        return view('pages.index', compact('sites', 'query'));
    }

    public function show(\App\Models\Page $page)
    {
        $page->load(['site', 'latestGscMetric', 'latestCwvMetric', 'channableData', 'categoryOwner']);
        return view('pages.show', compact('page'));
    }
}
