<?php
// FILE: app/Controllers/DashboardController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Models\Lead;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireAuth();

        $lead = new Lead();

        $totalLeads = $lead->countTotal();
        $todayLeads = $lead->countToday();
        $overdueSteps = $lead->countOverdue();
        $bySource = $lead->countBySource();
        $recentLeads = $lead->recentLeads(8);

        // indexar por fuente
        $sourceMap = [];
        foreach ($bySource as $row) {
            $sourceMap[$row['source']] = $row['total'];
        }

        $this->view('dashboard/index', [
            'totalLeads' => $totalLeads,
            'todayLeads' => $todayLeads,
            'overdueSteps' => $overdueSteps,
            'sourceMap' => $sourceMap,
            'recentLeads' => $recentLeads,
            'user' => Auth::user(),
            'activeMenu' => 'dashboard',
        ]);
    }
}