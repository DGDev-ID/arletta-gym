<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use \App\Models\Transaction;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Resolve gym and item details for a transaction, used by both print and download.
     */
    private function resolveInvoiceData(Transaction $transaction): array
    {
        $gym = null;
        $itemDetails = [];

        if ($transaction->transaction_type === 'membership' && $transaction->membership) {
            $gym = $transaction->membership->gym;
            $itemDetails[] = [
                'item_name'     => $transaction->membership->name,
                'item_price'    => (int) $transaction->price,
                'item_quantity' => 1,
                'subtotal'      => (int) $transaction->price,
            ];
        } elseif ($transaction->transaction_type === 'full_pt' && $transaction->fullPt) {
            $gym = $transaction->fullPt->gym;
            $itemDetails[] = [
                'item_name'     => $transaction->fullPt->name,
                'item_price'    => (int) $transaction->price,
                'item_quantity' => 1,
                'subtotal'      => (int) $transaction->price,
            ];
        } elseif ($transaction->transaction_type === 'installment_pt' && $transaction->installmentPt) {
            $itemDetails[] = [
                'item_name'     => 'Personal Training (Cicilan)',
                'item_price'    => (int) $transaction->price,
                'item_quantity' => 1,
                'subtotal'      => (int) $transaction->price,
            ];
        } else {
            $itemDetails[] = [
                'item_name'     => 'Transaksi',
                'item_price'    => (int) $transaction->price,
                'item_quantity' => 1,
                'subtotal'      => (int) $transaction->price,
            ];
        }

        if (!$gym) {
            $gym = MasterGym::first() ?? (object) ['name' => 'Arletta Gym', 'address' => ''];
        }

        $totalPrice = (int) $transaction->price;
        $fee        = (int) (($transaction->midtrans_fee ?? 0) + ($transaction->ppn_fee ?? 0));
        $totalPay   = (int) $transaction->total_price;

        return compact('gym', 'itemDetails', 'totalPrice', 'fee', 'totalPay');
    }

    public function printInvoice($id)
    {
        $transaction = Transaction::with(['user', 'membership.gym', 'fullPt.gym', 'installmentPt', 'transactionDetails'])
            ->findOrFail($id);

        ['gym' => $gym, 'itemDetails' => $itemDetails, 'totalPrice' => $totalPrice, 'fee' => $fee, 'totalPay' => $totalPay]
            = $this->resolveInvoiceData($transaction);

        $payload = [
            'gym' => [
                'name'    => $gym->name,
                'address' => $gym->address ?? '',
            ],
            'total_price'  => $totalPrice,
            'fee'          => $fee,
            'total_pay'    => $totalPay,
            'item_details' => $itemDetails,
        ];

        return response()->json($payload);
    }

    public function downloadInvoice($id)
    {
        $transaction = Transaction::with(['user', 'membership.gym', 'fullPt.gym', 'installmentPt', 'transactionDetails'])
            ->findOrFail($id);

        ['gym' => $gym, 'itemDetails' => $itemDetails, 'totalPrice' => $totalPrice, 'fee' => $fee, 'totalPay' => $totalPay]
            = $this->resolveInvoiceData($transaction);

        $pdf = Pdf::loadView('invoices.transaction', compact(
            'transaction', 'gym', 'itemDetails', 'totalPrice', 'fee', 'totalPay'
        ))->setPaper('a4', 'portrait');

        $filename = 'invoice_' . ($transaction->unique_id ?: $transaction->id) . '.pdf';

        return $pdf->download($filename);
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

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('gyms') && !in_array('all', $request->gyms)) {
            $gymIds = $request->gyms;
            $query->where(function ($q) use ($gymIds) {
                $q->whereHas('membership', fn($sq) => $sq->whereIn('gym_id', $gymIds))
                  ->orWhereHas('fullPt', fn($sq) => $sq->whereIn('gym_id', $gymIds));
            });
        }

        if ($request->filled('methods') && !in_array('all', $request->methods)) {
            $query->whereIn('method', $request->methods);
        }

        if ($request->filled('transaction_types') && !in_array('all', $request->transaction_types)) {
            $query->whereIn('transaction_type', $request->transaction_types);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        return $query;
    }

    public function index(Request $request) {
        $query = Transaction::where('status', 'success')
            ->with(['user', 'membership.gym', 'fullPt.gym', 'installmentPt', 'transactionDetails'])
            ->latest();

        $query = $this->applyFilters($query, $request);

        $transactions = $query->paginate(10)->withQueryString();

        $gyms = MasterGym::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Transaction/HistoryTransaction/Index', [
            'transactions' => $transactions,
            'gyms' => $gyms,
            'filters' => [
                'gyms' => $request->gyms ?? ['all'],
                'methods' => $request->methods ?? ['all'],
                'transaction_types' => $request->transaction_types ?? ['all'],
                'date_start' => $request->date_start ?? '',
                'date_end' => $request->date_end ?? '',
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Transaction::where('status', 'success')
            ->with(['user', 'membership.gym', 'fullPt.gym', 'installmentPt'])
            ->latest();

        $query = $this->applyFilters($query, $request);

        $transactions = $query->get();

        $now = Carbon::now();
        $filename = 'history_transaksi_' . $now->format('Y_m_d') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'ID Transaksi',
                'Nama Member',
                'Email',
                'Gym',
                'Tipe Transaksi',
                'Paket',
                'Metode Pembayaran',
                'Detail Metode',
                'Harga Dasar',
                'Biaya Midtrans',
                'Biaya PPN',
                'Total Harga',
                'Sesi / Hari',
                'Status',
                'Tanggal',
            ], ';');

            $no = 1;
            foreach ($transactions as $tx) {
                if ($tx->transaction_type === 'membership') {
                    $package = $tx->membership?->name ?? 'Membership';
                    $gym = $tx->membership?->gym?->name ?? '-';
                } elseif ($tx->transaction_type === 'full_pt') {
                    $package = $tx->fullPt?->name ?? 'Personal Training';
                    $gym = $tx->fullPt?->gym?->name ?? '-';
                } else {
                    $package = 'PT (Cicilan)';
                    $gym = '-';
                }

                fputcsv($handle, [
                    $no++,
                    $tx->unique_id,
                    $tx->user?->name ?? '-',
                    $tx->user?->email ?? '',
                    $gym,
                    $tx->transaction_type,
                    $package,
                    $tx->method ?? '',
                    $tx->method_midtrans_detail ?? '-',
                    (int) $tx->price,
                    (int) $tx->midtrans_fee,
                    (int) $tx->ppn_fee,
                    (int) $tx->total_price,
                    $tx->sessions_or_days ?? '-',
                    $tx->status,
                    $tx->created_at?->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
