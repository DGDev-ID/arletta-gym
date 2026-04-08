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
    public function index(Request $request)
    {
        $user = $request->user();
        // Determine gyms visible to the user (Super Admin -> all, Admin -> assigned gyms)
        $allowedGymIds = null;
        if ($user->hasRole('Super Admin')) {
            $gyms = MasterGym::select('id', 'name', 'price_per_session')->orderBy('name')->get();
        } else {
            $allowedGymIds = GymAdmin::where('admin_id', $user->id)->pluck('gym_id');
            $gyms = MasterGym::whereIn('id', $allowedGymIds)->select('id', 'name', 'price_per_session')->orderBy('name')->get();
        }

        // Build queries for success / pending and apply gym filter for non-super-admins
        $successQuery = TransactionPerSession::with('gym')->latest()->where('status', 'success');
        $pendingQuery = TransactionPerSession::with('gym')->latest()->where('status', 'pending');

        if ($allowedGymIds) {
            $successQuery->whereIn('gym_id', $allowedGymIds);
            $pendingQuery->whereIn('gym_id', $allowedGymIds);
        }

        $successTransactions = $successQuery->get();
        $pendingTransactions = $pendingQuery->get();

        return Inertia::render('Transaction/TransactionPerSession/Index', [
            'gyms' => $gyms,
            'successTransactions' => $successTransactions,
            'pendingTransactions' => $pendingTransactions,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Super Admin')) {
            $gyms = MasterGym::select('id', 'name', 'price_per_session')->orderBy('name')->get();
        } else {
            $gymIds = GymAdmin::where('admin_id', $user->id)->pluck('gym_id');
            $gyms = MasterGym::whereIn('id', $gymIds)->select('id', 'name', 'price_per_session')->orderBy('name')->get();
        }

        return Inertia::render('Transaction/TransactionPerSession/Create', [
            'gyms' => $gyms,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        $phone = $request->phone_number;
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        $request->merge([
            'phone_number' => $phone
        ]);

        $gym = MasterGym::findOrFail($request->gym_id);
        $price = $gym->price_per_session !== null ? (float) $gym->price_per_session : 0.00;

        TransactionPerSession::create([
            'gym_id' => $request->gym_id,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'price' => $price,
            'status' => 'pending',
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
}
