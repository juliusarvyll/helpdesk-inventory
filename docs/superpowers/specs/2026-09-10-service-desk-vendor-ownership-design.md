# Design: Bring `laravel-service-desk` and `filament-service-desk` in-house

Date: 2026-09-10
Status: Draft — pending user review

## Motivation

While enabling ticket-lifecycle notifications (ticket created, claimed/assigned,
status changed, comment added), we found a bug in every mail notification
shipped by `jeffersongoncalves/laravel-service-desk` v1.0.9: each
`Notification::toMail()`'s `withSymfonyMessage()` callback sets the
`Message-ID` header with `Headers::addTextHeader()`, which Symfony Mime
rejects for that header name (`Message-ID` must be added via `addIdHeader()`).
Every queued notification job we tested (`TicketCreated`, `TicketAssigned`,
`TicketStatusChanged`) failed with the same `LogicException` and landed in
`failed_jobs`.

Fixing this means editing package source. The user decided not to maintain
this as a patched Composer dependency (via a path repository or
`composer-patches`) and instead wants the two packages' source fully merged
into the application as first-party `App\*` code — "own it, no more added
package/vendor". This document specs that migration.

## Goals

- Physically move `vendor/jeffersongoncalves/laravel-service-desk/src` and
  `vendor/jeffersongoncalves/filament-service-desk/src` into `app/`, under the
  `App\*` namespace, following this project's existing Laravel conventions.
- Remove both packages (and their now-orphaned transitive dependency
  `spatie/laravel-package-tools`) from `composer.json` / `vendor/` entirely.
- Fix the `Message-ID` header bug as part of the copy, in all affected
  notification classes.
- Preserve all existing behavior: config keys, translation keys and their
  `service-desk::` / `filament-service-desk::` namespaces, Blade view
  namespaces, migrations (already published), routes, artisan commands,
  and the ticket lifecycle events/listeners already wired in
  `ServiceDeskServiceProvider`.
- Port the two packages' own test suites into `tests/Feature` as real Pest
  tests against this application (its real models, its real `TestCase`),
  replacing their Orchestra Testbench fixtures.

## Non-Goals

- No behavior changes beyond the bug fix — this is a mechanical migration,
  not a refactor or feature change.
- No change to already-published files: `database/migrations/*`,
  `config/service-desk.php`, `config/filament-service-desk.php`.
- No renaming of translation keys, view names, config keys, or the ticket
  reference format — external-facing behavior (emails users receive, admin
  UI labels) stays identical.

## Current State (for reference)

`vendor/jeffersongoncalves/laravel-service-desk/src/` (137 files, ~8.7k LOC),
namespace `JeffersonGoncalves\ServiceDesk`:

```
Commands/        6 artisan commands (SLA checks, escalations, IMAP polling, cleanup)
Concerns/        HasSla, HasSlug, HasTickets, IsOperator (traits)
Contracts/       ApprovalWorkflow, BusinessHoursCalculator, EmailDriver,
                 EscalationHandler, InboundEmailHandler, KnowledgeBaseSearchable,
                 SlaCalculator
Enums/           14 enums (TicketStatus, TicketPriority, ApprovalStatus, ...)
Events/          27 domain events (TicketCreated, TicketAssigned, CommentAdded, ...)
Exceptions/      5 domain exceptions
Facades/         ServiceDesk (→ ServiceDeskManager)
Http/Controllers 4 inbound-email webhook controllers (Mailgun/SendGrid/Resend/Postmark)
Http/Middleware  4 matching webhook signature verifiers
Listeners/       LogTicketHistory + 4 "Send*Notification" listeners actually wired
                 in the service provider's boot method
Mail/            EmailParser, ThreadResolver, Drivers/ (Imap/Mailgun/Postmark/Resend/SendGrid)
Models/          27 Eloquent models (Ticket, TicketComment, Department, SlaPolicy, ...)
Notifications/   10 Notification classes (5 are currently unreachable dead code —
                 see prior investigation; kept as-is, not wired further, per Non-Goals)
Services/        TicketService, CommentService, DepartmentService, AttachmentService,
                 InboundEmailService, BusinessHoursService, SlaService, EscalationService,
                 ApprovalService, KnowledgeBaseService, ServiceRequestService
ServiceDeskManager.php, ServiceDeskServiceProvider.php
```

