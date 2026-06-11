<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('pages.roles.index');
    }

    public function create(): View
    {
        return view('pages.roles.create');
    }

    public function edit(int $id): View
    {
        return view('pages.roles.edit', ['id' => $id]);
    }
}
