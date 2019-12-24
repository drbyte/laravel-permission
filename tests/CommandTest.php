<?php

namespace Spatie\Permission\Test;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;

class CommandTest extends TestCase
{
    /** @test */
    public function it_can_create_a_role()
    {
        Artisan::call('permission:create-role', ['name' => 'new-role']);

        $this->assertCount(1, Role::where('name', 'new-role')->get());
        $this->assertCount(0, Role::where('name', 'new-role')->first()->permissions);
    }

    /** @test */
    public function it_can_create_a_permission()
    {
        Artisan::call('permission:create-permission', ['name' => 'new-permission']);

        $this->assertCount(1, Permission::where('name', 'new-permission')->get());
    }

    /** @test */
    public function it_can_create_a_role_and_permissions_at_same_time()
    {
        Artisan::call('permission:create-role', [
            'name' => 'new-role',
            'permissions' => 'first permission | second permission',
        ]);

        $role = Role::where('name', 'new-role')->first();

        $this->assertTrue($role->hasPermissionTo('first permission'));
        $this->assertTrue($role->hasPermissionTo('second permission'));
    }

    /** @test */
    public function it_can_create_a_role_without_duplication()
    {
        Artisan::call('permission:create-role', ['name' => 'new-role']);
        Artisan::call('permission:create-role', ['name' => 'new-role']);

        $this->assertCount(1, Role::where('name', 'new-role')->get());
        $this->assertCount(0, Role::where('name', 'new-role')->first()->permissions);
    }

    /** @test */
    public function it_can_create_a_permission_without_duplication()
    {
        $this->expectException(PermissionAlreadyExists::class);

        Artisan::call('permission:create-permission', ['name' => 'new-permission']);
        Artisan::call('permission:create-permission', ['name' => 'new-permission']);

        $this->assertCount(1, Permission::where('name', 'new-permission')->get());
    }

    /** @test */
    public function it_can_show_permission_tables()
    {
        // ignore admin role for now
        Role::findByName('testAdminRole')->delete();

        Artisan::call('permission:show');

        $output = Artisan::output();

        // |               | testRole | testRole2 |
        $this->assertRegExp('/\|\s+\|\s+testRole\s+\|\s+testRole2\s+\|/', $output);

        // | edit-articles |  ·       |  ·        |
        $this->assertRegExp('/\|\s+edit-articles\s+\|\s+·\s+\|\s+·\s+\|/', $output);

        Role::findByName('testRole')->givePermissionTo('edit-articles');
        $this->reloadPermissions();

        Artisan::call('permission:show');

        $output = Artisan::output();

        // | edit-articles |  ·       |  ·        |
        $this->assertRegExp('/\|\s+edit-articles\s+\|\s+✔\s+\|\s+·\s+\|/', $output);
    }
}
