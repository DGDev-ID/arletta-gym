<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManageUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        // Search
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($subQ) use ($search) {
                $subQ->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        });

        // Filter Role
        $query->when($request->role, function ($q, $role) {
            $q->whereHas('roles', function ($sq) use ($role) {
                $sq->where('name', $role);
            });
        });

        return Inertia::render('Management/User/Index', [
            'users' => $query
                ->latest()
                ->paginate(10)
                ->withQueryString(),

            'filters' => [
                'search' => $request->search ?? '',
                'role'   => $request->role ?? '',
            ],
        ]);
    }
}
