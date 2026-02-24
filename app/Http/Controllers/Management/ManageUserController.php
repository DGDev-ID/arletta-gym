<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        // 🔥 Custom Order by Role Hierarchy (NO JOIN)
        $query->orderByRaw("
            FIELD(
                (
                    SELECT r.name
                    FROM roles r
                    INNER JOIN model_has_roles mhr 
                        ON r.id = mhr.role_id
                    WHERE mhr.model_id = users.id
                    AND mhr.model_type = ?
                    LIMIT 1
                ),
                'Super Admin',
                'Admin',
                'Personal Trainer',
                'User'
            )
        ", [User::class]);

        // Secondary order
        $query->orderBy('users.created_at', 'desc');

        return Inertia::render('Management/User/Index', [
            'users' => $query
                ->paginate(10)
                ->withQueryString(),

            'filters' => [
                'search' => $request->search ?? '',
                'role'   => $request->role ?? '',
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Management/User/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'nik' => 'nullable|string',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole('User');

            $user->userDetail()->create([
                'nik' => $validated['nik'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'phone_number' => $validated['phone_number'],
            ]);
        });

        return redirect()->route('management.user.index')->with('success', 'User created successfully.');
    }
}
