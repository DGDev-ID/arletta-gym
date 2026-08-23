<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterBundlePackage;
use App\Models\MasterGym;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterBundlePackageController extends Controller
{
    public function index()
    {
        $bundle_packages = MasterBundlePackage::with(['gym'])->latest()->get();
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
        ]);

        MasterBundlePackage::create($validated);

        return redirect()->route('master.bundle-package.index')->with('success', 'Paket bundling berhasil dibuat.');
    }

    public function edit(MasterBundlePackage $bundle_package)
    {
        return Inertia::render('Master/BundlePackage/Edit', [
            'bundle_package' => $bundle_package,
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
        ]);

        $bundle_package->update($validated);

        return redirect()->route('master.bundle-package.index')->with('success', 'Paket bundling berhasil diperbarui.');
    }

    public function destroy(MasterBundlePackage $bundle_package)
    {
        $bundle_package->delete();

        return redirect()->back()->with('success', 'Paket bundling berhasil dihapus.');
    }
}
