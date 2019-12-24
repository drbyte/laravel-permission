<?php

namespace Spatie\Permission\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Show extends Command
{
    protected $signature = 'permission:show
            {style? : The display style (default|borderless|compact|box)}';

    protected $description = 'Show a table of roles and permissions';

    public function handle()
    {
        $style = $this->argument('style') ?? 'default';

        $roles = Role::orderBy('name')->get()->mapWithKeys(function (Role $role) {
            return [$role->name => $role->permissions->pluck('name')];
        });

        $permissions = Permission::orderBy('name')->pluck('name');

        $body = $permissions->map(function ($permission) use ($roles) {
            return $roles->map(function (Collection $role_permissions) use ($permission) {
                return $role_permissions->contains($permission) ? ' ✔' : ' ·';
            })->prepend($permission);
        });

        $this->table(
            $roles->keys()->prepend('')->toArray(),
            $body->toArray(),
            $style
        );
    }
}
