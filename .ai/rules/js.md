---
paths:
  - 'resources/js/**'
---

# Js

## Always regenerate Wayfinder with --with-form
Run `php artisan wayfinder:generate --with-form`, never the bare command.

`vite.config.ts` sets `wayfinder({ formVariants: true })`, so pages call `.form()` on route helpers (e.g. `ProductController.store.form()`). Regenerating without `--with-form` strips those variants and breaks ~18 files across auth, settings, and admin with `Property 'form' does not exist`. The failure looks unrelated to whatever you were working on.
