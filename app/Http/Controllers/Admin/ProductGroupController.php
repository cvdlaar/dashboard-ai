<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ProductGroup;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::where('is_active', true)->get();

        $currentSiteId = $request->query('site_id')
            ?? session('current_site_id')
            ?? $sites->first()?->id;
        $currentSite = $sites->firstWhere('id', $currentSiteId) ?? $sites->first();

        if ($request->query('site_id')) {
            session(['current_site_id' => $currentSite->id]);
        }

        $groups = ProductGroup::where('site_id', $currentSite->id)
            ->withCount('pages')
            ->with('users')
            ->orderBy('name')
            ->get();

        $specialists = User::role('specialist')->get();

        return view('admin.product-groups.index', compact('sites', 'currentSite', 'groups', 'specialists'));
    }

    public function create()
    {
        $sites = Site::where('is_active', true)->get();
        $currentSiteId = session('current_site_id') ?? $sites->first()?->id;
        $currentSite = $sites->firstWhere('id', $currentSiteId) ?? $sites->first();
        $specialists = User::role('specialist')->get();

        return view('admin.product-groups.create', compact('sites', 'currentSite', 'specialists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name'    => 'required|string|max:100',
        ]);

        $group = ProductGroup::create([
            'site_id'     => $request->site_id,
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        if ($request->filled('user_ids')) {
            $group->users()->sync($request->user_ids);
        }

        return redirect()->route('admin.product-groups.index')
            ->with('success', 'Productgroep "' . $group->name . '" aangemaakt.');
    }

    public function edit(ProductGroup $productGroup)
    {
        $productGroup->load('users', 'pages');
        $sites      = Site::where('is_active', true)->get();
        $specialists = User::role('specialist')->get();

        // Pagina's zonder productgroep voor deze site (om te koppelen)
        $unassignedPages = Page::where('site_id', $productGroup->site_id)
            ->whereNull('product_group_id')
            ->orderBy('url')
            ->limit(500)
            ->get(['id', 'url', 'path', 'type']);

        return view('admin.product-groups.edit', compact('productGroup', 'sites', 'specialists', 'unassignedPages'));
    }

    public function update(Request $request, ProductGroup $productGroup)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $productGroup->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        $productGroup->users()->sync($request->input('user_ids', []));

        return redirect()->route('admin.product-groups.index')
            ->with('success', 'Productgroep bijgewerkt.');
    }

    public function destroy(ProductGroup $productGroup)
    {
        // Ontkoppel pagina's (zet product_group_id op null)
        Page::where('product_group_id', $productGroup->id)->update(['product_group_id' => null]);

        $productGroup->delete();

        return redirect()->route('admin.product-groups.index')
            ->with('success', 'Productgroep verwijderd.');
    }

    public function assignUsers(Request $request, ProductGroup $productGroup)
    {
        $productGroup->users()->sync($request->input('user_ids', []));

        return back()->with('success', 'Specialisten bijgewerkt.');
    }

    public function assignPages(Request $request, ProductGroup $productGroup)
    {
        $pageIds = $request->input('page_ids', []);

        // Koppel geselecteerde pagina's aan deze groep
        Page::whereIn('id', $pageIds)->update(['product_group_id' => $productGroup->id]);

        // Ontkoppel pagina's die gedeselecteerd zijn (al gekoppeld aan deze groep maar nu niet meer in selectie)
        Page::where('product_group_id', $productGroup->id)
            ->whereNotIn('id', $pageIds)
            ->update(['product_group_id' => null]);

        return back()->with('success', count($pageIds) . ' pagina\'s gekoppeld aan "' . $productGroup->name . '".');
    }
}
