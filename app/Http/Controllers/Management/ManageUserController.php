<?php

namespace App\Http\Controllers\Management;

use App\Helpers\S3Helper;
use App\Http\Controllers\Controller;
use App\Http\Services\PaymentService;
use App\Http\Services\UpdateStatusTransactionService;
use App\Models\MasterGym;
use App\Models\MembershipPromo;
use App\Models\PtPackagePromo;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPtPackage;
use App\Models\UserPtPackageInstalment;
use App\Models\UserPtPackageMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ManageUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['roles', 'userGyms' => function ($q) {
            $q->select('user_id', 'membership_end_at')->orderBy('membership_end_at', 'desc');
        }]);

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

        // Secondary order
        $query->orderBy('users.created_at', 'asc');

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
            'photo' => 'nullable|image|max:40720|mimes:jpeg,png,jpg,gif',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole('User');

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $tempFileName = S3Helper::storeFileTemp($file);
                $s3Path = S3Helper::storeFileToS3("user-profile", $tempFileName);
                $photoPath = S3Helper::getUrlFileS3("user-profile", $tempFileName);
                S3Helper::removeFileTemp($tempFileName);
            }

            $user->userDetail()->create([
                'nik' => $validated['nik'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'phone_number' => $validated['phone_number'],
                'photo' => $photoPath,
                'emergency_name' => $validated['emergency_name'] ?? null,
                'emergency_phone' => $validated['emergency_phone'] ?? null,
                'emergency_relation' => $validated['emergency_relation'] ?? null,
            ]);
        });

        return redirect()->route('management.user.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        abort_unless($user->hasRole('User'), 403);

        $user->load('userDetail');

        $gyms = MasterGym::all();
        if (Auth::user()->hasRole('Admin')) {
            $gyms = MasterGym::join('gym_admins', 'master_gyms.id', '=', 'gym_admins.gym_id')
                ->where('gym_admins.admin_id', Auth::id())
                ->select('master_gyms.id', 'master_gyms.name')
                ->get();
        }

        $pendingTransactions = Transaction::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('method', 'manual')
            ->with(['membership', 'fullPt', 'installmentPt']) // Eager load relasi paketnya
            ->latest()
            ->get();

        $userPtPackageIds = UserPtPackageMember::where('user_id', $user->id)->pluck('user_pt_package_id');
        $listInstalments = UserPtPackage::whereIn('id', $userPtPackageIds)->where('status', 'instalment')->pluck('id');
        $pendingInstalments = UserPtPackageInstalment::whereIn('user_pt_package_id', $listInstalments)->where('status', 'unpaid')->get();

        return Inertia::render('Management/User/Show', [
            'user' => $user,
            'gyms' => $gyms,
            'pendingTransactions' => $pendingTransactions,
            'pendingInstallments' => $pendingInstalments
        ]);
    }

    public function approveOrRejectManualPayment(Request $request, Transaction $transaction)
    {
        $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        try {
            DB::transaction(function () use ($transaction, $request) {
                if ($request->action === 'approve') {
                    UpdateStatusTransactionService::makeSuccess($transaction, Auth::id());
                } else {
                    UpdateStatusTransactionService::makeFailed($transaction, Auth::id());
                }
            });

            return back()->with('success', $message ?? 'Berhasil memperbarui status transaksi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses transaksi.' . $e->getMessage());
        }
    }

    public function getGymDetails(MasterGym $gym)
    {
        return response()->json([
            'memberships' => $gym->memberships()->with(['membershipPromos' => function ($q) {
                $q->whereNull('unique_code');
            }])->get(),
            'pt_packages' => $gym->ptPackages()->with(['ptPackagePromos' => function ($q) {
                $q->whereNull('unique_code');
            }])->get(),
        ]);
    }

    public function checkPromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'id' => 'required|integer',
            'type' => 'required|in:membership,pt'
        ]);

        if ($request->type === 'membership') {
            $promo = MembershipPromo::where('membership_id', $request->id)
                ->where('unique_code', $request->code)
                ->first();
        } else {
            $promo = PtPackagePromo::where('pt_package_id', $request->id)
                ->where('unique_code', $request->code)
                ->first();
        }

        if (!$promo) return response()->json(['message' => 'Kode promo tidak valid'], 404);

        return response()->json($promo);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8',
            'nik' => 'nullable|string',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:40720|mimes:jpeg,png,jpg,gif',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $user, $request) {
            $userData = [
                'name' => $validated['name'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);

            $detailData = [
                'nik' => $validated['nik'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'phone_number' => $validated['phone_number'],
                'emergency_name' => $validated['emergency_name'] ?? null,
                'emergency_phone' => $validated['emergency_phone'] ?? null,
                'emergency_relation' => $validated['emergency_relation'] ?? null,
            ];

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');

                $tempFileName = S3Helper::storeFileTemp($file);
                $s3Path = S3Helper::storeFileToS3("user-profile", $tempFileName);
                $url = S3Helper::getUrlFileS3("user-profile", $tempFileName);

                S3Helper::removeFileTemp($tempFileName);

                $user->userDetail()->update([
                    'photo' => $url,
                ]);
                $detailData['photo'] = $url;
            }

            $user->userDetail()->updateOrCreate(
                ['user_id' => $user->id],
                $detailData
            );
        });

        return redirect()->route('management.user.index')->with('success', 'User updated successfully.');
    }

    public function generateInstallment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                if (!User::role('User')->where('id', $value)->exists()) {
                    $fail('User tidak ditemukan atau tidak memiliki akses sebagai member.');
                }
            }],
            'user_pt_package_installment_id' =>  ['required', 'exists:user_pt_package_instalments,id'],
            'payment_method' => ['required', 'in:manual,va,qris']
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $userPtPackageInstallment = UserPtPackageInstalment::findOrFail($validated['user_pt_package_installment_id']);

        $res = PaymentService::processInstallment($userPtPackageInstallment, $validated['payment_method'], $validated['user_id']);

        if (in_array($validated['payment_method'], ['va', 'qris'])) {
            $user = User::find($validated['user_id']);

            $transaction = \App\Models\Transaction::find($res['transaction_id']);

            $midtransService = new \App\Http\Services\MidtransService();
            $snapToken = $midtransService->getSnapToken($transaction, $user);
            $transaction->snap_token = $snapToken;
            $transaction->save();

            $res['snap_token'] = $snapToken;
        }

        return response()->json($res);
    }

    public function generatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                if (!User::role('User')->where('id', $value)->exists()) {
                    $fail('User tidak ditemukan atau tidak memiliki akses sebagai member.');
                }
            }],
            'gym_id' => 'required|exists:master_gyms,id',
            'transaction_type' => ['required', Rule::in(['membership', 'pt'])],
            'type_id' => 'required|integer',
            'payment_method' => ['required', Rule::in(['manual', 'va', 'qris'])],
            'payment_type' => [
                'nullable',
                Rule::requiredIf($request->input('transaction_type') === 'pt'),
                Rule::in(['full_payment', 'dp_payment'])
            ],
            'dp_percent' => [
                'nullable',
                Rule::requiredIf($request->input('payment_type') === 'dp_payment'),
                'numeric',
                'min:0',
                'max:100'
            ],
            'installment_pt_id' => ['nullable', 'exists:user_pt_package_instalments,id'],
            'promo_code' => 'nullable|string',
            // 'trainer_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $res = PaymentService::processPayment($validated);

        if (in_array($validated['payment_method'], ['va', 'qris'])) {
            $user = User::find($validated['user_id']);

            $transaction = \App\Models\Transaction::find($res['transaction_id']);

            $midtransService = new \App\Http\Services\MidtransService();
            $snapToken = $midtransService->getSnapToken($transaction, $user);
            $transaction->snap_token = $snapToken;
            $transaction->save();

            $res['snap_token'] = $snapToken;
        }

        return response()->json($res);
    }


    public function ocr(Request $request)
    {
        $request->validate(['ktp_image' => 'required|image']);

        return response()->json([
            'message' => 'OCR logic not implemented yet',
            // 'nik' => '123456...', 
        ], 501);
    }
}
