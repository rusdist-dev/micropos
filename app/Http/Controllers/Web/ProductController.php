<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('pages.products.index');
    }

    public function create(): View
    {
        return view('pages.products.create');
    }

    public function edit(int $id): View
    {
        return view('pages.products.edit', ['id' => $id]);
    }
}
