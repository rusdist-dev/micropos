<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('pages.users.index');
    }

    public function create(): View
    {
        return view('pages.users.create');
    }

    public function edit(int $id): View
    {
        return view('pages.users.edit', ['id' => $id]);
    }
}
