<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use App\Models\MasterProduct;
use App\Models\MasterProductCategory;
use App\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MasterProductController extends Controller
{
    public function index(Request $request)
    {
        $products = MasterProduct::with(['category', 'gym'])->latest()->paginate(10);

        return Inertia::render('Master/Product/Index', [
            'products' => $products
        ]);
    }

    public function create()
    {
        $gyms = MasterGym::select('id', 'name');
        if (Auth::user()->hasRole('Admin')) {
            $gyms = MasterGym::join('gym_admins', 'master_gyms.id', '=', 'gym_admins.gym_id')
                ->where('gym_admins.admin_id', Auth::id())
                ->select('master_gyms.id', 'master_gyms.name');
        }

        $categories = MasterProductCategory::select('id', 'name')->get();

        return Inertia::render('Master/Product/Create', [
            'gyms' => $gyms->get(),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'product_category_id' => 'required|exists:master_product_categories,id',
            'name' => 'required|string|max:255',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
        ]);

        MasterProduct::create($validated);

        return redirect()->route('master.product.index')->with('success', 'Product berhasil dibuat.');
    }

    public function edit(MasterProduct $product)
    {
        $product->load(['category', 'gym']);

        $gyms = MasterGym::select('id', 'name');
        if (Auth::user()->hasRole('Admin')) {
            $gyms = MasterGym::join('gym_admins', 'master_gyms.id', '=', 'gym_admins.gym_id')
                ->where('gym_admins.admin_id', Auth::id())
                ->select('master_gyms.id', 'master_gyms.name');
        }

        $categories = MasterProductCategory::select('id', 'name')->get();

        $stockLogs = TransactionProduct::where('product_id', $product->id)
            ->where('type', 'in')
            ->latest()
            ->get();

        return Inertia::render('Master/Product/Edit', [
            'product' => $product,
            'gyms' => $gyms->get(),
            'categories' => $categories,
            'stockLogs' => $stockLogs,
        ]);
    }

    public function update(Request $request, MasterProduct $product)
    {
        $validated = $request->validate([
            'product_category_id' => 'required|exists:master_product_categories,id',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'name' => 'sometimes|required|string|max:255',
        ]);

        $product->update($validated);

        return redirect()->route('master.product.index')->with('success', 'Product berhasil diperbarui.');
    }

    public function destroy(MasterProduct $product)
    {
        $product->delete();

        return redirect()->route('master.product.index')->with('success', 'Product berhasil dihapus.');
    }

    public function addStock(Request $request, MasterProduct $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($product, $validated) {
            TransactionProduct::create([
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'type' => 'in',
                'buy_price' => $product->buy_price,
                'sell_price' => $product->sell_price,
                'transaction_product_out_id' => null,
            ]);

            $product->increment('stock', $validated['quantity']);
        });

        return redirect()->back()->with('success', 'Stok berhasil ditambahkan.');
    }

    public function reduceStock(Request $request, MasterProduct $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($product, $validated) {
            $newStock = max(0, $product->stock - $validated['quantity']);
            $product->update(['stock' => $newStock]);
        });

        return redirect()->back()->with('success', 'Stok berhasil dikurangi.');
    }

    public function resetStock(MasterProduct $product)
    {
        $product->update(['stock' => 0]);

        return redirect()->back()->with('success', 'Stok berhasil direset ke 0.');
    }

    public function deleteStockLog(TransactionProduct $stockLog)
    {
        if ($stockLog->type !== 'in') {
            abort(403, 'Hanya log barang masuk yang bisa dihapus.');
        }

        DB::transaction(function () use ($stockLog) {
            $product = $stockLog->product;
            if ($product) {
                $product->decrement('stock', $stockLog->quantity);
            }
            $stockLog->delete();
        });

        return redirect()->back()->with('success', 'Log barang masuk berhasil dihapus.');
    }
}
