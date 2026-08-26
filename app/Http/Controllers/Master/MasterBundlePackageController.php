<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\BundlePackagePromo;
use App\Models\MasterBundlePackage;
use App\Models\MasterGym;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterBundlePackageController extends Controller
{
    public function index()
    {
        $bundle_packages = MasterBundlePackage::with(['gym', 'bundlePackagePromos'])->latest()->get();
        return Inertia::render('Master/BundlePackage/Index', [
            'bundle_packages' => $bundle_packages
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/BundlePackage/Create', [
            'gyms' => MasterGym::select('id', 'name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gym_id'                      => 'required|exists:master_gyms,id',
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string',
            'membership_duration_in_days' => 'required|integer|min:1',
            'pt_sessions'                 => 'required|integer|min:1',
            'price'                       => 'required|numeric|min:0',
            'promos'                      => 'nullable|array',
            'promos.*.unique_code'        => 'nullable|string|max:100',
            'promos.*.type'               => 'required_with:promos|in:discount_percent,discount_amount,bonus_days,bonus_sessions',
            'promos.*.value'              => 'required_with:promos|numeric|min:0',
        ]);

        $bundle = MasterBundlePackage::create([
            'gym_id'                      => $validated['gym_id'],
            'name'                        => $validated['name'],
            'description'                 => $validated['description'] ?? null,
            'membership_duration_in_days' => $validated['membership_duration_in_days'],
            'pt_sessions'                 => $validated['pt_sessions'],
            'price'                       => $validated['price'],
        ]);

        if (!empty($validated['promos'])) {
            foreach ($validated['promos'] as $promo) {
                $bundle->bundlePackagePromos()->create([
                    'unique_code' => $promo['unique_code'] ? strtoupper(preg_replace('/\s+/', '', $promo['unique_code'])) : null,
                    'type'        => $promo['type'],
                    'value'       => $promo['value'],
                ]);
            }
        }

        return redirect()->route('master.bundle-package.index')->with('success', 'Paket bundling berhasil dibuat.');
    }

    public function edit(MasterBundlePackage $bundle_package)
    {
        return Inertia::render('Master/BundlePackage/Edit', [
            'bundle_package' => $bundle_package->load('bundlePackagePromos'),
            'gyms'           => MasterGym::select('id', 'name')->get()
        ]);
    }

    public function update(Request $request, MasterBundlePackage $bundle_package)
    {
        $validated = $request->validate([
            'gym_id'                      => 'required|exists:master_gyms,id',
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string',
            'membership_duration_in_days' => 'required|integer|min:1',
            'pt_sessions'                 => 'required|integer|min:1',
            'price'                       => 'required|numeric|min:0',
            'promos'                      => 'nullable|array',
            'promos.*.unique_code'        => 'nullable|string|max:100',
            'promos.*.type'               => 'required_with:promos|in:discount_percent,discount_amount,bonus_days,bonus_sessions',
            'promos.*.value'              => 'required_with:promos|numeric|min:0',
        ]);

        $bundle_package->update([
            'gym_id'                      => $validated['gym_id'],
            'name'                        => $validated['name'],
            'description'                 => $validated['description'] ?? null,
            'membership_duration_in_days' => $validated['membership_duration_in_days'],
            'pt_sessions'                 => $validated['pt_sessions'],
            'price'                       => $validated['price'],
        ]);

        // Sync promos: delete all then recreate
        $bundle_package->bundlePackagePromos()->delete();

        if (!empty($validated['promos'])) {
            foreach ($validated['promos'] as $promo) {
                $bundle_package->bundlePackagePromos()->create([
                    'unique_code' => $promo['unique_code'] ? strtoupper(preg_replace('/\s+/', '', $promo['unique_code'])) : null,
                    'type'        => $promo['type'],
                    'value'       => $promo['value'],
                ]);
            }
        }

        return redirect()->route('master.bundle-package.index')->with('success', 'Paket bundling berhasil diperbarui.');
    }

    public function destroy(MasterBundlePackage $bundle_package)
    {
        $bundle_package->delete();

        return redirect()->back()->with('success', 'Paket bundling berhasil dihapus.');
    }
}
