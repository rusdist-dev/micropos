<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('pages.customers.index');
    }

    public function create(): View
    {
        return view('pages.customers.create');
    }

    public function edit(int $id): View
    {
        return view('pages.customers.edit', ['id' => $id]);
    }
}
