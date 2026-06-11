<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class StockOpnameController extends Controller
{
    public function index(): View
    {
        return view('pages.stock-opnames.index');
    }

    public function create(): View
    {
        return view('pages.stock-opnames.create');
    }

    public function show(int $id): View
    {
        return view('pages.stock-opnames.show', ['id' => $id]);
    }
}
