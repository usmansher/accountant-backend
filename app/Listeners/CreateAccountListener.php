<?php

namespace App\Listeners;

use App\Events\AccountCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateAccountListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AccountCreated  $event
     * @return void
     */
    public function handle(AccountCreated $event)
    {

        $account = $event->account;


        try {
            // Create the tenant database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$account->db_database}`");

            // Set the tenant database connection
            config(['database.connections.account' => [
                'driver' => 'mysql',
                'host' => $account->db_host,
                'port' => $account->db_port,
                'database' => $account->db_database,
                'username' => $account->db_login,
                'password' => $account->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]]);

            // Run tenant migrations
            Artisan::call('migrate', [
                '--database' => 'account',
                '--path' => '/database/migrations/tenant',
                '--force' => true,
            ]);
        } catch (\Throwable $th) {
            // Log the error
            Log::error('Tenant database creation failed', ['error' => $th->getMessage()]);
            // Re-throw the exception to allow the transaction to roll back
            throw $th;
        }
    }
}
