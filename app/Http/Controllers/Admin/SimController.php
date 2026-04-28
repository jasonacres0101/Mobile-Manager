<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sim;
use Illuminate\Http\Request;

class SimController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);
        $sortable = ['mobile_number', 'iccid', 'network', 'tariff', 'monthly_cost', 'status', 'last_synced_at', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        return view('admin.sims.index', [
            'sims' => Sim::query()
                ->with(['company', 'agreement'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('mobile_number', 'like', "%{$search}%")
                            ->orWhere('msisdn', 'like', "%{$search}%")
                            ->orWhere('iccid', 'like', "%{$search}%")
                            ->orWhere('sim_number', 'like', "%{$search}%")
                            ->orWhere('network', 'like', "%{$search}%")
                            ->orWhere('tariff', 'like', "%{$search}%")
                            ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($request->input('match') === 'matched', fn ($query) => $query->whereNotNull('connectwise_addition_id')->whereNotNull('mobilemanager_sim_id'))
                ->when($request->input('match') === 'psa_only', fn ($query) => $query->whereNotNull('connectwise_addition_id')->whereNull('mobilemanager_sim_id'))
                ->when($request->input('match') === 'jola_only', fn ($query) => $query->whereNull('connectwise_addition_id')->whereNotNull('mobilemanager_sim_id'))
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->withQueryString(),
            'filters' => $request->only(['q', 'match', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function jola(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 25), 100);

        return view('admin.sims.jola', [
            'sims' => Sim::query()
                ->with('company')
                ->whereNotNull('mobilemanager_sim_id')
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('mobile_number', 'like', "%{$search}%")
                            ->orWhere('iccid', 'like', "%{$search}%")
                            ->orWhere('network', 'like', "%{$search}%")
                            ->orWhere('tariff', 'like', "%{$search}%");
                    });
                })
                ->latest('last_synced_at')
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }
}
