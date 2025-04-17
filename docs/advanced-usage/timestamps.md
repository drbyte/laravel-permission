---
title: Timestamps
weight: 10
---

## Excluding Timestamps from JSON

If you want to exclude timestamps from JSON output of role/permission pivots, you can extend the Role and Permission models into your own App and mark the pivot as hidden:

```php
    protected $hidden = ['pivot'];
 ```

Or if you don't want to extend this package's models, another approach could be to add this to your `AppServiceProvider`'s `boot()` method (adapt for Permission model as well, if needed):

```php
\Spatie\Permission\Models\Role::retrieved(function ($role) {
     $role->makeHidden(['pivot']);
});
```

## Adding Timestamps to Pivots

If you want to add timestamps to your pivot tables, you can do it with a few steps:
 - update the tables by calling `$table->timestamps();` in a migration
 - extend the `Permission` and `Role` models and add `->withTimestamps();` to the `BelongsToMany` relationshps for `roles()` and `permissions()`
 - update your `User` models (wherever you use the `HasRoles` or `HasPermissions` traits) by adding `->withTimestamps();` to the `BelongsToMany` relationships for `roles()` and `permissions()`
