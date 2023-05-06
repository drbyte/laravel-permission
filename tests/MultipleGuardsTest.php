<?php

namespace Spatie\Permission\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Tests\TestModels\Manager;

class MultipleGuardsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('auth.guards', [
            'web' => ['driver' => 'session', 'provider' => 'users'],
            'api' => ['driver' => 'token', 'provider' => 'users'],
            'jwt' => ['driver' => 'token', 'provider' => 'users'],
            'abc' => ['driver' => 'abc'],
        ]);

        $this->setUpRoutes();
    }

    /**
     * Create routes to test authentication with guards.
     */
    public function setUpRoutes(): void
    {
        Route::middleware('auth:api')->get('/check-api-guard-permission', function (Request $request) {
            return [
                'status' => $request->user()->checkPermissionTo('use_api_guard'),
            ];
        });
    }

    /** @test */
    public function it_can_give_a_permission_to_a_model_that_is_used_by_multiple_guards()
    {
        $this->testUser->givePermissionTo(app(Permission::class)::create([
            'name' => 'do_this',
            'guard_name' => 'web',
        ]));

        $this->testUser->givePermissionTo(app(Permission::class)::create([
            'name' => 'do_that',
            'guard_name' => 'api',
        ]));

        $this->assertTrue($this->testUser->checkPermissionTo('do_this', 'web'));
        $this->assertTrue($this->testUser->checkPermissionTo('do_that', 'api'));
        $this->assertFalse($this->testUser->checkPermissionTo('do_that', 'web'));
    }

    /** @test */
    public function it_can_honour_guardName_function_on_model_for_overriding_guard_name_property()
    {
        $user = Manager::create(['email' => 'manager@test.com']);
        $user->givePermissionTo(app(Permission::class)::create([
            'name' => 'do_jwt',
            'guard_name' => 'jwt',
        ]));

        // Manager test user has the guardName override method, which returns 'jwt'
        $this->assertTrue($user->checkPermissionTo('do_jwt', 'jwt'));
        $this->assertTrue($user->hasPermissionTo('do_jwt', 'jwt'));

        // Manager test user has the $guard_name property set to 'web'
        $this->assertFalse($user->checkPermissionTo('do_jwt', 'web'));
    }

    /** @test */
    public function it_can_authorize_when_check_guard_names_flag_is_disabled()
    {
        config('permission.check_guard_names', false);

        $this->testUser->givePermissionTo(app(Permission::class)::create([
            'name' => 'do_general',
        ]));

        $this->testUser->givePermissionTo(app(Permission::class)::create([
            'name' => 'do_web',
            'guard_name' => 'web',
        ]));

        $this->testUser->givePermissionTo(app(Permission::class)::create([
            'name' => 'do_api',
            'guard_name' => 'api',
        ]));

        $this->assertTrue($this->testUser->checkPermissionTo('do_general'));
        $this->assertTrue($this->testUser->checkPermissionTo('do_web'));
        // web guard works cuz web is built-in default
        $this->assertTrue($this->testUser->checkPermissionTo('do_web', 'web'));
        // api guard works here because we're asking for the guard that the permission was created for
        $this->assertTrue($this->testUser->checkPermissionTo('do_api', 'api'));
        // this works because the config guard check ignores the guard
        $this->assertTrue($this->testUser->checkPermissionTo('do_api'));
    }
}
