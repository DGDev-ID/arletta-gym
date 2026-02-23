<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManageAdminController extends Controller
{
    /**
     * Index: Mendapatkan data semua User dengan role Admin
     */
    public function index()
    {
        $admins = User::role('Admin')->with('gyms')->latest()->get();
        // return $admins;
        return Inertia::render('Management/Admin/Index', [
            'admins' => $admins
        ]);
    }

    /**
     * Show: Digunakan untuk pencarian (Search API) via Inertia/Axios
     * Mendapatkan data user yang BUKAN admin berdasarkan email LIKE
     */
    public function show(Request $request)
    {
        $search = $request->query('email');

        if (!$search) {
            return response()->json([]);
        }

        $users = User::withoutRole('Admin')
            ->where('email', 'LIKE', "%{$search}%")
            ->limit(5)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Store: Memberikan role admin ke user.id
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->assignRole('Admin');

        return redirect()->back()->with('success', "{$user->name} berhasil dijadikan Admin.");
    }

    /**
     * Destroy: Melepas role admin dari user
     */
    public function destroy(User $admin)
    {
        $admin->removeRole('Admin');

        return redirect()->back()->with('success', "Akses Admin {$admin->name} telah dicabut.");
    }
}
