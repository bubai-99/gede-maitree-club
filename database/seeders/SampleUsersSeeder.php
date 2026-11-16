<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SAMPLE sign-ins: one committee admin (the sample secretary) and one member
 * (the sample family MC-0104). Emails and passwords come from SEED_* in .env;
 * a blank password is generated and printed once in the console.
 */
class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->user(
            email: (string) config('sample.admin_email'),
            password: config('sample.admin_password'),
            attributes: ['name' => 'Rajib Banerjee', 'phone' => '9830012345', 'is_committee' => true],
            label: 'Sample committee admin',
        );
        $admin->syncRoles([Role::Admin->value]);

        $family = Family::query()->where('family_no', 'MC-0104')->first();

        $member = $this->user(
            email: (string) config('sample.member_email'),
            password: config('sample.member_password'),
            attributes: ['name' => 'Ananya Mukherjee', 'phone' => $family !== null ? $family->phone : '9831734917', 'family_id' => $family?->id],
            label: 'Sample member',
        );
        $member->syncRoles([Role::Member->value]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(string $email, mixed $password, array $attributes, string $label): User
    {
        $existing = User::query()->where('email', $email)->first();
        $plain = is_string($password) && $password !== '' ? $password : null;

        if ($existing !== null && $plain === null) {
            $existing->update($attributes);

            return $existing;
        }

        $printed = $plain === null;
        $plain ??= Str::password(16, symbols: false);

        $user = User::query()->updateOrCreate(['email' => $email], [
            ...$attributes,
            'password' => $plain,
            'email_verified_at' => now(),
        ]);

        if ($printed) {
            $this->command?->warn("{$label}: {$email} / {$plain}  (shown once; set SEED_* in .env to choose your own)");
        }

        return $user;
    }
}
