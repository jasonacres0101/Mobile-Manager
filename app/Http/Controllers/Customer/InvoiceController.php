<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        return view('customer.invoices.index', [
            'invoices' => $company->invoices()->with(['agreement', 'payments'])->latest()->paginate(25),
            'company' => $company,
        ]);
    }
}
