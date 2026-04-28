<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);
        $sortable = ['name', 'connectwise_agreement_id', 'connectwise_agreement_type_id', 'status', 'start_date', 'end_date', 'last_synced_at', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        return view('admin.agreements.index', [
            'agreements' => Agreement::query()
                ->with('company')
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('connectwise_agreement_id', 'like', "%{$search}%")
                            ->orWhere('connectwise_agreement_type_id', 'like', "%{$search}%")
                            ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
                ->when($request->filled('type_id'), fn ($query) => $query->where('connectwise_agreement_type_id', $request->input('type_id')))
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->withQueryString(),
            'filters' => $request->only(['q', 'status', 'type_id', 'sort', 'direction', 'per_page']),
            'statuses' => Agreement::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
            'typeIds' => Agreement::query()->whereNotNull('connectwise_agreement_type_id')->distinct()->orderBy('connectwise_agreement_type_id')->pluck('connectwise_agreement_type_id'),
        ]);
    }
}
