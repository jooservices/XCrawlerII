<?php

namespace Modules\Core\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthAuthorizeCommand extends Command
{
    protected $signature = 'auth:authorize
                            {--email=admin@xcrawler.local : Root admin email}
                            {--new-password= : New password for root admin}
                            {--password= : Password used for --test-access}
                            {--test-access : Verify provided password against root admin account}';

    protected $description = 'Authorize root admin actions: reset password and test access';

    public function handle(): int
    {
        $email = trim((string) $this->option('email'));
        $newPassword = $this->normalizePassword((string) $this->option('new-password'));
        $passwordToTest = $this->normalizePassword((string) $this->option('password'));
        $shouldTestAccess = (bool) $this->option('test-access');

        if ($newPassword === null && ! $shouldTestAccess) {
            $this->error('Nothing to do. Use --new-password to reset password and/or --test-access to validate access.');

            return self::INVALID;
        }

        $rootAdmin = User::query()
            ->where('email', $email)
            ->whereHas('roles', static function ($query): void {
                $query->where('slug', 'admin');
            })
            ->first();

        if (! $rootAdmin instanceof User) {
            $this->error("Root admin user not found for email '{$email}' with role 'admin'.");

            return self::FAILURE;
        }

        if ($newPassword !== null) {
            $validator = Validator::make(
                ['password' => $newPassword],
                ['password' => ['required', 'string', 'min:8']]
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::INVALID;
            }

            $rootAdmin->forceFill([
                'password' => $newPassword,
            ])->save();

            $this->info("Root admin password updated for {$rootAdmin->email}.");
        }

        if ($shouldTestAccess) {
            $passwordForTest = $passwordToTest ?? $newPassword ?? $this->promptPasswordForTest();

            if ($passwordForTest === null) {
                return self::INVALID;
            }

            if (! Hash::check($passwordForTest, (string) $rootAdmin->getAuthPassword())) {
                $this->error('Access test failed: password does not match the root admin account.');

                return self::FAILURE;
            }

            $this->info("Access test passed for {$rootAdmin->email}.");
        }

        return self::SUCCESS;
    }

    private function normalizePassword(string $password): ?string
    {
        $trimmed = trim($password);

        return $trimmed === '' ? null : $trimmed;
    }

    private function promptPasswordForTest(): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->error('Password is required for --test-access in non-interactive mode. Provide --password=...');

            return null;
        }

        $password = (string) $this->secret('Password to test access');

        return $this->normalizePassword($password);
    }
}