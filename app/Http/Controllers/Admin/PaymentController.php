<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);
        $sortable = ['amount', 'status', 'charge_date', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        return view('admin.payments.index', [
            'payments' => Payment::query()
                ->with(['company', 'invoice'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('gocardless_payment_id', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('invoice', fn ($query) => $query->where('invoice_number', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->withQueryString(),
            'filters' => $request->only(['q', 'status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function refresh(GoCardlessService $goCardless)
    {
        $count = $goCardless->refreshAllLocalPayments();

        return redirect()
            ->route('admin.payments.index')
            ->with('status', "Refreshed {$count} GoCardless payment(s).");
    }
}
