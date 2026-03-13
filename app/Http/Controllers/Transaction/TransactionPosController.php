<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use App\Models\MasterProduct;
use App\Models\GymAdmin;
use App\Models\TransactionProductOut;
use App\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionPosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $allowedGymIds = null;

        if ($user->hasRole('Super Admin')) {
            $gyms = MasterGym::select('id', 'name')->orderBy('name')->get();
        } else {
            $allowedGymIds = GymAdmin::where('admin_id', $user->id)->pluck('gym_id');
            $gyms = MasterGym::whereIn('id', $allowedGymIds)->select('id', 'name')->orderBy('name')->get();
        }

        $selectedGymId = $request->get('gym_id') ?? ($gyms->first()->id ?? null);

        $products = [];
        if ($selectedGymId) {
            $products = MasterProduct::where('gym_id', $selectedGymId)->with('category')->get();
        }

        $pendingQuery = TransactionProductOut::where('status', 'pending')->with(['products.product.category']);
        if ($allowedGymIds) {
            $pendingQuery->whereHas('products.product', function ($q) use ($allowedGymIds) {
                $q->whereIn('gym_id', $allowedGymIds);
            });
        }

        $pendingTransactions = $pendingQuery->latest()->get();

        return Inertia::render('Transaction/POS/Index', [
            'gyms' => $gyms,
            'products' => $products,
            'pendingTransactions' => $pendingTransactions,
            'selectedGymId' => $selectedGymId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, &$out) {
            $out = TransactionProductOut::create([
                'total_price' => 0,
                'status' => 'pending',
            ]);

            $totalSell = 0;

            foreach ($request->items as $item) {
                $product = MasterProduct::findOrFail($item['product_id']);
                $qty = (int) $item['quantity'];

                $buyPriceTotal = $product->buy_price * $qty;
                $sellPriceTotal = $product->sell_price * $qty;

                TransactionProduct::create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'type' => 'out',
                    'buy_price' => $buyPriceTotal,
                    'sell_price' => $sellPriceTotal,
                    'transaction_product_out_id' => $out->id,
                ]);

                $totalSell += $sellPriceTotal;
            }

            $out->total_price = $totalSell;
            $out->save();
        });

        return redirect()->route('transaction.pos.index')->with('success', 'Transaksi POS berhasil dibuat.');
    }

    public function makeFailed(Request $request, TransactionProductOut $transactionProductOut)
    {
        DB::transaction(function () use ($transactionProductOut) {
            $transactionProductOut->products()->delete();
            $transactionProductOut->status = 'failed';
            $transactionProductOut->save();
        });

        return redirect()->back()->with('success', 'Transaksi ditandai gagal dan log produk dihapus.');
    }

    public function makeSuccess(Request $request, TransactionProductOut $transactionProductOut)
    {
        DB::transaction(function () use ($transactionProductOut) {
            foreach ($transactionProductOut->products as $tp) {
                $product = $tp->product;
                if ($product) {
                    $product->decrement('stock', $tp->quantity);
                }
            }

            $transactionProductOut->status = 'success';
            $transactionProductOut->save();
        });

        return redirect()->back()->with('success', 'Transaksi diselesaikan, stok produk diperbarui.');
    }
}
