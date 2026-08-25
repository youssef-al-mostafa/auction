# Frontend Structure

## Stack

| Concern | Choice |
|---------|--------|
| Bridge | Inertia v3 |
| UI | React 19 + TypeScript |
| Styling | Tailwind 4 |
| Components | shadcn (Radix primitives) |
| Routing helpers | Laravel Wayfinder |
| Toasts | Sonner |

## Component Organisation

```
resources/js/components/
├── ui/       shadcn primitives — generated, not hand-edited
├── core/     our generic, domain-agnostic reusables
└── app/      domain components tied to auction concepts
```

| Folder | Holds | Example |
|--------|-------|---------|
| `ui/` | Buttons, inputs, tables, dialogs | `button.tsx`, `table.tsx` |
| `core/` | Reusables shadcn doesn't provide | `pagination.tsx` |
| `app/` | Auction domain pieces | `product-form.tsx` |

Files the starter kit shipped (`heading.tsx`, `nav-main.tsx`, `app-sidebar.tsx`) remain at the root
of `components/`. New components go into the folders above.

Filenames are kebab-case. Components are declared as arrow-function consts:

```tsx
export const Pagination = ({ links }: { links: PaginationLink[] }) => { ... };
```

Page components follow the same rule and export at the bottom:

```tsx
const ProductsIndex = ({ products }: ProductsIndexProps) => ( ... );

ProductsIndex.layout = { breadcrumbs: [...] };

export default ProductsIndex;
```

## Supporting Directories

```
resources/js/
├── config/     static configuration data (navigation.ts)
├── hooks/      shared behaviour (use-nav-items.ts, use-flash-toast.ts)
├── types/      shared types, re-exported from types/index.ts
├── actions/    Wayfinder — generated from controllers
└── routes/     Wayfinder — generated from named routes
```

`actions/` and `routes/` are **generated**. Regenerate after changing routes:

```bash
php artisan wayfinder:generate
```

## Types

`resources/js/types/index.ts` re-exports every type module, so imports stay flat:

```ts
import type { PaginationProps, Product } from '@/types';
```

| Module | Contains |
|--------|----------|
| `auth.ts` | `User`, `Auth`, `Permission` |
| `models.ts` | Domain models mirroring the Eloquent models |
| `pagination.ts` | `PaginationLink`, `PaginationProps<T>` |
| `navigation.ts` | `NavItem`, `BreadcrumbItem` |
| `ui.ts` | Layout props, `FlashToast` |

`Permission` is a union of the real permission names, so a typo in a nav config entry is a compile
error rather than a link that silently never appears.

## Navigation

Navigation is data, not markup. `config/navigation.ts` declares the items and the permission each
requires; `use-nav-items.ts` filters them; `app-sidebar.tsx` only renders.

```ts
export const mainNavItems: NavConfigItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    {
        title: 'Products',
        href: productsIndex(),
        icon: Package,
        permission: 'manage-products',
    },
];
```

Adding an admin section is one entry in this file.

## Routing from the Frontend

URLs are never hardcoded. Wayfinder gives typed functions generated from the Laravel routes:

```tsx
import { create, edit } from '@/routes/admin/products';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';

<Link href={create()} />
<Link href={edit(product.id)} />
<Form {...ProductController.store.form()} />
```

Renaming a route and regenerating turns every stale usage into a TypeScript error.

## Forms

Forms use Inertia's `<Form>` with the Wayfinder action spread in. Passing the form-data shape as a
generic types the `errors` object:

```tsx
<Form<ProductFormData> {...action} className="max-w-2xl space-y-6">
    {({ processing, errors }) => (
        <>
            <Input id="name" name="name" defaultValue={product?.name} required />
            <InputError message={errors.name} />
            <Button disabled={processing}>{submitLabel}</Button>
        </>
    )}
</Form>
```

Create and edit share one component; only the action passed in differs.

## Flash Messages

The starter kit already wires flash toasts end to end. `useFlashToast()` is called inside
`<Toaster>`, which is mounted in `app.tsx` — pages need no toast code at all.

Server side:

```php
Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

return to_route('admin.products.index');
```

Valid types are `success`, `info`, `warning`, `error`.

## Checks

```bash
npm run types:check     # tsc --noEmit
npm run lint:check      # eslint
npm run format          # prettier --write
```

On the PHP side, `vendor/bin/pint --dirty` and `vendor/bin/phpstan analyse` (Larastan, level 7).
Level 7 requires generic annotations on relations and query scopes:

```php
/**
 * @return HasMany<AuctionItem, $this>
 */
public function auctionItems(): HasMany
```
