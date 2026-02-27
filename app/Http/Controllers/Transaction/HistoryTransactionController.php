<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistoryTransactionController extends Controller
{
    public function index() {
        $data = null; // Isi trx dengan status success

        return Inertia::render('Transaction/HistoryTransaction/Index', [
            'data' => $data
        ]);
    }
}
