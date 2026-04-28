<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FibreConnection;
use Illuminate\Http\Request;

class FibreConnectionController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);
        $sortable = ['service_identifier', 'circuit_reference', 'access_type', 'bandwidth', 'monthly_cost', 'status', 'last_synced_at', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        return view('admin.fibre-connections.index', [
            'connections' => FibreConnection::query()
                ->with(['company', 'agreement'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('service_identifier', 'like', "%{$search}%")
                            ->orWhere('circuit_reference', 'like', "%{$search}%")
                            ->orWhere('access_type', 'like', "%{$search}%")
                            ->orWhere('bandwidth', 'like', "%{$search}%")
                            ->orWhere('location_address', 'like', "%{$search}%")
                            ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('agreement', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->withQueryString(),
            'filters' => $request->only(['q', 'status', 'sort', 'direction', 'per_page']),
            'statuses' => FibreConnection::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
        ]);
    }
}
