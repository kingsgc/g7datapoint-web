<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The initial admin credentials come from the environment so no working
     * password is committed to source:
     *
     *   ADMIN_INITIAL_PASSWORD   hashed and stored (bcrypt)
     *   ADMIN_INITIAL_PIN        stored as-is
     *
     * The pin is deliberately NOT hashed. The application compares it in plain
     * text all over the codebase (API\AuthController:161, APP\Auth:626/1062,
     * API\SecureController:128), and the column is only varchar(20). Hashing it
     * here would break transaction-PIN authentication app-wide.
     *
     * Each field is skipped when its variable is unset or blank, so an unset
     * value never overwrites a good password with an empty one. Anything that
     * is skipped is reported to the console rather than failing silently.
     */
    public function up(): void
    {
        $affected = DB::table('user')->where('username', 'TechBoy')->count();

        if ($affected === 0) {
            $this->warn('No user matched username "TechBoy"; admin credentials were not changed.');

            return;
        }

        $updates = [
            'name' => 'abdullahisdahiru',
            'username' => 'sarkidah',
            'email' => 'sakidahiru@gmail.com',
            'phone' => '08123456789',
        ];

        $password = config('admin.initial_password');
        $pin = config('admin.initial_pin');

        if (filled($password)) {
            $updates['password'] = Hash::make($password);
        } else {
            $this->warn('ADMIN_INITIAL_PASSWORD is not set; the admin password was left unchanged.');
        }

        if (filled($pin)) {
            if (mb_strlen($pin) > 20) {
                $this->warn('ADMIN_INITIAL_PIN is longer than the pin column (20 chars); the pin was left unchanged.');
            } else {
                $updates['pin'] = $pin;
            }
        } else {
            $this->warn('ADMIN_INITIAL_PIN is not set; the admin pin was left unchanged.');
        }

        DB::table('user')->where('username', 'TechBoy')->update($updates);

        $this->info(sprintf(
            'Admin user updated (%d row%s). Password %s, pin %s.',
            $affected,
            $affected === 1 ? '' : 's',
            array_key_exists('password', $updates) ? 'set from ADMIN_INITIAL_PASSWORD' : 'unchanged',
            array_key_exists('pin', $updates) ? 'set from ADMIN_INITIAL_PIN' : 'unchanged'
        ));
    }

    /**
     * Reverse the migrations.
     *
     * Left as a no-op on purpose: rolling back would have to either restore the
     * old hardcoded password (defeating the point of this migration) or blank
     * the account out and lock the administrator out of the panel.
     */
    public function down(): void
    {
        //
    }

    /**
     * $command is only set while the migrator is driving this class, so it is
     * probed with isset() rather than read directly.
     */
    private function warn(string $message): void
    {
        if (isset($this->command)) {
            $this->command->warn($message);
        }
    }

    private function info(string $message): void
    {
        if (isset($this->command)) {
            $this->command->info($message);
        }
    }
};