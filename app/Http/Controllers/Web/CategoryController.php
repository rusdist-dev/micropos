<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('pages.categories.index');
    }

    public function create(): View
    {
        return view('pages.categories.create');
    }

    public function edit(int $id): View
    {
        return view('pages.categories.edit', ['id' => $id]);
    }
}
