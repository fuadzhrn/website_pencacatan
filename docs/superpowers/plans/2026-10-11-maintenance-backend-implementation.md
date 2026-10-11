# Maintenance Backend Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the normalized maintenance database, Eloquent domain model, deterministic seed data, role-aware session authentication, and route middleware for the X-Ray preventive maintenance application.

**Architecture:** Extend the existing Laravel 13 authentication foundation instead of adding another auth package. Store domain states as controlled strings, preserve historical transactions with deliberate foreign-key actions, and enforce the approved role matrix at route boundaries through one variadic role middleware.

**Tech Stack:** PHP 8.3, Laravel 13.17, Eloquent ORM, Laravel session authentication, MySQL production database, PHPUnit 12, Laravel Pint.

**Spec:** `docs/superpowers/specs/2026-10-11-maintenance-backend-design.md`

## Global Constraints

- Do not add or update Composer or npm dependencies.
- Use `php artisan make:* --no-interaction` for Laravel-generated files, then edit them with `apply_patch`.
- Do not change desktop or mobile styling, markup, or JavaScript.
- Do not create CRUD endpoints, report queries, exports, uploads, or schedule-generation services.
- Do not create `tanggal_1` through `tanggal_31` columns; daily report cells are a presentation concern.
- Keep existing auth route names and the current login Blade contract intact.
- Preserve unrelated and pre-existing uncommitted working-tree changes.
- Use test-first development: add one failing behavior test, observe the expected failure, then implement the minimum production change.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes.

## Review Focus

- A second check for the same schedule must fail at the database boundary; Task 1 tests the unique schedule/check invariant.
- An inactive account with a correct password must remain unauthenticated; Task 4 tests the login status constraint.
- Deleting an optional assignee or reviewer must preserve historical rows and null the optional key; Tasks 1 and 2 test the deletion behavior and relationship result.
- A logged-in user must not reach a route outside the approved role matrix, including by entering the URL directly; Task 5 tests the full role/area matrix.
- Re-running seeders must not duplicate users, machines, categories, schedules, checks, details, or findings; Task 3 runs the seeders twice and asserts stable counts.

---

### Task 1: Normalized Maintenance Schema

**Files:**
- Create via Artisan: `database/migrations/<timestamp>_add_phone_and_status_to_users_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_machines_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_maintenance_categories_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_maintenance_items_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_maintenance_schedules_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_maintenance_checks_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_maintenance_check_details_table.php`
- Create via Artisan: `database/migrations/<timestamp>_create_maintenance_findings_table.php`
- Create: `tests/Feature/Database/MaintenanceSchemaTest.php`

**Interfaces:**
- Consumes: Existing `users.id` and `users.role` from the base migrations.
- Produces: Eight normalized domain tables and the user `phone`/`status` fields required by all later tasks.

- [ ] **Step 1: Generate the schema test and write the first failing cases**

Run `php artisan make:test --phpunit Database/MaintenanceSchemaTest --no-interaction`. Add tests named:

- `test_maintenance_schema_contains_the_required_columns_without_day_columns`
- `test_a_schedule_can_have_only_one_maintenance_check`
- `test_a_check_can_contain_an_item_only_once`
- `test_deleting_an_optional_assignee_preserves_the_schedule`

Assert every required column with `Schema::hasColumns()`, assert that no table column matches `tanggal_1` through `tanggal_31`, exercise both unique constraints with real inserts, and verify `assigned_user_id` becomes null after deleting the assigned user.

- [ ] **Step 2: Run the schema test and verify the expected red state**

Run: `php artisan test --compact tests/Feature/Database/MaintenanceSchemaTest.php`

Expected: FAIL because the seven maintenance tables and new user fields do not exist.

- [ ] **Step 3: Generate migrations in dependency order**

Run one `php artisan make:migration <name> --no-interaction` command per file in the order listed above. Do not modify the previously applied user-role migration.

- [ ] **Step 4: Implement columns, constraints, defaults, and query-driven indexes**

Use string status fields from the spec. Add the composite indexes `(scheduled_date, status)` and `(status, finding_date)`, unique constraints on `serial_number`, category `name`, `(maintenance_category_id, item_name)`, `maintenance_schedule_id`, and `(maintenance_check_id, maintenance_item_id)`.

Use `restrictOnDelete()` for required historical parents, `nullOnDelete()` for nullable actors/references, and `cascadeOnDelete()` only for check details owned by a check. Add an index to the existing `users.role` column and the new `users.status` column.

- [ ] **Step 5: Run the focused schema test**

Run: `php artisan test --compact tests/Feature/Database/MaintenanceSchemaTest.php`