`vendor/jeffersongoncalves/filament-service-desk/src/` (132 files, ~6.5k LOC),
namespace `JeffersonGoncalves\FilamentServiceDesk`:

```
Concerns/HasServiceDeskPluginConfig.php   shared trait used by all 3 plugins
Admin/       Resources/ (13), Widgets/ (Overview, SLA, Departments)
Agent/       Pages/ (TicketQueuePage, Dashboard), Resources/ (2), Widgets/ (Stats, Breaches)
User/        Pages/ (Knowledge Base), Resources/ (2), Widgets/ (My Tickets)
ServiceDeskAdminPlugin.php, ServiceDeskAgentPlugin.php, ServiceDeskUserPlugin.php
```

Both packages also ship `resources/lang` (translations under the
`service-desk` / `filament-service-desk` namespaces) and, for the Filament
package, `resources/views` — neither is currently published into this app;
they're loaded straight from `vendor/` today.

App-side wiring today:
- `composer.json` directly requires only `jeffersongoncalves/filament-service-desk`
  (`laravel-service-desk` and `spatie/laravel-package-tools` are transitive).
- `app/Providers/Filament/{AdminPanelProvider,AgentPanelProvider,AppPanelProvider}.php`
  each call `ServiceDesk{Admin,Agent,User}Plugin::make()`. The package's "User"
  plugin corresponds to this app's `App` panel.
- `app/Filament/{Admin,Agent,App}/` currently only contain each panel's Login
  page plus empty `Resources/`/`Widgets/` placeholders — all actual ticket
  UI lives in the vendor package today.
- `app/` has no `Events/`, `Listeners/`, `Notifications/`, `Services/`,
  `Enums/`, `Contracts/`, `Mail/`, or `Exceptions/` directories yet.

## Namespace & Directory Mapping

Flat, Laravel-idiomatic top-level directories under `app/` (matching where
`php artisan make:event`, `make:listener`, `make:notification`, `make:mail`
would scaffold by default), rather than a bespoke `app/ServiceDesk/` grouping:

