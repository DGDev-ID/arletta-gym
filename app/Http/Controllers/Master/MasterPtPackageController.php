<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use App\Models\MasterPtPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterPtPackageController extends Controller
{
    public function index()
    {
        $pt_packages = MasterPtPackage::with(['gym', 'ptPackagePromos'])->latest()->get();
        return Inertia::render('Master/PersonalTrainerPackage/Index', [
            'pt_packages' => $pt_packages
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/PersonalTrainerPackage/Create', [
            'gyms' => MasterGym::select('id', 'name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string|max:255',
            'duration_in_sessions' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'max_person' => 'required|integer|min:1',
            // Validasi array promo
            'promos' => 'nullable|array',
            'promos.*.unique_code' => 'nullable|unique:pt_package_promos,unique_code',
            'promos.*.type' => 'required|in:discount_percent,discount_amount,bonus_sessions',
            'promos.*.value' => 'required|numeric|min:0',
        ]);

        $pt_package = MasterPtPackage::create([
            'gym_id' => $validated['gym_id'],
            'name' => $validated['name'],
            'duration_in_sessions' => $validated['duration_in_sessions'],
            'price' => $validated['price'],
            'max_person' => $validated['max_person'],
        ]);

        if (!empty($validated['promos'])) {
            $pt_package->ptPackagePromos()->createMany($validated['promos']);
        }

        return redirect()->route('master.personal-trainer-package.index')->with('success', 'PT Package & Promo berhasil dibuat.');
    }

    public function edit(MasterPtPackage $personal_trainer_package)
    {
        // Load SEMUA promo (Has Many)
        $personal_trainer_package->load('ptPackagePromos');

        return Inertia::render('Master/PersonalTrainerPackage/Edit', [
            'pt_package' => $personal_trainer_package,
            'gyms' => MasterGym::select('id', 'name')->get()
        ]);
    }

    public function update(Request $request, MasterPtPackage $personal_trainer_package)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string|max:255',
            'duration_in_sessions' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'max_person' => 'required|integer|min:1',
            'promos' => 'nullable|array',
            'promos.*.unique_code' => 'nullable|unique:pt_package_promos,unique_code',
            'promos.*.type' => 'required|in:discount_percent,discount_amount,bonus_days',
            'promos.*.value' => 'required|numeric|min:0',
        ]);

        $personal_trainer_package->update([
            'gym_id' => $validated['gym_id'],
            'name' => $validated['name'],
            'duration_in_sessions' => $validated['duration_in_sessions'],
            'price' => $validated['price'],
            'max_person' => $validated['max_person'],
        ]);

        // Cara termudah untuk Has Many: Hapus semua yang lama, buat baru
        // Atau gunakan sync if you have custom logic
        $personal_trainer_package->ptPackagePromos()->delete();
        if (!empty($validated['promos'])) {
            $personal_trainer_package->ptPackagePromos()->createMany($validated['promos']);
        }

        return redirect()->route('master.personal-trainer-package.index')->with('success', 'PT Package berhasil diperbarui.');
    }

    public function destroy(MasterPtPackage $pt_package)
    {
        $pt_package->ptPackagePromos()->delete();
        $pt_package->delete();

        return redirect()->back()->with('success', 'PT Package berhasil dihapus.');
    }
}
