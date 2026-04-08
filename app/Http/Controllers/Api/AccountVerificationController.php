<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\WhatsappBlastService;
use App\Jobs\SendWhatsappBlast;
use App\Models\User;
use App\Models\WABlastTemplate;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AccountVerificationController extends Controller
{
    protected $waService;

    public function __construct(WhatsappBlastService $waService)
    {
        $this->waService = $waService;
    }

    public function sendVerification(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Akun sudah terverifikasi'
            ], 400);
        }

        // generate signed URL (valid 60 menit)
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        try {
            $waBlastTemplate = WABlastTemplate::where('template_name', 'ACCOUNT_VERIFICATION')->firstOrFail();
            SendWhatsappBlast::dispatch(
                $user->userDetail->phone_number,
                $waBlastTemplate->template_id,
                [
                    '{user}' => $user->name,
                    '{app_name}' => config('app.name'),
                    '{verification_link}' => $verificationUrl,
                ]
            );

            return response()->json([
                'message' => 'Link verifikasi berhasil dikirim via WhatsApp'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal kirim link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        // cek signature (WAJIB)
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Link tidak valid atau expired'
            ], 400);
        }

        $user = \App\Models\User::findOrFail($request->id);

        // validasi hash
        if (!hash_equals((string) $request->hash, sha1($user->email))) {
            return response()->json([
                'message' => 'Hash tidak valid'
            ], 400);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Akun sudah terverifikasi'
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        event(new Verified($user));

        return response()->json([
            'message' => 'Akun berhasil diverifikasi'
        ]);
    }
}
