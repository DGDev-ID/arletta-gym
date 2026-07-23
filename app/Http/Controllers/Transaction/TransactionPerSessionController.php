<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Services\WhatsappBlastService;
use App\Jobs\SendWhatsappBlast;
use Illuminate\Http\Request;
use App\Models\MasterGym;
use App\Models\GymAdmin;
use App\Models\TransactionPerSession;
use App\Models\WABlastTemplate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TransactionPerSessionController extends Controller
{
    private function applyFilters($query, Request $request)
    {
        // Filter gym hanya berlaku jika user secara eksplisit memilih gym dari filter UI
        if ($request->filled('gyms') && !in_array('all', $request->gyms)) {
            $query->whereIn('gym_id', $request->gyms);
        }

        if ($request->filled('statuses') && !in_array('all', $request->statuses)) {
            $query->whereIn('status', $request->statuses);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Semua user melihat semua gym di dropdown filter
        $gyms = MasterGym::select('id', 'name', 'price_per_session')->orderBy('name')->get();

        $query = TransactionPerSession::with('gym');

        $query = $this->applyFilters($query, $request);

        // Sort by pending first, then by latest
        $query->orderByRaw("
            CASE
                WHEN status = 'pending' THEN 1
                WHEN status = 'success' THEN 2
                WHEN status = 'failed' THEN 3
                ELSE 4
            END
        ")->latest();

        $transactions = $query->paginate(10)->withQueryString();

        return Inertia::render('Transaction/TransactionPerSession/Index', [
            'gyms'         => $gyms,
            'transactions' => $transactions,
            'filters'      => [
                'gyms'       => $request->gyms ?? ['all'],
                'statuses'   => $request->statuses ?? ['all'],
                'date_start' => $request->date_start ?? '',
                'date_end'   => $request->date_end ?? '',
            ],
            'isSuperAdmin' => $user->hasRole('Super Admin'),
        ]);
    }

    public function create(Request $request)
    {
        // Semua user dapat memilih semua gym saat membuat transaksi
        $gyms = MasterGym::select('id', 'name', 'price_per_session')->orderBy('name')->get();

        return Inertia::render('Transaction/TransactionPerSession/Create', [
            'gyms' => $gyms,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'gym_id'           => 'required|exists:master_gyms,id',
            'name'             => 'required|string',
            'phone_number'     => 'required|string',
            'transaction_date' => 'nullable|date',
            'payment_method'   => 'required|in:cash,debit',
        ]);

        $phone = $request->phone_number;
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        $request->merge(['phone_number' => $phone]);

        $gym   = MasterGym::findOrFail($request->gym_id);
        $price = $gym->price_per_session !== null ? (float) $gym->price_per_session : 0.00;

        TransactionPerSession::create([
            'gym_id'         => $request->gym_id,
            'name'           => $request->name,
            'phone_number'   => $request->phone_number,
            'price'          => $price,
            'payment_method' => $request->payment_method,
            'status'         => 'pending',
            'created_at'     => $request->transaction_date
                ? \Carbon\Carbon::parse($request->transaction_date)->format('Y-m-d H:i:s')
                : now(),
        ]);

        return redirect()->route('transaction.transaction-per-session.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:success,failed',
        ]);

        $trx = TransactionPerSession::findOrFail($id);
        $trx->status = $request->status;
        $trx->save();

        if ($request->status == 'success') {
            Log::info("Sending WA Blast for Transaction ID: {$trx->unique_id}");
            try {
                $waBlastTemplate = WABlastTemplate::where('template_name', 'INVOICE_MEMBERSHIP_PER_SESSION')->firstOrFail();
                SendWhatsappBlast::dispatch(
                    $trx->phone_number,
                    $waBlastTemplate->template_id,
                    [
                        '{CUST_NAME}' => $trx->name,
                        '{TRANSACTION_DATE}' => $trx->updated_at->format('d M Y H:i'),
                        '{TRANSACTION_PRICE}' => 'Rp ' . number_format($trx->price, 0, ',', '.')
                    ]
                );
            } catch (\Exception $e) {
                // Log error but don't fail the transaction update
            }
        }

        return redirect()->back();
    }

    /**
     * Hapus transaksi per session — hanya bisa diakses oleh Super Admin.
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->hasRole('Super Admin')) {
            abort(403, 'Hanya Super Admin yang dapat menghapus transaksi ini.');
        }

        $trx = TransactionPerSession::findOrFail($id);
        $trx->delete();

        return redirect()->back()->with('success', 'Transaksi berhasil dihapus.');
    }
}
