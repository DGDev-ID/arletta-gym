<?php

namespace App\Http\Controllers;

use App\Models\GymPt;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserGym;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // --- User Stats (role: User) ---
        $userRoleQuery = fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'User'));

        $totalUsers = $userRoleQuery()->count();

        $newUsersThisMonth = $userRoleQuery()
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $activeUsers = UserGym::where('membership_end_at', '>=', $now->toDateString())
            ->whereHas('user', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'User')))
            ->distinct('user_id')
            ->count('user_id');

        $inactiveUsers = max(0, $totalUsers - $activeUsers);

        // --- PT Stats (role: Personal Trainer) ---
        $ptRoleQuery = fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Personal Trainer'));

        $totalPt = $ptRoleQuery()->count();

        $newPtThisMonth = $ptRoleQuery()
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $activePt = GymPt::whereHas('pt', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'Personal Trainer')))
            ->distinct('pt_id')
            ->count('pt_id');

        $inactivePt = max(0, $totalPt - $activePt);

        // --- Transaction Stats (this month) ---
        $txBaseQuery = fn () => Transaction::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year);

        $totalTransactions    = $txBaseQuery()->count();
        $paidTransactions     = $txBaseQuery()->where('status', 'success')->count();
        $pendingTransactions  = $txBaseQuery()->where('status', 'pending')->count();
        $failedTransactions   = $txBaseQuery()->where('status', 'failed')->count();

        // --- Revenue Stats (this month, status = success) ---
        $revenueBaseQuery = fn () => Transaction::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where('status', 'success');

        $totalRevenue = $revenueBaseQuery()->sum('total_price');

        // Bulanan = membership with duration_in_days <= 31
        $bulananRevenue = $revenueBaseQuery()
            ->where('transaction_type', 'membership')
            ->whereHas('membership', fn ($q) => $q->where('duration_in_days', '<=', 31))
            ->sum('total_price');

        // Personal = PT packages (full or installment)
        $personalRevenue = $revenueBaseQuery()
            ->whereIn('transaction_type', ['full_pt', 'installment_pt'])
            ->sum('total_price');

        // Tahunan = membership with duration_in_days > 31
        $tahunanRevenue = $revenueBaseQuery()
            ->where('transaction_type', 'membership')
            ->whereHas('membership', fn ($q) => $q->where('duration_in_days', '>', 31))
            ->sum('total_price');

        // --- 12-Month Chart Data ---
        $chartRevenue    = [];
        $chartNewMembers = [];
        $chartNewPt      = [];
        $chartMonths     = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            $revenue = Transaction::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->where('status', 'success')
                ->sum('total_price');

            $newMembers = User::role('User')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $newPt = User::role('Personal Trainer')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $chartRevenue[]    = round($revenue / 1_000_000, 2); // in millions
            $chartNewMembers[] = $newMembers;
            $chartNewPt[]      = $newPt;
            $chartMonths[]     = $month->format('M Y');
        }

        // --- Recent Transactions (last 3) ---
        $recentTransactions = Transaction::with(['user', 'membership', 'fullPt', 'installmentPt'])
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($tx) {
                if ($tx->transaction_type === 'membership') {
                    $package = $tx->membership?->name ?? 'Membership';
                } elseif ($tx->transaction_type === 'full_pt') {
                    $package = $tx->fullPt?->name ?? 'Personal Training';
                } else {
                    $package = 'PT (Cicilan)';
                }

                return [
                    'unique_id'   => $tx->unique_id,
                    'member_name' => $tx->user?->name ?? 'Unknown',
                    'package'     => $package,
                    'amount'      => (float) $tx->total_price,
                    'date'        => $tx->created_at->format('d M Y'),
                    'status'      => $tx->status,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_users'          => $totalUsers,
                'new_users'            => $newUsersThisMonth,
                'active_users'         => $activeUsers,
                'inactive_users'       => $inactiveUsers,
                'total_pt'             => $totalPt,
                'new_pt'               => $newPtThisMonth,
                'active_pt'            => $activePt,
                'inactive_pt'          => $inactivePt,
                'total_transactions'   => $totalTransactions,
                'paid_transactions'   => $paidTransactions,
                'pending_transactions'=> $pendingTransactions,
                'failed_transactions' => $failedTransactions,
                'total_revenue'       => (float) $totalRevenue,
                'bulanan_revenue'     => (float) $bulananRevenue,
                'personal_revenue'    => (float) $personalRevenue,
                'tahunan_revenue'     => (float) $tahunanRevenue,
            ],
            'chart_data' => [
                'months'      => $chartMonths,
                'revenue'     => $chartRevenue,
                'new_members' => $chartNewMembers,
                'new_pt'      => $chartNewPt,
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $now = Carbon::now();

        $transactions = Transaction::with(['user', 'membership', 'fullPt', 'installmentPt'])
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'transaksi_' . $now->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'ID Transaksi',
                'Nama Member',
                'Email',
                'Tipe Transaksi',
                'Paket',
                'Metode Pembayaran',
                'Harga Dasar',
                'Biaya Midtrans',
                'Biaya PPN',
                'Total Harga',
                'Status',
                'Tanggal',
            ], ';');

            $no = 1;
            foreach ($transactions as $tx) {
                if ($tx->transaction_type === 'membership') {
                    $package = $tx->membership?->name ?? 'Membership';
                } elseif ($tx->transaction_type === 'full_pt') {
                    $package = $tx->fullPt?->name ?? 'Personal Training';
                } else {
                    $package = 'PT (Cicilan)';
                }

                fputcsv($handle, [
                    $no++,
                    $tx->unique_id,
                    $tx->user?->name ?? 'Unknown',
                    $tx->user?->email ?? '',
                    $tx->transaction_type,
                    $package,
                    $tx->method ?? '',
                    (int) $tx->price,
                    (int) $tx->midtrans_fee,
                    (int) $tx->ppn_fee,
                    (int) $tx->total_price,
                    $tx->status,
                    $tx->created_at->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
