<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;
use RuntimeException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $goCardless = app(GoCardlessService::class);
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);
        $sortable = ['invoice_number', 'invoice_date', 'due_date', 'total', 'balance', 'status', 'payment_status', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        return view('admin.invoices.index', [
            'invoices' => Invoice::query()
                ->with(['company.mandates', 'agreement', 'payments'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('invoice_number', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('payment_status', 'like', "%{$search}%")
                            ->orWhere('gocardless_payment_id', 'like', "%{$search}%")
                            ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')))
                ->when($request->input('balance') === 'open', fn ($query) => $query->where('balance', '>', 0))
                ->when($request->input('balance') === 'paid', fn ($query) => $query->where('balance', '<=', 0))
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->withQueryString()
                ->through(function (Invoice $invoice) use ($goCardless) {
                    $invoice->collect_block_reason = $goCardless->collectBlockReason($invoice);

                    return $invoice;
                }),
            'filters' => $request->only(['q', 'payment_status', 'balance', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function collect(Invoice $invoice, GoCardlessService $goCardless)
    {
        try {
            $goCardless->createPaymentForInvoice($invoice);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['collect' => $exception->getMessage()]);
        }

        return back()->with('status', 'Payment collection requested.');
    }
}