Expected: PASS with all schema invariants enforced.

- [ ] **Step 6: Commit the schema slice**

Stage only the new migrations and `MaintenanceSchemaTest.php`, then commit with `feat: add maintenance database schema`.

### Task 2: Eloquent Models and Relationships

**Files:**
- Create via Artisan: `app/Models/Machine.php`
- Create via Artisan: `app/Models/MaintenanceCategory.php`
- Create via Artisan: `app/Models/MaintenanceItem.php`
- Create via Artisan: `app/Models/MaintenanceSchedule.php`
- Create via Artisan: `app/Models/MaintenanceCheck.php`
- Create via Artisan: `app/Models/MaintenanceCheckDetail.php`
- Create via Artisan: `app/Models/MaintenanceFinding.php`
- Modify: `app/Models/User.php`
- Create: `tests/Feature/Models/MaintenanceRelationshipsTest.php`

**Interfaces:**
- Consumes: Foreign keys and defaults created in Task 1.
- Produces: Typed relationship methods, explicit mass-assignment fields, and date casts for use by seeders and later backend features.

- [ ] **Step 1: Generate the relationship test and write failing behavior cases**

Run `php artisan make:test --phpunit Models/MaintenanceRelationshipsTest --no-interaction`. Create the minimum records directly through model `create()` calls and test:

- machine schedules/checks/findings
- category items/schedules
- schedule machine/category/assignedUser/check
- check schedule/machine/checkedBy/verifiedBy/details/findings
- check detail item/check/findings
- finding check/checkDetail/machine/item/reportedBy/reviewedBy
- user assigned schedules, performed checks, verified checks, reported findings, and reviewed findings
- date and timestamp attributes are Carbon instances

- [ ] **Step 2: Run the relationship test and verify it fails for missing model classes**

Run: `php artisan test --compact tests/Feature/Models/MaintenanceRelationshipsTest.php`

Expected: FAIL because the domain models do not exist.

- [ ] **Step 3: Generate the seven models**

Run `php artisan make:model <ModelName> --no-interaction` for each model. Do not generate factories, controllers, migrations, policies, or resources from this command.

- [ ] **Step 4: Implement the model contracts**

Follow the existing `User` convention with `#[Fillable([...])]` and a protected `casts(): array` method. Declare concrete `BelongsTo`, `HasMany`, and `HasOne` return types.

Use these exact User relation names: `assignedMaintenanceSchedules`, `performedMaintenanceChecks`, `verifiedMaintenanceChecks`, `reportedMaintenanceFindings`, and `reviewedMaintenanceFindings`. Use `assignedUser`, `checkedBy`, `verifiedBy`, `reportedBy`, and `reviewedBy` on inverse relations.

Add `phone` and `status` to User fillable fields while retaining the password, hidden fields, hashed password cast, and `UserRole` cast.

- [ ] **Step 5: Run relationship and existing authentication tests**

Run: `php artisan test --compact tests/Feature/Models/MaintenanceRelationshipsTest.php tests/Feature/Auth/AuthenticationTest.php`

Expected: PASS; existing authentication behavior remains intact.

- [ ] **Step 6: Commit the model slice**

Stage only models and `MaintenanceRelationshipsTest.php`, then commit with `feat: add maintenance domain relationships`.

### Task 3: Deterministic Seed Data