| Package source | App destination | Namespace |
|---|---|---|
| `laravel-service-desk/src/Models/*` | `app/Models/*` (alongside existing `Admin`, `Operator`, `User`) | `App\Models` |
| `laravel-service-desk/src/Events/*` | `app/Events/*` | `App\Events` |
| `laravel-service-desk/src/Listeners/*` | `app/Listeners/*` | `App\Listeners` |
| `laravel-service-desk/src/Notifications/*` | `app/Notifications/*` | `App\Notifications` |
| `laravel-service-desk/src/Services/*` | `app/Services/*` | `App\Services` |
| `laravel-service-desk/src/Enums/*` | `app/Enums/*` | `App\Enums` |
| `laravel-service-desk/src/Contracts/*` | `app/Contracts/*` | `App\Contracts` |
| `laravel-service-desk/src/Exceptions/*` | `app/Exceptions/*` (this app has no `app/Exceptions` yet — Laravel 13's slim skeleton only creates it on demand) | `App\Exceptions` |
| `laravel-service-desk/src/Mail/*` | `app/Mail/ServiceDesk/*` (`EmailParser`, `ThreadResolver`, `Drivers/`) | `App\Mail\ServiceDesk` |
| `laravel-service-desk/src/Http/Controllers/*` | `app/Http/Controllers/ServiceDesk/*` | `App\Http\Controllers\ServiceDesk` |
| `laravel-service-desk/src/Http/Middleware/*` | `app/Http/Middleware/*` | `App\Http\Middleware` |
| `laravel-service-desk/src/Concerns/*` | `app/Concerns/*` (new flat dir; small, framework-standard name for shared traits) | `App\Concerns` |
| `laravel-service-desk/src/Commands/*` | `app/Console/Commands/*` (this app has no `app/Console` yet either — standard Laravel location) | `App\Console\Commands` |
| `laravel-service-desk/src/Facades/ServiceDesk.php` + `ServiceDeskManager.php` | `app/Support/ServiceDesk.php` + `app/Support/ServiceDeskManager.php` | `App\Support` |
| `laravel-service-desk/src/ServiceDeskServiceProvider.php` | replaced by `app/Providers/ServiceDeskServiceProvider.php` (see below) | `App\Providers` |
| `filament-service-desk/src/Concerns/HasServiceDeskPluginConfig.php` | `app/Filament/Concerns/HasServiceDeskPluginConfig.php` | `App\Filament\Concerns` |
| `filament-service-desk/src/Admin/{Resources,Widgets}` | merged into `app/Filament/Admin/{Resources,Widgets}` | `App\Filament\Admin\*` |
| `filament-service-desk/src/Agent/{Pages,Resources,Widgets}` | merged into `app/Filament/Agent/{Pages,Resources,Widgets}` | `App\Filament\Agent\*` |
| `filament-service-desk/src/User/{Pages,Resources,Widgets}` | merged into `app/Filament/App/{Pages,Resources,Widgets}` (package's "User" = this app's "App" panel) | `App\Filament\App\*` |
| `filament-service-desk/src/ServiceDesk{Admin,Agent,User}Plugin.php` | `app/Filament/{Admin,Agent,App}/ServiceDeskPlugin.php` | `App\Filament\{Admin,Agent,App}` |

All internal references (`use JeffersonGoncalves\ServiceDesk\...` /
`use JeffersonGoncalves\FilamentServiceDesk\...`) across every copied file get
rewritten to the corresponding `App\...` path per this table. This is a
mechanical, scriptable find/replace per directory once the mapping is fixed,
followed by a full static-analysis pass (`vendor/bin/phpstan`) to catch
anything the script missed.

## Config, translations, and views

- `config/service-desk.php` and `config/filament-service-desk.php` are
  already published and app-owned — untouched, still read via
  `config('service-desk.*')` string keys (no namespace dependency).
- `laravel-service-desk/resources/lang/*` → copied to `lang/service-desk/*`.
  `filament-service-desk/resources/lang/*` → copied to
  `lang/filament-service-desk/*`. Both are re-registered under their
  **existing namespaces** (`service-desk`, `filament-service-desk`) via
  `Lang::addNamespace()` (or `loadTranslationsFrom()`) in the new
  `ServiceDeskServiceProvider::boot()`. Every existing
  `__('service-desk::notifications.ticket_created.subject')`-style call
  across ~250 files keeps working unmodified.
- `filament-service-desk/resources/views/*` → copied to
  `resources/views/vendor/filament-service-desk/*`, re-registered under the
  `filament-service-desk` view namespace the same way, so every
  `view('filament-service-desk::pages.agent.ticket-queue')` call keeps
  working unmodified.
- `filament-service-desk/resources/css`, `resources/dist` (compiled Filament
  panel assets) copied alongside and re-published the same way the package
  currently exposes them, so panel styling doesn't regress.

## New `App\Providers\ServiceDeskServiceProvider`

Replaces the package's `PackageServiceProvider`-based provider. Responsibilities,
carried over 1:1 from the current `ServiceDeskServiceProvider::packageRegistered()`
/ `packageBooted()`:

```php
public function register(): void
{
    $this->app->singleton(TicketService::class);
    $this->app->singleton(CommentService::class);
    $this->app->singleton(DepartmentService::class);
    $this->app->singleton(AttachmentService::class);
    $this->app->singleton(InboundEmailService::class);
    $this->app->bind(SlaCalculator::class, BusinessHoursService::class);
    $this->app->singleton(ServiceDeskManager::class, /* ... */);
}

public function boot(): void
{
    $this->loadTranslationsFrom(lang_path('service-desk'), 'service-desk');
    $this->loadTranslationsFrom(lang_path('filament-service-desk'), 'filament-service-desk');
    $this->loadViewsFrom(resource_path('views/vendor/filament-service-desk'), 'filament-service-desk');
    $this->loadRoutesFrom(base_path('routes/service-desk-webhooks.php'));

    $this->commands([
        CheckSlaBreachesCommand::class,
        CleanInboundEmailsCommand::class,
        CloseStaleTicketsCommand::class,
        PollImapMailboxCommand::class,
        ProcessEscalationsCommand::class,
        RecalculateSlaCommand::class,
    ]);

    if (config('service-desk.register_default_listeners', true)) {
        Event::subscribe(LogTicketHistory::class);
        Event::listen(TicketCreated::class, SendTicketCreatedNotification::class);
        Event::listen(TicketStatusChanged::class, SendTicketStatusChangedNotification::class);
        Event::listen(CommentAdded::class, SendCommentAddedNotification::class);
        Event::listen(TicketAssigned::class, SendTicketAssignedNotification::class);
        Event::listen(InboundEmailReceived::class, ProcessInboundEmail::class);
    }
}
```

Registered in `bootstrap/providers.php` alongside `AppServiceProvider`.

Webhook routes move from the package's `routes/webhooks.php` to
`routes/service-desk-webhooks.php` in the app, same route group/prefix/names.

**Correction found during plan self-review:** `filament-service-desk` ships
its own second package provider, `FilamentServiceDeskServiceProvider`
(distinct from the core package's `ServiceDeskServiceProvider`), whose only
job is `->hasViews('filament-service-desk')`, `->hasTranslations()`
(namespace defaults to the package name `filament-service-desk`), and
registering the plugin's one compiled CSS file via
`FilamentAsset::register([Css::make('filament-service-desk', $path)], 'jeffersongoncalves/filament-service-desk')`.
Rather than create a second app-owned provider, these three responsibilities
are folded into the same `App\Providers\ServiceDeskServiceProvider::boot()`
described above — it already calls `loadTranslationsFrom`/`loadViewsFrom` for
this namespace, and gains one more call to `FilamentAsset::register()`
pointing at the copied CSS file under `resources/css/`.

## Filament plugin classes

Kept as thin `Filament\Contracts\Plugin` classes (not inlined into the
PanelProviders) to preserve the existing fluent config API
(`->knowledgeBase(true)->sla(true)->emailChannels(true)->serviceCatalog(true)`)
with a minimal diff. Only change in each of the 3 PanelProviders is the
`use` import and class name:

```diff
-use JeffersonGoncalves\FilamentServiceDesk\ServiceDeskAdminPlugin;
+use App\Filament\Admin\ServiceDeskPlugin;
...
-ServiceDeskAdminPlugin::make()
+ServiceDeskPlugin::make()
```

## Bug fix folded into the copy

In every copied notification class that sets a `Message-ID` header
(`TicketCreatedNotification`, `TicketStatusChangedNotification`,
`TicketAssignedNotification`, `NewCommentNotification`,
`TicketClosedNotification`), change:

```php
$headers->addTextHeader('Message-ID', $messageId);
```
to:
```php
$headers->addIdHeader('Message-ID', $messageId);
```

No other behavior change to these classes.

## Composer changes

1. `composer remove jeffersongoncalves/filament-service-desk` — this also
   drops `jeffersongoncalves/laravel-service-desk` and
   `spatie/laravel-package-tools` once nothing else requires them.
2. Delete `vendor/jeffersongoncalves/laravel-service-desk` and
   `vendor/jeffersongoncalves/filament-service-desk` (composer remove should
   already do this; verified as a final check).
3. `composer dump-autoload`.
4. Confirm `composer show --direct` no longer lists either package.

## Testing strategy

Port the packages' own test coverage into `tests/Feature` as Pest tests
against this application's real `TestCase`, models, and factories (no
Orchestra Testbench):

- `tests/Feature/ServiceDesk/TicketLifecycleTest.php` — create, assign/claim,
  status change, close, reopen (covers `TicketService`, the 4 wired events).
- `tests/Feature/ServiceDesk/CommentNotificationTest.php` — public comment
  notifies requester + watchers, skips comment author, skips internal comments.
- `tests/Feature/ServiceDesk/NotificationMailTest.php` — `Notification::fake()`
  assertions for `TicketCreatedNotification`, `TicketAssignedNotification`,
  `TicketStatusChangedNotification`, `NewCommentNotification`, **plus a
  regression test that actually renders each `toMail()` message** (not just
  fakes it) to catch header bugs like the one that started this — this is
  the test that would have caught the `Message-ID` bug before it reached
  `failed_jobs`.
- `tests/Feature/ServiceDesk/SlaServiceTest.php`, `EscalationServiceTest.php`,
  `ApprovalServiceTest.php` — port the package's existing service-level unit
  tests, adapted to real factories. **Deferred to a fast-follow after this
  plan's initial pass** (see the implementation plan's Task 6 scope note):
  writing real assertions for these three requires reading each service's
  actual method signatures first, the same way `TicketService`/
  `CommentService` were read before `TicketLifecycleTest`/
  `CommentNotificationTest` were written — doing that for all 5 services in
  one plan risked shallow, unverified test bodies.
