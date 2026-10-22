# Lead workflow builder: Laravel + Blade + LogicFlow

## Install
1. Copy these folders into your Laravel app (merge with existing `app`, `config`, `database`, `resources`, `routes`).
2. `npm i @logicflow/core`
3. `vite.config.js`: add `'resources/js/workflow/builder.js'` to the laravel-vite-plugin `input` array.
4. `routes/web.php`: add `require __DIR__.'/workflows.php';`
5. `AppServiceProvider::boot()`:
   ```php
   \App\Models\Lead::observe(\App\Observers\LeadObserver::class);
   \Illuminate\Support\Facades\Gate::define('manage-workflows', fn ($user) => $user->is_admin); // your rule
   ```
6. `php artisan migrate`
7. Run a worker: `php artisan queue:work --queue=workflows,default`
8. Create one: `Workflow::create(['name' => 'Lead lifecycle'])`, then open `/workflows/1`.

The Blade view extends `layouts.app` and expects `@yield('content')` and `@stack('scripts')`. If your layout differs, change the first and last lines of `builder.blade.php`. Set `--wf-offset` (your navbar height) on `.wf-app` if the page scrolls.

## Assumptions to adjust
- `Lead` columns: `status, stage, score, owner_id, converted_at, archived_at, purge_after, disqualified_reason, company, name, email`; relation `activities()`.
- `Account`, `Contact`, `Opportunity` models each with a `lead_id` column.
- `users.is_sales_rep` is used for round-robin assignment.

## How it works
- Browser autosaves the draft (1.2 s debounce) with `revision` as an optimistic lock (409 if another tab saved first).
- **Publish** validates on the server, then freezes a copy in `published_graph`. Editing a draft never affects running workflows.
- A new `Lead` fires `RunWorkflowJob` (after commit). One run per workflow, lead and published version; retries are safe because actions are idempotent.
- **Simulate** is a server dry run: it calls the same engine with `$dry = true`, so what you see is what will run.
- To add a node type: one action class, one line in `config/workflows.php`, one entry in `nodes.js` (TYPES, SCHEMA, DEFAULT_CONFIG, metaOf).
