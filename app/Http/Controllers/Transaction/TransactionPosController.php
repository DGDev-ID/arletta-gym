<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use App\Models\MasterProduct;
use App\Models\GymAdmin;
use App\Models\TransactionProductOut;
use App\Models\TransactionProduct;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $successQuery = TransactionProductOut::where('status', 'success')->with(['products.product.category', 'products.product.gym']);
        if ($allowedGymIds) {
            $successQuery->whereHas('products.product', function ($q) use ($allowedGymIds) {
                $q->whereIn('gym_id', $allowedGymIds);
            });
        }
        $successTransactions = $successQuery->latest()->get();

        return Inertia::render('Transaction/POS/Index', [
            'gyms' => $gyms,
            'products' => $products,
            'pendingTransactions' => $pendingTransactions,
            'successTransactions' => $successTransactions,
            'selectedGymId' => $selectedGymId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,debit',
        ]);

        DB::transaction(function () use ($request, &$out) {
            $out = TransactionProductOut::create([
                'total_price' => 0,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
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

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $allowedGymIds = null;

        if (!$user->hasRole('Super Admin')) {
            $allowedGymIds = GymAdmin::where('admin_id', $user->id)->pluck('gym_id');
        }

        $query = TransactionProductOut::where('status', 'success')
            ->with(['products.product.category', 'products.product.gym']);

        if ($allowedGymIds) {
            $query->whereHas('products.product', function ($q) use ($allowedGymIds) {
                $q->whereIn('gym_id', $allowedGymIds);
            });
        }

        $transactions = $query->latest()->get();

        $now = Carbon::now();
        $filename = 'kasir_pembayaran_' . $now->format('Y_m_d') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'ID Transaksi',
                'Nama Produk',
                'Kategori',
                'Gym',
                'Qty',
                'Harga Beli',
                'Harga Jual',
                'Total Harga Transaksi',
                'Status',
                'Metode Pembayaran',
                'Tanggal',
            ], ';');

            $no = 1;
            foreach ($transactions as $trx) {
                foreach ($trx->products as $tp) {
                    fputcsv($handle, [
                        $no++,
                        $trx->id,
                        $tp->product?->name ?? '-',
                        $tp->product?->category?->name ?? '-',
                        $tp->product?->gym?->name ?? '-',
                        $tp->quantity,
                        (int) $tp->buy_price,
                        (int) $tp->sell_price,
                        (int) $trx->total_price,
                        $trx->status,
                        $trx->payment_method ?? '-',
                        $trx->created_at?->format('d/m/Y H:i'),
                    ], ';');
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadInvoice(Request $request, TransactionProductOut $transactionProductOut)
    {
        $transactionProductOut->load(['products.product.category', 'products.product.gym']);

        // Resolve gym info from the first product's gym
        $firstProduct = $transactionProductOut->products->first();
        $gym = $firstProduct?->product?->gym;

        $gymName    = $gym?->name    ?? config('app.name', 'Arletta Gym');
        $gymAddress = $gym?->address ?? '-';

        $totalPrice = (int) $transactionProductOut->total_price;
        $fee        = 0;
        $totalPay   = $totalPrice + $fee;

        $pdf = pdf::loadView('pdf.pos-invoice', [
            'transaction' => $transactionProductOut,
            'gymName'     => $gymName,
            'gymAddress'  => $gymAddress,
            'totalPrice'  => $totalPrice,
            'fee'         => $fee,
            'totalPay'    => $totalPay,
        ])->setPaper('a5', 'portrait');

        $filename = 'invoice-' . str_pad($transactionProductOut->id, 5, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->download($filename);
    }
}
