<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class AddDefaultPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-default-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $system = getVar('system');
        $bar = $this->output->createProgressBar(1);
        $permissions = Permission::pluck('name')->toArray();

        foreach ($system['permissions'] as $permission) {
            if (!in_array($permission, $permissions)) {
                Permission::create([
                    'name' => strtolower($permission),
                    'guard_name' => 'api'
                ]);
            }else{
                Permission::where('name', strtolower($permission))
                    ->update(['guard_name' => 'api']);
            }
        }

        $role = Role::whereName('admin')->first();
        $role->syncPermissions($system['permissions']);

        $role = Role::whereName('accountant')->first();
        $role->syncPermissions($system['accountant_permissions']);


        $bar->finish();
        //
    }
}
