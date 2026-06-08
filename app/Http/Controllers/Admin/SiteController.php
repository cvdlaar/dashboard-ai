<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $sites = \App\Models\Site::withCount('pages')->get();
        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('admin.sites.form', ['site' => new \App\Models\Site()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'domain' => 'required|string|max:255|unique:sites',
            'sitemap_url' => 'nullable|url',
            'channable_feed_url' => 'nullable|url',
            'languages' => 'array',
        ]);
        $data['languages'] = $request->input('languages', ['nl']);
        \App\Models\Site::create($data);
        return redirect()->route('admin.sites.index')->with('success', 'Website toegevoegd.');
    }

    public function edit(\App\Models\Site $site)
    {
        return view('admin.sites.form', compact('site'));
    }

    public function update(Request $request, \App\Models\Site $site)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'domain' => 'required|string|max:255|unique:sites,domain,'.$site->id,
            'sitemap_url' => 'nullable|url',
            'channable_feed_url' => 'nullable|url',
            'languages' => 'array',
        ]);
        $data['languages'] = $request->input('languages', ['nl']);
        $site->update($data);
        return redirect()->route('admin.sites.index')->with('success', 'Website bijgewerkt.');
    }

    public function destroy(\App\Models\Site $site)
    {
        $site->delete();
        return redirect()->route('admin.sites.index')->with('success', 'Website verwijderd.');
    }
}