- Skip porting: IMAP/inbound-email driver tests and webhook signature tests
  unless the app is actually using inbound email today (`SERVICE_DESK_INBOUND_DRIVER`
  is unset by default) — flagged as an explicit exclusion, not silently dropped.

Run `vendor/bin/pint --dirty` and `vendor/bin/phpstan` after the migration,
and the full suite (`php artisan test --compact`) before calling this done.

## Rollout order

1. Copy + renamespace `laravel-service-desk` source into `app/` (per mapping
   table). Get it compiling/autoloading standalone (phpstan clean) before
   touching the Filament package, so failures are easier to isolate.
2. Write the new `App\Providers\ServiceDeskServiceProvider`, register it,
   remove nothing yet (old package still installed) — run the app against
   the new classes while the old package still provides the Filament plugins,
   to isolate core-package issues from UI-package issues.
3. Copy + renamespace `filament-service-desk` source into `app/Filament/*`,
   switch the 3 PanelProviders to the new plugin classes.
4. Copy lang/views, wire `loadTranslationsFrom`/`loadViewsFrom`.
5. Apply the Message-ID bug fix.
6. Port tests; run full suite.
7. Remove the two Composer packages; `composer dump-autoload`; run the full
   suite again against the fully-vendor-free app.
8. Manual smoke test in the browser: create a ticket as a user, claim it as
   an agent, add a comment, close it — confirm each step's job lands in
   `jobs`/gets processed (not `failed_jobs`), and check `storage/logs` /
   mail log for the rendered email.

## Risks

- **Namespace-rewrite mistakes at this scale (269 files)** are the primary
  risk. Mitigated by doing the rewrite mechanically per the mapping table,
  then leaning on `phpstan` (already configured in this repo) and the full
  test suite to catch anything missed, rather than hand-editing use-by-use.
- **Filament auto-discovery / resource registration order** could differ
  now that resources live directly in `app/Filament/*` instead of being
  registered by a package plugin's `getResources()` — mitigated by keeping
  the plugin classes (step above) so registration is explicit and unchanged.
- **Losing upstream fixes**: once merged in, this app no longer gets
  `laravel-service-desk`/`filament-service-desk` updates via Composer. This
  is the explicit tradeoff the user asked for ("own it").
