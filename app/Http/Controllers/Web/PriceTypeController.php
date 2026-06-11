<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PriceTypeController extends Controller
{
    public function index(): View
    {
        return view('pages.price-types.index');
    }

    public function create(): View
    {
        return view('pages.price-types.create');
    }

    public function edit(int $id): View
    {
        return view('pages.price-types.edit', ['id' => $id]);
    }
}
