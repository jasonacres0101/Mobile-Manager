<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JolaProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class JolaProductController extends Controller
{
    public function index()
    {
        return view('admin.jola-products.index', [
            'products' => JolaProduct::latest('last_synced_at')->paginate(25),
        ]);
    }

    public function sync(Request $request)
    {
        try {
            Artisan::call('sync:jola-products');

            return redirect()
                ->route('admin.jola-products.index')
                ->with('status', trim(Artisan::output()) ?: 'Jola products synced.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.jola-products.index')
                ->withErrors(['jola_products' => 'Unable to sync Jola products. Check the Jola settings and connection status.']);
        }
    }
}
