<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('pages.suppliers.index');
    }

    public function create(): View
    {
        return view('pages.suppliers.create');
    }

    public function edit(int $id): View
    {
        return view('pages.suppliers.edit', ['id' => $id]);
    }
}
