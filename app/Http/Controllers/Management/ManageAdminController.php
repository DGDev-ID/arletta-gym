<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManageAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('Admin')->with('gyms');

        return Inertia::render('Management/Admin/Index', [
            'admins' => $query->latest()->paginate(10)->withQueryString(),
        ]);
    }

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

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->assignRole('Admin');

        return redirect()->back()->with('success', "{$user->name} berhasil dijadikan Admin.");
    }

    public function destroy(User $admin)
    {
        $admin->removeRole('Admin');

        return redirect()->back()->with('success', "Akses Admin {$admin->name} telah dicabut.");
    }
}