**Files:**
- Create via Artisan: `database/seeders/UserSeeder.php`
- Create via Artisan: `database/seeders/MaintenanceMasterSeeder.php`
- Create via Artisan: `database/seeders/MaintenanceTransactionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Preserve: `database/seeders/AdminUserSeeder.php`
- Create: `tests/Feature/Seeders/MaintenanceDatabaseSeederTest.php`

**Interfaces:**
- Consumes: Models and relationships from Task 2.
- Produces: Three login accounts and a coherent static maintenance dataset for subsequent UI/backend work.

- [ ] **Step 1: Generate the seeder test and write failing dataset assertions**

Run `php artisan make:test --phpunit Seeders/MaintenanceDatabaseSeederTest --no-interaction`. Freeze time to `2026-10-11 08:00:00`, seed `DatabaseSeeder`, and assert:

- the three specified emails exist with exact roles, active status, and `Hash::check('password', ...)` true
- exactly three machines and six categories exist
- every category has at least one item
- schedules, checks, details, and findings exist and reference valid parents
- open, review, and resolved findings are represented
- calling `DatabaseSeeder` twice leaves all table counts unchanged

- [ ] **Step 2: Run the seeder test and verify it fails**

Run: `php artisan test --compact tests/Feature/Seeders/MaintenanceDatabaseSeederTest.php`

Expected: FAIL because the maintenance seeders do not exist.

- [ ] **Step 3: Generate the three seeders**

Run `php artisan make:seeder UserSeeder --no-interaction`, `MaintenanceMasterSeeder`, and `MaintenanceTransactionSeeder` with the same flag.

- [ ] **Step 4: Implement idempotent seeders**

Use `updateOrCreate()` with stable natural identifiers: email for users, serial number for machines, category name for categories, category plus item name for items, and explicit stable attribute combinations for transaction rows. Use fixed October 2026 dummy schedule/check/finding dates rather than relative dates so rerunning the seeder on another day cannot duplicate transactions. Use model relationships to connect data and the User password cast to hash `password`.

Create three machines, the six required categories, multiple checklist items across all categories, multiple schedules, normal and finding checks, related check details, and findings in all three statuses.

- [ ] **Step 5: Update `DatabaseSeeder` order**

Call `UserSeeder`, `MaintenanceMasterSeeder`, and `MaintenanceTransactionSeeder` in that order. Keep `AdminUserSeeder` available for explicit environment-driven use but remove it from the default dummy seeding path.

- [ ] **Step 6: Run focused seeder tests**

Run: `php artisan test --compact tests/Feature/Seeders/MaintenanceDatabaseSeederTest.php tests/Feature/InitialAdminSeederTest.php`

Expected: PASS for both the new deterministic dataset and the preserved configurable admin seeder.

- [ ] **Step 7: Commit the seeder slice**

Stage only the seeder files and seeder test, then commit with `feat: seed maintenance reference data`.

### Task 4: Role-Aware Authentication

**Files:**
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Modify: `app/UserRole.php`
- Modify: `bootstrap/app.php`
- Modify: `tests/Feature/Auth/AuthenticationTest.php`

**Interfaces:**
- Consumes: User `status` field and `UserRole` cast.
- Produces: Active-account enforcement and `UserRole::dashboardRouteName(): string` as the single source for default destinations.

- [ ] **Step 1: Add failing authentication cases**

Add tests to `AuthenticationTest.php` named:

- `test_admin_is_redirected_to_the_desktop_dashboard_after_login`
- `test_petugas_is_redirected_to_the_mobile_dashboard_after_login`
- `test_supervisor_is_redirected_to_the_desktop_dashboard_after_login`
- `test_inactive_user_cannot_authenticate_with_a_valid_password`
- `test_authenticated_petugas_is_redirected_away_from_login_to_the_mobile_dashboard`

Assert both redirects and authentication/session state. Keep existing invalid-password, validation, throttle, guest redirect, remember-me, and logout coverage.

- [ ] **Step 2: Run authentication tests and verify role/status cases fail**

Run: `php artisan test --compact tests/Feature/Auth/AuthenticationTest.php`

Expected: FAIL because every role currently falls back to the desktop dashboard and status is not included in credentials.

- [ ] **Step 3: Implement active-only login and role fallback routes**

Add `status => 'active'` to the credentials passed to `Auth::attempt()`. Add `UserRole::dashboardRouteName(): string`, returning `mobile.dashboard` for Petugas and `dashboard` for Admin and Supervisor, then pass that route URL to `redirect()->intended()`.

Change `redirectUsersTo` in `bootstrap/app.php` to call the same enum method for an already-authenticated user visiting `/login`. Keep session regeneration and logout invalidation unchanged.

- [ ] **Step 4: Run authentication tests**

Run: `php artisan test --compact tests/Feature/Auth/AuthenticationTest.php`

Expected: PASS for all role redirects, inactive denial, throttling, remember-me, and logout.

- [ ] **Step 5: Commit the authentication slice**

Stage the controller, bootstrap file, and authentication test; commit with `feat: redirect authenticated users by role`.

### Task 5: Role Middleware and Route Protection

**Files:**
- Create via Artisan: `app/Http/Middleware/RoleMiddleware.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Middleware/RoleMiddlewareTest.php`
- Modify: `tests/Feature/MobileNavigationTest.php`
- Modify only if authorization expectations require it: existing page feature tests under `tests/Feature/Mobile*PageTest.php`

**Interfaces:**
- Consumes: `UserRole` values and all existing named desktop/mobile routes.
- Produces: Middleware alias `role` and HTTP 403 enforcement for unauthorized role/route combinations.

- [ ] **Step 1: Generate the middleware test and define the failing permission matrix**

Run `php artisan make:test --phpunit Middleware/RoleMiddlewareTest --no-interaction`. Use data providers to test representative desktop and mobile routes for each role:

- Admin is allowed on every area.
- Petugas is allowed on mobile dashboard, schedules, inspection today, findings, and history; forbidden from machines, checklists, reports, and users.
- Supervisor is allowed on dashboard, findings, history, and reports; forbidden from machines, checklists, schedules, inspection today, and users.
- Guests are redirected to login before role evaluation.

Assert `assertSuccessful()` or the route's expected redirect only for allowed routes, `assertForbidden()` for authenticated unauthorized roles, and `assertRedirectToRoute('login')` for guests.

- [ ] **Step 2: Run middleware tests and verify they fail because routes are auth-only**

Run: `php artisan test --compact tests/Feature/Middleware/RoleMiddlewareTest.php`

Expected: FAIL because unauthorized authenticated roles currently receive the pages.

- [ ] **Step 3: Generate and register `RoleMiddleware`**

Run `php artisan make:middleware RoleMiddleware --no-interaction`. Implement `handle(Request $request, Closure $next, string ...$roles): Response`, compare the authenticated user's `UserRole` value with the allowed strings using strict comparison, and abort with 403 when it does not match.

Register the `role` alias in the existing `withMiddleware()` closure in `bootstrap/app.php` without removing `redirectUsersTo`.

- [ ] **Step 4: Apply middleware to existing route areas**

Protect both desktop and mobile routes with these role lists:

- machines, checklists, users: `admin`
- schedules and inspection today: `admin,petugas`
- findings and history: `admin,petugas,supervisor`
- reports: `admin,supervisor`
- desktop dashboard: `admin,supervisor`
- mobile dashboard: `admin,petugas,supervisor`

Replace the mobile route array's manual `adminOnly` branch with a `roles` value applied through the registered middleware. Keep route names, URIs, page metadata, and views unchanged.

- [ ] **Step 5: Align existing page tests with the approved access matrix**

Use Admin fixtures for page-render tests of admin-only areas. Preserve and extend explicit Petugas/Supervisor forbidden tests instead of weakening route protection. Do not alter rendered design assertions.

- [ ] **Step 6: Run role and page tests**

Run: `php artisan test --compact tests/Feature/Middleware/RoleMiddlewareTest.php tests/Feature/MobileNavigationTest.php tests/Feature/MobileChecklistsPageTest.php tests/Feature/MobileSchedulesPageTest.php tests/Feature/MobileFindingsPageTest.php tests/Feature/MobileHistoryPageTest.php tests/Feature/MobileUsersPageTest.php`

Expected: PASS with the new role matrix and unchanged page rendering for authorized users.

- [ ] **Step 7: Commit the authorization slice**

Stage only middleware, bootstrap, routes, and affected tests; commit with `feat: protect maintenance routes by role`.

### Task 6: Required Database and Application Verification

**Files:**
- Modify only if verification exposes a defect in an earlier task.

**Interfaces:**
- Consumes: All deliverables from Tasks 1–5.
- Produces: A verified, clean backend foundation ready for CRUD and report work.

- [ ] **Step 1: Format all changed PHP files**

Run: `vendor/bin/pint --dirty --format agent`

Expected: exit code 0.

- [ ] **Step 2: Rebuild and seed the configured database**

Run: `php artisan migrate:fresh --seed --no-interaction`

Expected: every migration and seeder completes without SQL or foreign-key errors. This command is destructive to the configured development database and must only run after confirming the target connection is the intended project database.

- [ ] **Step 3: Inspect registered routes and middleware**

Run: `php artisan route:list --except-vendor`

Expected: login/logout plus every desktop/mobile route is present; protected areas list both `auth` and the appropriate `role:*` middleware.

- [ ] **Step 4: Clear optimized caches**

Run: `php artisan optimize:clear`

Expected: configuration, route, view, and event caches clear successfully.

- [ ] **Step 5: Run the complete Laravel suite**

Run: `php artisan test --compact`

Expected: all tests pass with zero failures.

- [ ] **Step 6: Run the complete JavaScript suite**

Run: `node --test public/assets/js/*.test.js`

Expected: all existing frontend tests pass with zero failures.

- [ ] **Step 7: Check patch hygiene and final database facts**

Run `git diff --check`. Then use read-only database queries or a focused test to confirm the three account roles, seeded table counts, and absence of day-number columns without exposing password hashes.

Expected: no whitespace errors and all final facts match the approved specification.

- [ ] **Step 8: Commit verification-only fixes, if any**

If verification required code fixes, commit only those fixes and their regression tests with `fix: resolve maintenance backend verification issues`. Do not create an empty commit.

