<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function index(Request $request)
    {
        $sites = \App\Models\Site::where('is_active', true)->get();

        $pages = \App\Models\Page::with('site')
            ->when($request->site, fn($q) => $q->where('site_id', $request->site))
            ->when($request->filter === 'no_jsonld', fn($q) => $q->where('has_json_ld', false))
            ->when($request->filter === 'no_meta', fn($q) => $q->whereNull('meta_description'))
            ->orderBy('type')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'total' => \App\Models\Page::count(),
            'with_json_ld' => \App\Models\Page::where('has_json_ld', true)->count(),
            'with_meta' => \App\Models\Page::whereNotNull('meta_description')->count(),
            'with_h1' => \App\Models\Page::whereNotNull('h1')->count(),
        ];

        return view('geo.index', compact('sites', 'pages', 'stats'));
    }
}
