<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use App\Models\MasterMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterMembershipController extends Controller
{
    public function index()
    {
        $memberships = MasterMembership::with(['gym', 'membershipPromos'])->latest()->get();
        return Inertia::render('Master/Membership/Index', [
            'memberships' => $memberships
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Membership/Create', [
            'gyms' => MasterGym::select('id', 'name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string|max:255',
            'duration_in_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            // Validasi array promo
            'promos' => 'nullable|array',
            'promos.*.unique_code' => 'required|unique:membership_promos,unique_code',
            'promos.*.type' => 'required|in:discount_percent,discount_amount,bonus_days',
            'promos.*.value' => 'required|numeric|min:0',
        ]);

        $membership = MasterMembership::create([
            'gym_id' => $validated['gym_id'],
            'name' => $validated['name'],
            'duration_in_days' => $validated['duration_in_days'],
            'price' => $validated['price'],
        ]);

        if (!empty($validated['promos'])) {
            $membership->membershipPromos()->createMany($validated['promos']);
        }

        return redirect()->route('master.membership.index')->with('success', 'Membership & Promo berhasil dibuat.');
    }

    public function edit(MasterMembership $membership)
    {
        // Load SEMUA promo (Has Many)
        $membership->load('membershipPromos');

        return Inertia::render('Master/Membership/Edit', [
            'membership' => $membership,
            'gyms' => MasterGym::select('id', 'name')->get()
        ]);
    }

    public function update(Request $request, MasterMembership $membership)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string|max:255',
            'duration_in_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'promos' => 'nullable|array',
            'promos.*.unique_code' => 'required|string', // Validasi unique manual/custom jika perlu
            'promos.*.type' => 'required|in:discount_percent,discount_amount,bonus_days',
            'promos.*.value' => 'required|numeric|min:0',
        ]);

        $membership->update([
            'gym_id' => $validated['gym_id'],
            'name' => $validated['name'],
            'duration_in_days' => $validated['duration_in_days'],
            'price' => $validated['price'],
        ]);

        // Cara termudah untuk Has Many: Hapus semua yang lama, buat baru
        // Atau gunakan sync if you have custom logic
        $membership->membershipPromos()->delete();
        if (!empty($validated['promos'])) {
            $membership->membershipPromos()->createMany($validated['promos']);
        }

        return redirect()->route('master.membership.index')->with('success', 'Membership berhasil diperbarui.');
    }

    public function destroy(MasterMembership $membership)
    {
        $membership->promo()->delete();
        $membership->delete();

        return redirect()->back()->with('success', 'Membership berhasil dihapus.');
    }
}
