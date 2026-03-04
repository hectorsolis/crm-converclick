<?php
// FILE: app/Controllers/LeadController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Lead;
use App\Models\User;
use App\Models\LeadActivity;
use App\Helpers\DateHelper;

class LeadController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireAuth();
        $lead = new Lead();
        $userM = new User();
        $search = $request->get('search', '');
        $source = $request->get('source', '');
        $filters = [];
        if ($search)
            $filters['search'] = $search;
        if ($source)
            $filters['source'] = $source;

        // Vendedor solo ve sus propios leads
        if (Auth::isVendedor()) {
            $filters['assigned_to'] = Auth::id();
        }

        $leads = $lead->all($filters);
        $vendedores = $userM->vendedores();

        $this->view('leads/index', [
            'leads' => $leads,
            'vendedores' => $vendedores,
            'search' => $search,
            'source' => $source,
            'user' => Auth::user(),
            'activeMenu' => 'leads',
        ]);
    }

    public function export(Request $request): void
    {
        Auth::requireAuth();
        $lead = new Lead();
        $search = $request->get('search', '');
        $source = $request->get('source', '');
        $filters = [];
        if ($search)
            $filters['search'] = $search;
        if ($source)
            $filters['source'] = $source;

        // Vendedor solo ve sus propios leads
        if (Auth::isVendedor()) {
            $filters['assigned_to'] = Auth::id();
        }

        $leads = $lead->all($filters);

        // Headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=leads_' . date('Y-m-d_His') . '.csv');

        $output = fopen('php://output', 'w');

        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, [
            'ID',
            'Nombre',
            'Email',
            'Teléfono',
            'Empresa',
            'Industria',
            'Tamaño',
            'Fuente',
            'Vendedor',
            'Presupuesto',
            'Plazo',
            'Problema Activo',
            'Tomador Decisión',
            'Próximo Paso',
            'Fecha Próx. Paso',
            'Fecha Creación'
        ]);

        foreach ($leads as $row) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['company_name'],
                $row['company_industry'],
                $row['company_size'],
                $row['source'],
                $row['vendedor_name'] ?? 'Sin asignar',
                $row['has_budget'] ? 'Sí' : 'No',
                $row['has_deadline'] ? 'Sí' : 'No',
                $row['has_active_problem'] ? 'Sí' : 'No',
                $row['decision_maker'] ? 'Sí' : 'No',
                $row['next_step'],
                $row['next_step_date'],
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    public function show(Request $request): void
    {
        Auth::requireAuth();
        $id = (int)$request->param('id');
        $lead = (new Lead())->findById($id);
        if (!$lead) {
            $this->redirect('/leads');
        }

        // Vendedor solo puede ver sus leads
        if (Auth::isVendedor() && $lead['assigned_to'] != Auth::id()) {
            $this->redirect('/leads');
        }

        $activities = (new LeadActivity())->forLead($id);
        $vendedores = (new User())->vendedores();

        $this->view('leads/show', [
            'lead' => $lead,
            'activities' => $activities,
            'vendedores' => $vendedores,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'leads',
            'DateHelper' => new DateHelper(),
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireAuth();
        $vendedores = (new User())->vendedores();
        $this->view('leads/create', [
            'vendedores' => $vendedores,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'leads',
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireAuth();
        Csrf::verifyPost();

        $data = [
            'name' => $request->post('name'),
            'email' => $request->post('email') ?: null,
            'phone' => $request->post('phone') ?: null,
            'company_name' => $request->post('company_name') ?: null,
            'company_industry' => $request->post('company_industry') ?: null,
            'company_size' => $request->post('company_size') ?: null,
            'source' => 'manual',
            'assigned_to' => $request->post('assigned_to') ?: null,
            'has_budget' => (int)$request->post('has_budget', 0),
            'has_deadline' => (int)$request->post('has_deadline', 0),
            'has_active_problem' => (int)$request->post('has_active_problem', 0),
            'decision_maker' => (int)$request->post('decision_maker', 0),
            'context_notes' => $request->post('context_notes') ?: null,
            'next_step' => $request->post('next_step') ?: null,
            'next_step_date' => $request->post('next_step_date') ?: null,
        ];

        if (empty($data['name'])) {
            $this->withFlash('danger', 'El nombre es obligatorio.', '/leads/create');
        }
        if (empty($data['email']) && empty($data['phone'])) {
            $this->withFlash('danger', 'Debes ingresar al menos email o teléfono.', '/leads/create');
        }

        $leadModel = new Lead();
        $id = $leadModel->create($data);
        (new LeadActivity())->log($id, 'created', 'Lead creado manualmente', Auth::id());

        $this->withFlash('success', 'Lead creado correctamente.', "/leads/{$id}");
    }

    public function edit(Request $request): void
    {
        Auth::requireAuth();
        $id = (int)$request->param('id');
        $lead = (new Lead())->findById($id);
        if (!$lead) {
            $this->redirect('/leads');
        }

        $vendedores = (new User())->vendedores();
        $this->view('leads/edit', [
            'lead' => $lead,
            'vendedores' => $vendedores,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'leads',
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireAuth();
        Csrf::verifyPost();

        $id = (int)$request->param('id');
        $leadModel = new Lead();
        $existing = $leadModel->findById($id);
        if (!$existing) {
            $this->redirect('/leads');
        }

        $data = [
            'name' => $request->post('name'),
            'email' => $request->post('email') ?: null,
            'phone' => $request->post('phone') ?: null,
            'company_name' => $request->post('company_name') ?: null,
            'company_industry' => $request->post('company_industry') ?: null,
            'company_size' => $request->post('company_size') ?: null,
            'assigned_to' => $request->post('assigned_to') ?: null,
            'has_budget' => (int)$request->post('has_budget', 0),
            'has_deadline' => (int)$request->post('has_deadline', 0),
            'has_active_problem' => (int)$request->post('has_active_problem', 0),
            'decision_maker' => (int)$request->post('decision_maker', 0),
            'context_notes' => $request->post('context_notes') ?: null,
            'next_step' => $request->post('next_step') ?: null,
            'next_step_date' => $request->post('next_step_date') ?: null,
        ];

        $leadModel->update($id, $data);
        (new LeadActivity())->log($id, 'updated', 'Datos del lead actualizados', Auth::id());

        $this->withFlash('success', 'Lead actualizado.', "/leads/{$id}");
    }

    public function delete(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $id = (int)$request->param('id');
        (new Lead())->delete($id);
        $this->withFlash('success', 'Lead eliminado.', '/leads');
    }
}