<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Page;
use App\Models\PageMetricGsc;
use App\Models\PageMetricCwv;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $sites = Site::withCount('pages')->where('is_active', true)->get();

        $totalPages = Page::count();
        $productPages = Page::where('type', 'product')->count();
        $categoryPages = Page::where('type', 'category')->count();

        $avgPosition = PageMetricGsc::whereDate('date', '>=', now()->subDays(28))
            ->whereNull('query')
            ->avg('position');

        $avgCwv = PageMetricCwv::where('strategy', 'mobile')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->avg('performance_score');

        $openPriorities = Page::whereNotNull('category_owner_id')->count();

        $topOpportunities = Page::with(['latestGscMetric', 'channableData'])
            ->get()
            ->sortByDesc(fn($p) => $p->priority_score)
            ->take(5)
            ->map(function ($page) {
                $score = $page->priority_score;
                $page->priority_label = $score >= 70 ? 'Hoog' : ($score >= 40 ? 'Middel' : 'Laag');
                return $page;
            });

        $cwvAlerts = PageMetricCwv::with('page')
            ->where('strategy', 'mobile')
            ->where(function ($q) {
                $q->where('lcp_rating', 'poor')
                  ->orWhere('cls_rating', 'poor')
                  ->orWhere('inp_rating', 'poor');
            })
            ->orderBy('performance_score')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'sites', 'totalPages', 'productPages', 'categoryPages',
            'avgPosition', 'avgCwv', 'openPriorities',
            'topOpportunities', 'cwvAlerts'
        ));
    }
}
