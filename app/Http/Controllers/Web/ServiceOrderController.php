<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ServiceOrderController extends Controller
{
    public function index(): View
    {
        return view('pages.service-orders.index');
    }

    public function create(): View
    {
        return view('pages.service-orders.create');
    }

    public function show(int $id): View
    {
        return view('pages.service-orders.show', ['id' => $id]);
    }
}
