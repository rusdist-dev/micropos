<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ReturnController extends Controller
{
    public function index(): View
    {
        return view('pages.returns.index');
    }

    public function create(): View
    {
        return view('pages.returns.create');
    }

    public function show(int $id): View
    {
        return view('pages.returns.show', ['id' => $id]);
    }
}
