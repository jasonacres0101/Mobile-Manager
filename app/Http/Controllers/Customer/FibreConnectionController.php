<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FibreConnectionController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        return view('customer.fibre-connections.index', [
            'connections' => $company->fibreConnections()->with('agreement')->latest()->paginate(25),
            'company' => $company,
        ]);
    }
}
