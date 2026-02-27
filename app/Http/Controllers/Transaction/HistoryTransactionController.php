<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Transaction;
use Inertia\Inertia;

class HistoryTransactionController extends Controller
{
    public function show($id) {
        $transaction = Transaction::with(['user', 'membership', 'fullPt', 'installmentPt', 'transactionDetails'])
            ->findOrFail($id);
        // return $transaction;
        return Inertia::render('Transaction/HistoryTransaction/Show', [
            'transaction' => $transaction
        ]);
    }
    public function update(Request $request, $id) {
        $request->validate([
            'status' => 'required|in:success,failed',
        ]);

        $transaction = Transaction::findOrFail($id);
        $transaction->status = $request->status;
        $transaction->save();

        return redirect()->back();
    }

    public function index(Request $request) {
        // Ambil transaksi dengan status 'success' saja, beserta relasi
        $transactions = Transaction::where('status', 'success')
            ->with(['user', 'membership', 'fullPt', 'installmentPt', 'transactionDetails'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Transaction/HistoryTransaction/Index', [
            'transactions' => $transactions
        ]);
    }
}
