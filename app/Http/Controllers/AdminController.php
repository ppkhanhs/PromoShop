<?php

namespace App\Http\Controllers;

use App\Services\CassandraDataService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected ?Authenticatable $adminAccount = null;

    public function __construct(protected CassandraDataService $dataService)
    {
        $this->adminAccount = Auth::user();
        view()->share('adminAccount', $this->adminAccount);
    }

    public function dashboard(): View
    {
        $stats = $this->dataService->fetchDashboardStats();

        return $this->renderAdminView('admin.dashboard', [
            'stats' => $stats,
        ]);
    }

    protected function renderAdminView(string $view, array $data = []): View
    {
        return view($view, array_merge([
            'adminAccount' => $this->adminAccount,
        ], $data));
    }
}
