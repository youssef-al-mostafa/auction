# Role-Based Access Control

## Roles

The application uses Spatie Permissions with two roles:

1. **USER**
   - Assigned automatically on registration
   - Browses auctions, places bids, chats with the admin
   - No access to admin areas

2. **ADMIN**
   - Creates and manages products and auctions
   - Controls the live auction: launching items, countdowns, closing bids
   - Seeded, never self-registered

## Routes Gate on Permissions, Not Roles

Routes check **what a user may do**, never **who they are**. Adding a third privileged role later
is a matter of granting it a permission — no route file changes.

| Permission | Covers |
|------------|--------|
| `manage-products` | Product CRUD |
| `manage-auctions` | Auction CRUD and attaching items to an auction |

## Permission Matrix

| Feature | USER | ADMIN |
|---------|------|-------|
| Browse auctions | ✅ | ✅ |
| Place bids | ✅ | ✅ |
| Manage products | ❌ | ✅ |
| Manage auctions | ❌ | ✅ |
| Add items to an auction | ❌ | ✅ |

## Implementation Details

### Enums as the Source of Truth

Role and permission names are never written as loose strings:

```php
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;

RolesEnum::ADMIN->value;              // 'admin'
PermissionsEnum::MANAGE_PRODUCTS;     // 'manage-products'
PermissionsEnum::values();            // ['manage-products', 'manage-auctions']
```

### Middleware Aliases Must Be Registered Manually

Spatie ships `PermissionMiddleware`, `RoleMiddleware` and `RoleOrPermissionMiddleware` but does
**not** register their aliases. Without this block, `permission:manage-products` fails with
"Target class [permission] does not exist":

```php
// bootstrap/app.php
$middleware->alias([
    'role' => RoleMiddleware::class,
    'permission' => PermissionMiddleware::class,
    'role_or_permission' => RoleOrPermissionMiddleware::class,
]);
```

### Route Protection

```php
Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('permission:'.PermissionsEnum::MANAGE_PRODUCTS->value)
            ->resource('products', ProductController::class)
            ->except('show');
    });
```

Each admin resource is gated on its **own specific** permission. Where a shared landing page needs
"any admin capability", Spatie's pipe syntax is an OR:

```php
'permission:'.PermissionsEnum::MANAGE_PRODUCTS->value.'|'.PermissionsEnum::MANAGE_AUCTIONS->value
```

Requiring *both* on a shared entry point would defeat the design — a role granted only
`manage-products` would be blocked at the door, collapsing the two permissions into a single
"is admin" flag.

### Role Assignment on Registration

Fortify handles registration in this starter kit, so `CreateNewUser` is the single place new
accounts pass through:

```php
$user = User::create([...]);

$user->assignRole(RolesEnum::USER->value);

return $user;
```

### Permissions in the Frontend

`HandleInertiaRequests` shares the current user's permissions with every page:

```php
'auth' => [
    'user' => $request->user(),
    'permissions' => $request->user()?->getAllPermissions()->pluck('name')->values() ?? [],
],
```

Navigation is data in `config/navigation.ts`, where an item may declare a required permission, and
`useNavItems()` filters the list:

```ts
export const useNavItems = (): NavItem[] => {
    const { auth } = usePage().props;

    return mainNavItems.filter(
        (item) => !item.permission || auth.permissions.includes(item.permission),
    );
};
```

Hiding a link is presentation only — the route middleware is what actually enforces access.

## Seeded Accounts

`php artisan db:seed` creates the following. Every account uses the password `password`.

| Email | Role |
|-------|------|
| `admin@auction.test` | admin |
| `sara@auction.test` | user |
| `omar@auction.test` | user |
| `lina@auction.test` | user |

Seeders run in order — `RolePermissionSeeder`, `AdminUserSeeder`, `UserSeeder` — because roles must
exist before they can be assigned. All three use `firstOrCreate`, so re-seeding is safe.
