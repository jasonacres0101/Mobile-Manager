<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        return view('customer.sims.index', [
            'sims' => $company->sims()->with('agreement')->latest()->paginate(25),
            'company' => $company,
        ]);
    }
}
