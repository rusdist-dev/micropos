<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SupplyController extends Controller
{
    public function index(): View
    {
        return view('pages.supplies.index');
    }

    public function create(): View
    {
        return view('pages.supplies.create');
    }

    public function show(int $id): View
    {
        return view('pages.supplies.show', ['id' => $id]);
    }
}
