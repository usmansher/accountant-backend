<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class BackupTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-tenants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Backup the main (landlord) database
        $this->backupMainDatabase();

        // Iterate over each tenant and back up their database
        Account::all()->each(function ($account) {
            $this->backupTenantDatabase($account);
        });

        $this->info('All tenant backups completed.');
    }

    /**
     * Backup the main (landlord) database.
     */
    protected function backupMainDatabase()
    {
        $this->info("Starting backup for main database");

        try {
            Artisan::call('backup:run', [
                '--only-db' => true,
            ]);
            $this->info("Backup completed for main database");
        } catch (\Exception $e) {
            $this->error("Backup failed for main database. Error: " . $e->getMessage());
        }
        sleep(1); // Wait for a few seconds to avoid potential conflicts with tenant backups
    }

    /**
     * Backup a tenant's database.
     *
     * @param Account $account
     */
    protected function backupTenantDatabase(Account $account)
    {
        $this->info("Starting backup for tenant: {$account->name}");

        // Set the tenant as the current context
        tenancy()->initialize($account);

        // Retrieve tenant database connection details
        $tenantDatabaseName = $account->tenancy_db_name; // Assuming 'database' is the attribute storing the tenant's database name

        // Update the tenant database connection configuration
        Config::set('database.connections.mysql.database', $tenantDatabaseName);
        DB::purge('mysql'); // Clear the tenant database connection
        DB::reconnect('mysql'); // Reconnect to the tenant database

        // Update the backup configuration to target the tenant's database
        Config::set('backup.backup.source.databases', ['tenant']);
        Config::set('backup.backup.name', 'backup-' . $account->name);
        Config::set('backup.backup.destination.disks', ['local']); // Modify as needed

        // Run the backup
        try {
            Artisan::call('backup:run', [
                '--only-db' => true,
            ]);
            $this->info("Backup completed for tenant: {$account->name}");
        } catch (\Exception $e) {
            $this->error("Backup failed for tenant: {$account->name}. Error: " . $e->getMessage());
        }
    }
}
