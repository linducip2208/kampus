<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withPivot([
            'university_id', 'campus_id', 'faculty_id', 'study_program_id',
        ]);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function permissions()
    {
        return Permission::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $this->roles()->pluck('roles.id')));
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasRole('super_admin') || $this->permissions()->where('permissions.name', $permission)->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->hasRole('super_admin') || $this->permissions()->whereIn('permissions.name', $permissions)->exists();
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
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
        ];
    }
}
