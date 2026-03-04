<?php
// FILE: app/Controllers/PipelineController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Models\Lead;
use App\Models\User;

class PipelineController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireAuth();

        $filters = [];

        // VENDEDOR solo ve sus propios leads en pipeline
        if (Auth::isVendedor()) {
            $filters['assigned_to'] = Auth::id();
        }
        elseif ($request->get('assigned_to')) {
            $filters['assigned_to'] = (int)$request->get('assigned_to');
        }

        if ($request->get('source'))
            $filters['source'] = $request->get('source');
        if ($request->get('date_from'))
            $filters['date_from'] = $request->get('date_from');
        if ($request->get('date_to'))
            $filters['date_to'] = $request->get('date_to');
        if ($request->get('next_step'))
            $filters['next_step_filter'] = $request->get('next_step');
        if ($request->get('qualification'))
            $filters['qualification'] = $request->get('qualification');

        $lead = new Lead();
        $leads = $lead->all($filters);
        $vendedores = (new User())->vendedores();

        $this->view('pipeline/index', [
            'leads' => $leads,
            'vendedores' => $vendedores,
            'filters' => $request->all(),
            'user' => Auth::user(),
            'activeMenu' => 'pipeline',
        ]);
    }
}