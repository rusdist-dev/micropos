<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('pages.transactions.index');
    }

    public function show(int $id): View
    {
        return view('pages.transactions.show', ['id' => $id]);
    }
}
