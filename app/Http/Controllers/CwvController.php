<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CwvController extends Controller
{
    public function index(Request $request)
    {
        $sites = \App\Models\Site::where('is_active', true)->get();
        $strategy = $request->get('strategy', 'mobile');

        $metrics = \App\Models\PageMetricCwv::with('page.site')
            ->where('strategy', $strategy)
            ->when($request->site, fn($q) => $q->whereHas('page', fn($q2) => $q2->where('site_id', $request->site)))
            ->when($request->filter === 'poor', fn($q) => $q->where(function($q2) {
                $q2->where('lcp_rating', 'poor')->orWhere('cls_rating', 'poor')->orWhere('inp_rating', 'poor');
            }))
            ->orderBy('performance_score')
            ->paginate(50)
            ->withQueryString();

        $avgScores = \App\Models\PageMetricCwv::where('strategy', $strategy)
            ->selectRaw('AVG(performance_score) as perf, AVG(lcp) as lcp, AVG(cls) as cls, AVG(inp) as inp')
            ->first();

        return view('cwv.index', compact('sites', 'metrics', 'strategy', 'avgScores'));
    }
}
