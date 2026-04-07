<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\UserGym;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'unique_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->unique_id)) {
                $user->unique_id = self::generateUniqueUuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    private static function generateUniqueUuid()
    {
        do {
            $uuid = (string) \Illuminate\Support\Str::uuid();
        } while (static::where('unique_id', $uuid)->exists());

        return $uuid;
    }

    // Relations
    public function userDetail()
    {
        return $this->hasOne(UserDetail::class);
    }

    public function gymPts()
    {
        return $this->hasMany(GymPt::class, 'pt_id');
    }

    public function ptProfile()
    {
        return $this->hasOne(PtProfile::class, 'pt_id');
    }

    public function ptDescriptions()
    {
        return $this->hasMany(PtDescription::class, 'pt_id');
    }

    public function ptImgUrls()
    {
        return $this->hasMany(PtImgUrl::class, 'pt_id');
    }

    public function gyms()
    {
        return $this->belongsToMany(MasterGym::class, 'gym_admins', 'admin_id', 'gym_id');
    }

    public function userPtPackages()
    {
        return $this->hasMany(UserPtPackage::class, 'pt_id');
    }

    public function userPtPackageMembers()
    {
        return $this->hasMany(UserPtPackageMember::class, 'user_id');
    }

    public function userPtPackageDetails()
    {
        return $this->hasMany(UserPtPackageDetail::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'confirmed_by');
    }

    public function userGyms()
    {
        return $this->hasMany(UserGym::class, 'user_id');
    }
}
