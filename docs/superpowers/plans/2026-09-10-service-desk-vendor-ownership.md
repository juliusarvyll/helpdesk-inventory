# Service Desk Vendor Ownership Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merge `vendor/jeffersongoncalves/laravel-service-desk` and `vendor/jeffersongoncalves/filament-service-desk` into this app's own `App\*` namespace, fix the Message-ID mail bug found in the process, remove both packages from Composer, and add real test coverage — so the ticketing/notification code is fully owned by this codebase.

**Architecture:** Mechanical copy-and-renamespace of ~269 PHP files from two vendor packages into Laravel-idiomatic top-level `app/` directories (`Events`, `Listeners`, `Notifications`, `Services`, `Enums`, `Contracts`, `Mail`, `Console/Commands`, `Http`, `Concerns`, `Support`, `Models`, and merged into the existing `Filament/{Admin,Agent,App}` panel dirs), using an exact, ordered `sed` rewrite chain per phase so every internal reference lands on the correct new FQCN. Translations and views are re-registered under their existing namespaces so the ~250 `__('service-desk::...')` / `view('filament-service-desk::...')` call sites do not need to change. The Message-ID bug is fixed in-place while copying the affected Notification classes. Both packages stay installed until the very last task, so failures are isolated to "did the copy compile" rather than "did removing the package break something."

**Tech Stack:** Laravel 13, Filament 5, Pest 5 (pestphp/pest-plugin-laravel), PHPStan/Larastan, Laravel Pint. MySQL (real DB, not sqlite — confirmed via Boost `database-schema`).

**Spec:** `docs/superpowers/specs/2026-09-10-service-desk-vendor-ownership-design.md`

## Global Constraints

- No behavior changes beyond the Message-ID bug fix — this is a mechanical migration (per spec Non-Goals).
- Do not touch already-published `database/migrations/*`, `config/service-desk.php`, `config/filament-service-desk.php` **structure** (only the FQCN references inside `filament-service-desk.php` change, not its shape).
- Preserve every existing translation key, view name, config key, and the ticket reference format — emails and admin UI text must stay identical.
- Run `vendor/bin/pint --dirty --format agent` after any PHP file is modified, before considering a task done (per CLAUDE.md).
- Run the narrowest relevant test/check per task; run the full suite only in the final tasks.
- This is not a git repository (confirmed) — "commit" steps below use `git add`/`git commit` **only if** `git status` succeeds; if there is no `.git`, skip the commit sub-step and just note the task as done. Check once at the start of Task 1 and remember the answer for the rest of the plan.

---

## Task 1: Foundation — Enums, Contracts, Exceptions, Concerns

**Files:**
- Create: `app/Enums/*.php` (14 files, copied from `vendor/jeffersongoncalves/laravel-service-desk/src/Enums/`)
- Create: `app/Contracts/*.php` (7 files, from `.../src/Contracts/`)
- Create: `app/Exceptions/*.php` (5 files, from `.../src/Exceptions/`)
- Create: `app/Concerns/*.php` (4 files, from `.../src/Concerns/`: `HasSla.php`, `HasSlug.php`, `HasTickets.php`, `IsOperator.php`)
- Modify: `app/Models/User.php:25` (the `use JeffersonGoncalves\ServiceDesk\Concerns\HasTickets;` line)
- Modify: `app/Models/Operator.php:22` (the `use JeffersonGoncalves\ServiceDesk\Concerns\IsOperator;` line)

**Interfaces:**
- Produces: `App\Enums\{TicketStatus,TicketPriority,TicketSource,CommentType,ApprovalStatus,ArticleStatus,ArticleVisibility,DayOfWeek,EscalationAction,FormFieldType,HistoryAction,ServiceCategoryVisibility,ServiceRequestStatus,SlaBreachType}`, `App\Contracts\{ApprovalWorkflow,BusinessHoursCalculator,EmailDriver,EscalationHandler,InboundEmailHandler,KnowledgeBaseSearchable,SlaCalculator}`, `App\Exceptions\{EmailProcessingException,InvalidAttachmentException,InvalidStatusTransitionException,TicketNotFoundException,UnauthorizedOperatorException}`, `App\Concerns\{HasSla,HasSlug,HasTickets,IsOperator}` — every later task's models/services import from these.

- [ ] **Step 1: Confirm git status**

```bash
cd /home/ict/dev/testkit
git status
```
If this errors with "not a git repository", skip every "commit" sub-step for the rest of this plan and just move on after each task's verification passes.

- [ ] **Step 2: Copy the four foundation directories**

```bash
cd /home/ict/dev/testkit
mkdir -p app/Enums app/Contracts app/Exceptions app/Concerns
cp vendor/jeffersongoncalves/laravel-service-desk/src/Enums/*.php app/Enums/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Contracts/*.php app/Contracts/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Exceptions/*.php app/Exceptions/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Concerns/*.php app/Concerns/
```

- [ ] **Step 3: Rewrite namespaces in the copied files**

```bash
cd /home/ict/dev/testkit
find app/Enums app/Contracts app/Exceptions app/Concerns -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g'
```

This 8-rule chain (most-specific-first, generic catch-all last) is the **exact same chain reused in every task below** that copies from `laravel-service-desk` — always paste it in full, never abbreviate it as "same as Task 1".

- [ ] **Step 4: Update the two existing models that already import the vendor Concerns**

Edit `app/Models/User.php` line 25:
```diff
-use JeffersonGoncalves\ServiceDesk\Concerns\HasTickets;
+use App\Concerns\HasTickets;
```

Edit `app/Models/Operator.php` line 22:
```diff
-use JeffersonGoncalves\ServiceDesk\Concerns\IsOperator;
+use App\Concerns\IsOperator;
```

- [ ] **Step 5: Regenerate autoload and verify with static analysis**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Enums app/Contracts app/Exceptions app/Concerns app/Models/User.php app/Models/Operator.php --no-progress
```
Expected: no errors (the Concerns files reference `App\Models\{Ticket,TicketComment,TicketWatcher,Department,TicketHistory}`, which don't exist yet until Task 2 — if phpstan complains only about those specific classes not being found yet, that's expected at this point; any other error must be fixed before continuing).

- [ ] **Step 6: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Enums app/Contracts app/Exceptions app/Concerns app/Models/User.php app/Models/Operator.php
git commit -m "$(cat <<'EOF'
Bring in service-desk Enums, Contracts, Exceptions, Concerns as App\* classes

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```
(Skip this step entirely if Step 1 found no git repository.)

---

## Task 2: Models and their factories

**Files:**
- Create: `app/Models/*.php` (27 files, from `.../src/Models/`)
- Create: `database/factories/{CategoryFactory,DepartmentFactory,TagFactory,TicketCommentFactory,TicketFactory}.php` (from `.../database/factories/`)

**Interfaces:**
- Consumes: `App\Enums\*`, `App\Concerns\*` (Task 1)
- Produces: `App\Models\{BusinessHoursSchedule,BusinessHoursTimeSlot,CannedResponse,Category,Department,EmailChannel,EscalationRule,Holiday,InboundEmail,KbArticle,KbArticleFeedback,KbArticleVersion,KbCategory,Service,ServiceCategory,ServiceFormField,ServiceRequest,ServiceRequestApproval,SlaPolicy,SlaTarget,Tag,Ticket,TicketAttachment,TicketComment,TicketHistory,TicketSla,TicketWatcher}` and `Database\Factories\{CategoryFactory,DepartmentFactory,TagFactory,TicketCommentFactory,TicketFactory}` — every later task's services/events/notifications/tests use these.

- [ ] **Step 1: Copy models and factories**

```bash
cd /home/ict/dev/testkit
cp vendor/jeffersongoncalves/laravel-service-desk/src/Models/*.php app/Models/
cp vendor/jeffersongoncalves/laravel-service-desk/database/factories/*.php database/factories/
```

- [ ] **Step 2: Rewrite namespaces (same 8-rule chain as Task 1 Step 3)**

```bash
cd /home/ict/dev/testkit
find app/Models database/factories -name "*.php" -newer composer.json -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g'
```

`-newer composer.json` scopes the `find` to just-copied files so this pass never touches `app/Models/{Admin,Operator,User}.php` again (already handled in Task 1, and re-running is harmless but noisy). If unsure, list the 27 filenames explicitly instead of using `find`.

- [ ] **Step 3: Verify factory-to-model resolution**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Models database/factories --no-progress
```
Expected: no errors. This confirms `App\Models\Ticket`'s `newFactory()` (if it overrides one) correctly resolves to `Database\Factories\TicketFactory`, and that `TicketFactory`'s `use App\Models\User;` / `use App\Models\Department;` / `use App\Models\Ticket;` / `use App\Enums\{TicketPriority,TicketSource,TicketStatus};` all resolve.

- [ ] **Step 4: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Models database/factories
git commit -m "$(cat <<'EOF'
Bring in service-desk Eloquent models and factories as App\*

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 3: Events, Listeners, Notifications — with the Message-ID bug fix

**Files:**
- Create: `app/Events/*.php` (27 files, from `.../src/Events/`)
- Create: `app/Listeners/*.php` (6 files: `LogTicketHistory.php`, `ProcessInboundEmail.php`, `SendCommentAddedNotification.php`, `SendTicketAssignedNotification.php`, `SendTicketCreatedNotification.php`, `SendTicketStatusChangedNotification.php`)
- Create: `app/Notifications/*.php` (10 files, from `.../src/Notifications/`)

**Interfaces:**
- Consumes: `App\Models\*` (Task 2), `App\Enums\*` (Task 1)
- Produces: `App\Events\{TicketCreated,TicketAssigned,TicketStatusChanged,CommentAdded,InboundEmailReceived,...}`, `App\Listeners\{LogTicketHistory,SendTicketCreatedNotification,SendTicketStatusChangedNotification,SendCommentAddedNotification,SendTicketAssignedNotification,ProcessInboundEmail}`, `App\Notifications\{TicketCreatedNotification,TicketAssignedNotification,TicketStatusChangedNotification,NewCommentNotification,TicketClosedNotification,SlaBreachedNotification,SlaNearBreachNotification,EscalationNotification,ApprovalRequestedNotification,ApprovalDecisionNotification}` — Task 5's provider wires four of these events to their listeners.

- [ ] **Step 1: Copy the three directories**

```bash
cd /home/ict/dev/testkit
mkdir -p app/Events app/Listeners app/Notifications
cp vendor/jeffersongoncalves/laravel-service-desk/src/Events/*.php app/Events/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Listeners/*.php app/Listeners/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Notifications/*.php app/Notifications/
```

- [ ] **Step 2: Rewrite namespaces (same 8-rule chain)**

```bash
cd /home/ict/dev/testkit
find app/Events app/Listeners app/Notifications -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g'
```

- [ ] **Step 3: Fix the Message-ID bug in the 5 affected notifications**

In each of `app/Notifications/TicketCreatedNotification.php`, `TicketStatusChangedNotification.php`, `TicketAssignedNotification.php`, `NewCommentNotification.php`, `TicketClosedNotification.php`, change the one line that adds the `Message-ID` header:

```diff
-                $headers->addTextHeader('Message-ID', $messageId);
+                $headers->addIdHeader('Message-ID', $messageId);
```

Verify all 5 occurrences are caught:
```bash
cd /home/ict/dev/testkit
grep -rn "addTextHeader('Message-ID'" app/Notifications/
```
Expected: no output (all 5 replaced). If any remain, that file was missed — fix it.

- [ ] **Step 4: Verify**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Events app/Listeners app/Notifications --no-progress
```
Expected: no errors.

- [ ] **Step 5: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Events app/Listeners app/Notifications
git commit -m "$(cat <<'EOF'
Bring in service-desk Events/Listeners/Notifications; fix Message-ID header bug

Symfony Mime requires Message-ID to be added via addIdHeader() (creates an
IdentificationHeader), not addTextHeader() (creates an UnstructuredHeader).
The vendor package used addTextHeader() everywhere, which threw a
LogicException on every queued mail notification and sent every job to
failed_jobs.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 4: Services, Mail, Http, Commands, Support (Facade + Manager)

**Files:**
- Create: `app/Services/*.php` (11 files, from `.../src/Services/`)
- Create: `app/Mail/ServiceDesk/*.php` and `app/Mail/ServiceDesk/Drivers/*.php` (from `.../src/Mail/`)
- Create: `app/Http/Controllers/ServiceDesk/*.php` (4 files, from `.../src/Http/Controllers/`)
- Create: `app/Http/Middleware/*.php` (4 files, from `.../src/Http/Middleware/`)
- Create: `app/Console/Commands/*.php` (6 files, from `.../src/Commands/`)
- Create: `app/Support/ServiceDesk.php`, `app/Support/ServiceDeskManager.php` (from `.../src/Facades/ServiceDesk.php` and `.../src/ServiceDeskManager.php`)

**Interfaces:**
- Consumes: `App\Models\*` (Task 2), `App\Events\*` (Task 3), `App\Enums\*`, `App\Contracts\*` (Task 1)
- Produces: `App\Services\{TicketService,CommentService,DepartmentService,AttachmentService,InboundEmailService,BusinessHoursService,SlaService,EscalationService,ApprovalService,KnowledgeBaseService,ServiceRequestService}`, `App\Support\ServiceDeskManager` — Task 5's provider binds these as singletons; Task 8-10's Filament resources call `app(App\Services\TicketService::class)` etc.

- [ ] **Step 1: Copy all five source trees**

```bash
cd /home/ict/dev/testkit
mkdir -p app/Services app/Mail/ServiceDesk/Drivers app/Http/Controllers/ServiceDesk app/Http/Middleware app/Console/Commands app/Support
cp vendor/jeffersongoncalves/laravel-service-desk/src/Services/*.php app/Services/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Mail/*.php app/Mail/ServiceDesk/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Mail/Drivers/*.php app/Mail/ServiceDesk/Drivers/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Http/Controllers/*.php app/Http/Controllers/ServiceDesk/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Http/Middleware/*.php app/Http/Middleware/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Commands/*.php app/Console/Commands/
cp vendor/jeffersongoncalves/laravel-service-desk/src/Facades/ServiceDesk.php app/Support/ServiceDesk.php
cp vendor/jeffersongoncalves/laravel-service-desk/src/ServiceDeskManager.php app/Support/ServiceDeskManager.php
```

- [ ] **Step 2: Rewrite namespaces (same 8-rule chain)**

```bash
cd /home/ict/dev/testkit
find app/Services app/Mail app/Http/Controllers/ServiceDesk app/Http/Middleware app/Console/Commands app/Support -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g'
```

- [ ] **Step 3: Verify**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Services app/Mail app/Http/Controllers/ServiceDesk app/Http/Middleware app/Console/Commands app/Support --no-progress
```
Expected: no errors.

- [ ] **Step 4: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Services app/Mail app/Http/Controllers/ServiceDesk app/Http/Middleware app/Console/Commands app/Support
git commit -m "$(cat <<'EOF'
Bring in service-desk Services, Mail drivers, webhook controllers, commands

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 5: `App\Providers\ServiceDeskServiceProvider` and webhook routes

**Files:**
- Create: `app/Providers/ServiceDeskServiceProvider.php`
- Create: `routes/service-desk-webhooks.php` (from `vendor/jeffersongoncalves/laravel-service-desk/routes/webhooks.php`)
- Modify: `bootstrap/providers.php`

**Interfaces:**
- Consumes: `App\Services\*` (Task 4), `App\Contracts\SlaCalculator`, `App\Services\BusinessHoursService` (Task 1, Task 4), `App\Events\{TicketCreated,TicketStatusChanged,CommentAdded,TicketAssigned,InboundEmailReceived}` (Task 3), `App\Listeners\{LogTicketHistory,SendTicketCreatedNotification,SendTicketStatusChangedNotification,SendCommentAddedNotification,SendTicketAssignedNotification,ProcessInboundEmail}` (Task 3), `App\Console\Commands\*` (Task 4), `App\Support\ServiceDeskManager` (Task 4)
- Produces: the app now boots `App\Services\TicketService` etc. as singletons and fires the four notification listeners on the four core events — this is the cut-over point tested by Task 6.

- [ ] **Step 1: Copy and rewrite the webhook routes file**

```bash
cd /home/ict/dev/testkit
cp vendor/jeffersongoncalves/laravel-service-desk/routes/webhooks.php routes/service-desk-webhooks.php
sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  routes/service-desk-webhooks.php
```

- [ ] **Step 2: Write the new service provider**

Create `app/Providers/ServiceDeskServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Console\Commands\CheckSlaBreachesCommand;
use App\Console\Commands\CleanInboundEmailsCommand;
use App\Console\Commands\CloseStaleTicketsCommand;
use App\Console\Commands\PollImapMailboxCommand;
use App\Console\Commands\ProcessEscalationsCommand;
use App\Console\Commands\RecalculateSlaCommand;
use App\Contracts\SlaCalculator;
use App\Events\CommentAdded;
use App\Events\InboundEmailReceived;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Listeners\LogTicketHistory;
use App\Listeners\ProcessInboundEmail;
use App\Listeners\SendCommentAddedNotification;
use App\Listeners\SendTicketAssignedNotification;
use App\Listeners\SendTicketCreatedNotification;
use App\Listeners\SendTicketStatusChangedNotification;
use App\Services\AttachmentService;
use App\Services\BusinessHoursService;
use App\Services\CommentService;
use App\Services\DepartmentService;
use App\Services\InboundEmailService;
use App\Services\TicketService;
use App\Support\ServiceDeskManager;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ServiceDeskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TicketService::class);
        $this->app->singleton(CommentService::class);
        $this->app->singleton(DepartmentService::class);
        $this->app->singleton(AttachmentService::class);
        $this->app->singleton(InboundEmailService::class);

        $this->app->bind(SlaCalculator::class, BusinessHoursService::class);

        $this->app->singleton(ServiceDeskManager::class, function ($app) {
            return new ServiceDeskManager(
                $app->make(TicketService::class),
                $app->make(CommentService::class),
                $app->make(DepartmentService::class),
                $app->make(AttachmentService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('service-desk'), 'service-desk');
        $this->loadTranslationsFrom(lang_path('filament-service-desk'), 'filament-service-desk');
        $this->loadViewsFrom(resource_path('views/vendor/filament-service-desk'), 'filament-service-desk');
        $this->loadRoutesFrom(base_path('routes/service-desk-webhooks.php'));

        FilamentAsset::register([
            Css::make('filament-service-desk', resource_path('css/filament-service-desk.css')),
        ], 'jeffersongoncalves/filament-service-desk');

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
}
```

Note: `loadTranslationsFrom(lang_path('filament-service-desk'), ...)`, `loadViewsFrom(resource_path('views/vendor/filament-service-desk'), ...)`, and the `Css::make('filament-service-desk', resource_path('css/filament-service-desk.css'))` path will all point at files/directories that don't exist until Task 12 — that's fine, none of `loadTranslationsFrom`/`loadViewsFrom`/`FilamentAsset::register` read or validate the path at registration time, they just won't find anything until Task 12 creates it.

- [ ] **Step 3: Register the provider**

Edit `bootstrap/providers.php`:
```diff
 <?php

 use App\Providers\AppServiceProvider;
+use App\Providers\ServiceDeskServiceProvider;

 return [
     AppServiceProvider::class,
+    ServiceDeskServiceProvider::class,
 ];
```

- [ ] **Step 4: Verify the app boots with the new provider**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
php artisan route:list --path=service-desk/webhooks
```
Expected: the 4 webhook routes (`service-desk.webhooks.mailgun`, `.sendgrid`, `.resend`, `.postmark`) listed with `App\Http\Controllers\ServiceDesk\*` handlers — proves the provider booted, the route file loaded, and the controllers/middleware resolve.

```bash
php artisan list | grep -i "service-desk\|sla\|escalation"
```
Expected: the 6 commands (`service-desk:check-sla-breaches`, etc. — exact names per each Command's `$signature`) are registered.

- [ ] **Step 5: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Providers/ServiceDeskServiceProvider.php routes/service-desk-webhooks.php bootstrap/providers.php
git commit -m "$(cat <<'EOF'
Add App\Providers\ServiceDeskServiceProvider to replace the package provider

Registers the same singletons, event listeners, commands, and webhook
routes the vendor package's ServiceDeskServiceProvider used to, now
pointing at App\* classes. The vendor package is still installed and its
own provider still runs at this point — Task 11 is what cuts the live
Filament UI over to the new classes.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 6: Port core-package tests to `tests/Feature/ServiceDesk`

**Files:**
- Create: `tests/Feature/ServiceDesk/TicketLifecycleTest.php`
- Create: `tests/Feature/ServiceDesk/CommentNotificationTest.php`
- Create: `tests/Feature/ServiceDesk/NotificationMailTest.php`

**Interfaces:**
- Consumes: `App\Services\TicketService::{create,update,updateStatus,assign,unassign,close,reopen,delete,findByUuid}` (exact method names/signatures — read `app/Services/TicketService.php` before writing this task, since the plan author has not verified every signature against the copied file), `App\Services\CommentService::addComment`, `App\Models\{Ticket,Department,TicketComment}`, `App\Notifications\{TicketCreatedNotification,TicketAssignedNotification,TicketStatusChangedNotification,NewCommentNotification}`, `Database\Factories\{DepartmentFactory,TicketFactory}` (Task 2), `App\Models\{User,Operator}` (existing)
- Produces: automated proof the ported core package works end-to-end against the real app, and a regression test that would have caught the Message-ID bug.

- [ ] **Step 1: Confirm `TicketService` and `CommentService` signatures match what the tests below assume**

```bash
cd /home/ict/dev/testkit
grep -n "public function" app/Services/TicketService.php app/Services/CommentService.php
```
The test bodies in Steps 2, 4, and 6 below were written against these exact signatures (confirmed by reading the vendor source during planning — the copy in Tasks 1-4 should be byte-identical apart from namespaces):
- `TicketService::create(array $data, Model $user): Ticket`
- `TicketService::assign(Ticket $ticket, Model $operator, ?Model $assignedBy = null): Ticket`
- `TicketService::close(Ticket $ticket, ?Model $performer = null): Ticket` — internally calls `changeStatus()`, which checks `TicketStatus::Open->canTransitionTo(TicketStatus::Closed)`; this transition is allowed (see `app/Enums/TicketStatus.php::allowedTransitions()`), so closing a freshly-created (`Open`) ticket works directly.
- `TicketService::reopen(Ticket $ticket, ?Model $performer = null): Ticket` — same path, `Closed->canTransitionTo(Open)` is allowed when `config('service-desk.ticket.allow_reopen')` is true (it is, by default).
- `CommentService::addReply(Ticket $ticket, Model $author, string $body, array $options = []): TicketComment` and `addNote(...)` (same shape) — **not** `addComment()` directly, which requires a `CommentType $type` as a required 4th positional argument rather than an `$options` key.

If this `grep` output doesn't match, the copy in Tasks 1-4 diverged from the vendor source (it shouldn't have — these tasks only rewrite namespaces) — fix the copy, don't rewrite these tests to match a divergence.

- [ ] **Step 2: Write the failing tests — `TicketLifecycleTest.php`**

Create `tests/Feature/ServiceDesk/TicketLifecycleTest.php`:

```php
<?php

use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Events\TicketReopened;
use App\Events\TicketStatusChanged;
use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(TicketService::class);
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
});

it('creates a ticket and dispatches TicketCreated', function () {
    Event::fake([TicketCreated::class]);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Cannot log in',
        'description' => 'Password reset email never arrives.',
    ], $this->user);

    expect($ticket->status)->toBe(TicketStatus::Open);
    expect($ticket->user_id)->toBe($this->user->id);
    Event::assertDispatched(TicketCreated::class);
});

it('assigns (claims) a ticket to an operator and dispatches TicketAssigned', function () {
    Event::fake([TicketAssigned::class]);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Printer jam',
        'description' => 'Printer on 3rd floor is jammed.',
    ], $this->user);

    $this->service->assign($ticket, $this->operator, $this->operator);

    $ticket->refresh();
    expect($ticket->assigned_to_id)->toBe($this->operator->id);
    Event::assertDispatched(TicketAssigned::class, fn ($event) => $event->ticket->is($ticket) && $event->assignedTo->is($this->operator));
});

it('dispatches TicketStatusChanged and TicketClosed when closing a ticket', function () {
    Event::fake([TicketStatusChanged::class, TicketClosed::class]);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Broken chair',
        'description' => 'Office chair leg broke.',
    ], $this->user);

    $this->service->close($ticket, $this->operator);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Closed);
    Event::assertDispatched(TicketStatusChanged::class);
    Event::assertDispatched(TicketClosed::class);
});

it('dispatches TicketReopened when reopening a closed ticket', function () {
    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Broken chair',
        'description' => 'Office chair leg broke.',
    ], $this->user);
    $this->service->close($ticket, $this->operator);

    Event::fake([TicketReopened::class]);
    $this->service->reopen($ticket, $this->operator);

    $ticket->refresh();
    expect($ticket->status)->not->toBe(TicketStatus::Closed);
    Event::assertDispatched(TicketReopened::class);
});
```

- [ ] **Step 3: Run it to confirm it fails only for the right reason (or passes if the ported service already matches)**

```bash
cd /home/ict/dev/testkit
php artisan test tests/Feature/ServiceDesk/TicketLifecycleTest.php --compact
```
If any test fails because a method/parameter name doesn't match `TicketService`'s real signature (checked in Step 1), fix the test to match the real signature — do not change `TicketService` to match a guessed test. If a test fails for any other reason, that is a real bug introduced during the Task 1-5 copy/rewrite and must be fixed in the `app/` source, not worked around in the test.

- [ ] **Step 4: Write `CommentNotificationTest.php`**

Create `tests/Feature/ServiceDesk/CommentNotificationTest.php`. Note: `CommentService::addComment()`'s real signature (confirmed by reading `app/Services/CommentService.php` in Step 1) is `addComment(Ticket $ticket, Model $author, string $body, CommentType $type, array $options = [])` — `$type` is a required positional argument, not an `$options` key. Use the service's `addReply()`/`addNote()` convenience wrappers instead, which fill `$type` (and, for `addNote()`, `is_internal`) for you:

```php
<?php

use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Services\CommentService;
use App\Services\TicketService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->ticketService = app(TicketService::class);
    $this->commentService = app(CommentService::class);
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
    $this->ticket = $this->ticketService->create([
        'department_id' => $this->department->id,
        'title' => 'Slow laptop',
        'description' => 'Laptop takes 10 minutes to boot.',
    ], $this->user);
});

it('notifies the ticket requester when an operator adds a public reply', function () {
    Notification::fake();

    $this->commentService->addReply($this->ticket, $this->operator, 'Have you tried a factory reset?');

    Notification::assertSentTo($this->user, NewCommentNotification::class);
});

it('does not notify the comment author about their own reply', function () {
    Notification::fake();

    $this->commentService->addReply($this->ticket, $this->user, 'Still an issue.');

    Notification::assertNotSentTo($this->user, NewCommentNotification::class);
});

it('does not notify anyone for an internal note', function () {
    Notification::fake();

    $this->commentService->addNote($this->ticket, $this->operator, 'Internal note for the team.');

    Notification::assertNothingSent();
});
```

- [ ] **Step 5: Run it, fix real signature mismatches only**

```bash
cd /home/ict/dev/testkit
php artisan test tests/Feature/ServiceDesk/CommentNotificationTest.php --compact
```

- [ ] **Step 6: Write `NotificationMailTest.php` — the regression test for the actual bug**

This is the test that would have caught the Message-ID bug: it does **not** use `Notification::fake()`, it renders the real `toMail()` message.

Create `tests/Feature/ServiceDesk/NotificationMailTest.php`:

```php
<?php

use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Services\CommentService;
use App\Services\TicketService;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
    $this->ticket = app(TicketService::class)->create([
        'department_id' => $this->department->id,
        'title' => 'Mail rendering check',
        'description' => 'Used to render this to verify Message-ID header.',
    ], $this->user);
});

it('renders TicketCreatedNotification to a real mail message without throwing', function () {
    $mail = (new TicketCreatedNotification($this->ticket))->toMail($this->user);

    expect($mail->render())->toContain($this->ticket->reference_number);
});

it('renders TicketAssignedNotification to a real mail message without throwing', function () {
    $mail = (new TicketAssignedNotification($this->ticket))->toMail($this->operator);

    expect($mail->render())->toContain($this->ticket->reference_number);
});

it('renders TicketStatusChangedNotification to a real mail message without throwing', function () {
    $mail = (new TicketStatusChangedNotification(
        $this->ticket,
        \App\Enums\TicketStatus::Open,
        \App\Enums\TicketStatus::Closed,
    ))->toMail($this->user);

    expect($mail->render())->toContain($this->ticket->reference_number);
});

it('renders NewCommentNotification to a real mail message without throwing', function () {
    $comment = app(CommentService::class)->addReply($this->ticket, $this->operator, 'Test comment');

    $mail = (new NewCommentNotification($this->ticket, $comment))->toMail($this->user);

    expect($mail->render())->toContain($this->ticket->reference_number);
});
```

`MailMessage::render()` builds the actual `Symfony\Component\Mime\Email` and walks the `withSymfonyMessage()` callback — this is exactly the code path that threw `LogicException` before the fix. If Task 3's fix were reverted, these four tests would fail with that exception.

- [ ] **Step 7: Run all three new test files together**

```bash
cd /home/ict/dev/testkit
php artisan test tests/Feature/ServiceDesk --compact
```
Expected: all pass. If `render()` throws the original `LogicException`, the fix in Task 3 Step 3 was incomplete — go back and check `grep -rn "addTextHeader('Message-ID'" app/Notifications/` again.

- [ ] **Step 8: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add tests/Feature/ServiceDesk
git commit -m "$(cat <<'EOF'
Add Feature tests for ported ticket/comment/notification flows

Includes a mail-rendering regression test (calls toMail()->render()
instead of Notification::fake()) that exercises the exact code path
that threw the original Message-ID LogicException.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

**Scope note vs. the spec:** the design doc's Testing Strategy section also listed porting `SlaServiceTest`, `EscalationServiceTest`, and `ApprovalServiceTest`. This plan deliberately does not include them: writing real assertions for those three services would require reading `app/Services/{SlaService,EscalationService,ApprovalService}.php` in as much depth as `TicketService`/`CommentService` got here (Step 1), and fabricating test bodies against services not yet inspected risks tests that don't compile or assert nothing meaningful — worse than not having them. Treat porting those three as a fast-follow task once this plan lands: repeat this task's Step 1 pattern (read the real service signatures first) against `vendor/jeffersongoncalves/laravel-service-desk/tests/Feature/Services/{SlaServiceTest,EscalationServiceTest... check exact filenames}.php` for the assertions to adapt, then write against the real `App\Services\*` signatures the same way `TicketLifecycleTest` was written here.

---

## Task 7: Filament shared Concerns trait

**Files:**
- Create: `app/Filament/Concerns/HasServiceDeskPluginConfig.php` (from `vendor/jeffersongoncalves/filament-service-desk/src/Concerns/HasServiceDeskPluginConfig.php`)

**Interfaces:**
- Produces: `App\Filament\Concerns\HasServiceDeskPluginConfig` — used by all three plugin classes in Tasks 8-10.

- [ ] **Step 1: Copy and rewrite**

```bash
cd /home/ict/dev/testkit
mkdir -p app/Filament/Concerns
cp vendor/jeffersongoncalves/filament-service-desk/src/Concerns/HasServiceDeskPluginConfig.php app/Filament/Concerns/
sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAdminPlugin/App\\Filament\\Admin\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAgentPlugin/App\\Filament\\Agent\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskUserPlugin/App\\Filament\\App\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Concerns/App\\Filament\\Concerns/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Admin/App\\Filament\\Admin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Agent/App\\Filament\\Agent/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\User/App\\Filament\\App/g' \
  app/Filament/Concerns/HasServiceDeskPluginConfig.php
```

This 15-rule chain (8 core rules + 7 Filament rules) is the **exact chain reused in Tasks 8-11** — always paste it in full.

- [ ] **Step 2: Verify and commit**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Filament/Concerns --no-progress
vendor/bin/pint --dirty --format agent
git add app/Filament/Concerns
git commit -m "$(cat <<'EOF'
Bring in filament-service-desk's shared plugin-config trait

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 8: Admin panel resources, widgets, and plugin

**Files:**
- Create: `app/Filament/Admin/Resources/*` (13 resource directories, from `.../src/Admin/Resources/`)
- Create: `app/Filament/Admin/Widgets/*` (3 files, from `.../src/Admin/Widgets/`)
- Create: `app/Filament/Admin/ServiceDeskPlugin.php` (from `.../src/ServiceDeskAdminPlugin.php`)

**Interfaces:**
- Consumes: `App\Filament\Concerns\HasServiceDeskPluginConfig` (Task 7), `App\Models\*`, `App\Services\*`, `App\Enums\*` (Tasks 1-4)
- Produces: `App\Filament\Admin\ServiceDeskPlugin` — wired into `AdminPanelProvider` in Task 11.

- [ ] **Step 1: Copy**

```bash
cd /home/ict/dev/testkit
cp -r vendor/jeffersongoncalves/filament-service-desk/src/Admin/Resources/. app/Filament/Admin/Resources/
cp -r vendor/jeffersongoncalves/filament-service-desk/src/Admin/Widgets/. app/Filament/Admin/Widgets/
cp vendor/jeffersongoncalves/filament-service-desk/src/ServiceDeskAdminPlugin.php app/Filament/Admin/ServiceDeskPlugin.php
```

- [ ] **Step 2: Rewrite namespaces (same 15-rule chain as Task 7)**

```bash
cd /home/ict/dev/testkit
find app/Filament/Admin -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAdminPlugin/App\\Filament\\Admin\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAgentPlugin/App\\Filament\\Agent\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskUserPlugin/App\\Filament\\App\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Concerns/App\\Filament\\Concerns/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Admin/App\\Filament\\Admin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Agent/App\\Filament\\Agent/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\User/App\\Filament\\App/g' \
  {} +
```

- [ ] **Step 3: Verify**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Filament/Admin --no-progress
```
Fix any reported errors (most likely a missed reference to a class from a directory this task doesn't own — check it against the mapping table in the spec) before continuing.

- [ ] **Step 4: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Filament/Admin
git commit -m "$(cat <<'EOF'
Bring in filament-service-desk Admin resources, widgets, and plugin

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 9: Agent panel pages, resources, widgets, and plugin

**Files:**
- Create: `app/Filament/Agent/Pages/*` (from `.../src/Agent/Pages/`, includes `TicketQueuePage.php` — the "Claim" button)
- Create: `app/Filament/Agent/Resources/*` (2 resource directories, from `.../src/Agent/Resources/`)
- Create: `app/Filament/Agent/Widgets/*` (from `.../src/Agent/Widgets/`)
- Create: `app/Filament/Agent/ServiceDeskPlugin.php` (from `.../src/ServiceDeskAgentPlugin.php`)

**Interfaces:**
- Consumes: `App\Filament\Concerns\HasServiceDeskPluginConfig` (Task 7), `App\Models\Ticket`, `App\Services\TicketService`, `App\Enums\{TicketStatus,TicketPriority}` (Tasks 1-4)
- Produces: `App\Filament\Agent\ServiceDeskPlugin` — wired into `AgentPanelProvider` in Task 11.

- [ ] **Step 1: Copy**

```bash
cd /home/ict/dev/testkit
cp -r vendor/jeffersongoncalves/filament-service-desk/src/Agent/Pages/. app/Filament/Agent/Pages/
cp -r vendor/jeffersongoncalves/filament-service-desk/src/Agent/Resources/. app/Filament/Agent/Resources/
cp -r vendor/jeffersongoncalves/filament-service-desk/src/Agent/Widgets/. app/Filament/Agent/Widgets/
cp vendor/jeffersongoncalves/filament-service-desk/src/ServiceDeskAgentPlugin.php app/Filament/Agent/ServiceDeskPlugin.php
```

- [ ] **Step 2: Rewrite namespaces (same 15-rule chain — paste the full chain from Task 8 Step 2, targeting `app/Filament/Agent` instead of `app/Filament/Admin`)**

```bash
cd /home/ict/dev/testkit
find app/Filament/Agent -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAdminPlugin/App\\Filament\\Admin\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAgentPlugin/App\\Filament\\Agent\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskUserPlugin/App\\Filament\\App\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Concerns/App\\Filament\\Concerns/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Admin/App\\Filament\\Admin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Agent/App\\Filament\\Agent/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\User/App\\Filament\\App/g' \
  {} +
```

- [ ] **Step 3: Verify — specifically confirm the Claim action still calls the fixed `TicketService`**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Filament/Agent --no-progress
grep -n "TicketService::class" app/Filament/Agent/Pages/TicketQueuePage.php
```
Expected: `app(TicketService::class)` resolves to `App\Services\TicketService` (check the `use` statement at the top of the file resolved to `App\Services\TicketService`, not the old vendor namespace) — this is the exact "claim" action that started the whole investigation.

- [ ] **Step 4: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Filament/Agent
git commit -m "$(cat <<'EOF'
Bring in filament-service-desk Agent pages, resources, widgets, and plugin

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 10: User (App) panel pages, resources, widgets, and plugin

**Files:**
- Create: `app/Filament/App/Pages/*` (from `.../src/User/Pages/`)
- Create: `app/Filament/App/Resources/*` (2 resource directories, from `.../src/User/Resources/`)
- Create: `app/Filament/App/Widgets/*` (from `.../src/User/Widgets/`)
- Create: `app/Filament/App/ServiceDeskPlugin.php` (from `.../src/ServiceDeskUserPlugin.php`)

**Interfaces:**
- Consumes: `App\Filament\Concerns\HasServiceDeskPluginConfig` (Task 7), `App\Models\*`, `App\Services\TicketService` (Tasks 1-4)
- Produces: `App\Filament\App\ServiceDeskPlugin` — wired into `AppPanelProvider` in Task 11. (The vendor package calls this the "User" plugin; this app's panel is named `App`, confirmed via `AppPanelProvider.php` currently registering `ServiceDeskUserPlugin::make()`.)

- [ ] **Step 1: Copy**

```bash
cd /home/ict/dev/testkit
cp -r vendor/jeffersongoncalves/filament-service-desk/src/User/Pages/. app/Filament/App/Pages/
cp -r vendor/jeffersongoncalves/filament-service-desk/src/User/Resources/. app/Filament/App/Resources/
cp -r vendor/jeffersongoncalves/filament-service-desk/src/User/Widgets/. app/Filament/App/Widgets/
cp vendor/jeffersongoncalves/filament-service-desk/src/ServiceDeskUserPlugin.php app/Filament/App/ServiceDeskPlugin.php
```

- [ ] **Step 2: Rewrite namespaces (same 15-rule chain, targeting `app/Filament/App`)**

```bash
cd /home/ict/dev/testkit
find app/Filament/App -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Database\\Factories/Database\\Factories/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Controllers/App\\Http\\Controllers\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Http\\Middleware/App\\Http\\Middleware/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Mail/App\\Mail\\ServiceDesk/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Commands/App\\Console\\Commands/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\Facades/App\\Support/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk\\ServiceDeskManager/App\\Support\\ServiceDeskManager/g' \
  -e 's/JeffersonGoncalves\\ServiceDesk/App/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAdminPlugin/App\\Filament\\Admin\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAgentPlugin/App\\Filament\\Agent\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskUserPlugin/App\\Filament\\App\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Concerns/App\\Filament\\Concerns/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Admin/App\\Filament\\Admin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Agent/App\\Filament\\Agent/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\User/App\\Filament\\App/g' \
  {} +
```

**Important check specific to this task:** the vendor source's own namespace segment is `User` (e.g. `JeffersonGoncalves\FilamentServiceDesk\User\Pages\KnowledgeBase`), and rule `JeffersonGoncalves\\FilamentServiceDesk\\User` → `App\\Filament\\App` handles that. But if any copied file *also* references the app's own `App\Models\User` (the requester model — a completely different, unrelated `User`), confirm it wasn't double-substituted:

```bash
cd /home/ict/dev/testkit
grep -rn "App\\\\Filament\\\\App\\\\Models\\\\User\|Filament\\\\App\\\\User" app/Filament/App/
```
Expected: no output. If this finds something, the `User` rule over-matched a `Models\User` or similar reference — fix that specific file by hand to restore `App\Models\User`.

- [ ] **Step 3: Verify**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Filament/App --no-progress
```

- [ ] **Step 4: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Filament/App
git commit -m "$(cat <<'EOF'
Bring in filament-service-desk User-panel pages, resources, widgets, plugin

Vendor package calls this the "User" plugin; this app's matching panel is
named App (AppPanelProvider), so classes land under App\Filament\App.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 11: Cut over — PanelProviders and `config/filament-service-desk.php`

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Providers/Filament/AgentPanelProvider.php`
- Modify: `app/Providers/Filament/AppPanelProvider.php`
- Modify: `config/filament-service-desk.php`
- Modify: `database/seeders/ServiceDeskSeeder.php`

**Interfaces:**
- Consumes: `App\Filament\{Admin,Agent,App}\ServiceDeskPlugin` (Tasks 8-10), `App\Models\*` (Task 2)
- Produces: this is the live cut-over — after this task, the running app's three Filament panels serve ticket UI entirely from `App\*` classes, and no live code path still calls into the vendor packages.

- [ ] **Step 1: Rewrite the three PanelProviders' plugin usage**

```bash
cd /home/ict/dev/testkit
sed -i \
  -e 's/use JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAdminPlugin;/use App\\Filament\\Admin\\ServiceDeskPlugin;/' \
  -e 's/ServiceDeskAdminPlugin::make()/ServiceDeskPlugin::make()/' \
  app/Providers/Filament/AdminPanelProvider.php

sed -i \
  -e 's/use JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAgentPlugin;/use App\\Filament\\Agent\\ServiceDeskPlugin;/' \
  -e 's/ServiceDeskAgentPlugin::make()/ServiceDeskPlugin::make()/' \
  app/Providers/Filament/AgentPanelProvider.php

sed -i \
  -e 's/use JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskUserPlugin;/use App\\Filament\\App\\ServiceDeskPlugin;/' \
  -e 's/ServiceDeskUserPlugin::make()/ServiceDeskPlugin::make()/' \
  app/Providers/Filament/AppPanelProvider.php
```

- [ ] **Step 2: Confirm no leftover vendor references in the three providers**

```bash
cd /home/ict/dev/testkit
grep -n "JeffersonGoncalves" app/Providers/Filament/AdminPanelProvider.php app/Providers/Filament/AgentPanelProvider.php app/Providers/Filament/AppPanelProvider.php
```
Expected: no output.

- [ ] **Step 3: Rewrite `config/filament-service-desk.php`**

```bash
cd /home/ict/dev/testkit
sed -i \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAdminPlugin/App\\Filament\\Admin\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskAgentPlugin/App\\Filament\\Agent\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\ServiceDeskUserPlugin/App\\Filament\\App\\ServiceDeskPlugin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Admin/App\\Filament\\Admin/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\Agent/App\\Filament\\Agent/g' \
  -e 's/JeffersonGoncalves\\FilamentServiceDesk\\User/App\\Filament\\App/g' \
  config/filament-service-desk.php
```

- [ ] **Step 4: Rewrite `database/seeders/ServiceDeskSeeder.php`**

```bash
cd /home/ict/dev/testkit
sed -i 's/JeffersonGoncalves\\ServiceDesk\\Models/App\\Models/g' database/seeders/ServiceDeskSeeder.php
grep -n "JeffersonGoncalves" database/seeders/ServiceDeskSeeder.php
```
Expected: no output after the sed runs.

- [ ] **Step 5: Verify the whole app boots on the new classes**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse app/Providers/Filament config/filament-service-desk.php database/seeders/ServiceDeskSeeder.php --no-progress
php artisan route:list --path=admin | head -20
php artisan route:list --path=agent | head -20
php artisan route:list --path=app | head -20
```
Expected: no phpstan errors; each panel's routes list resolves without a "class not found" fatal (Filament would throw immediately on `route:list` if a registered Resource/Page class was missing or misnamed).

- [ ] **Step 6: Run the full Feature suite (includes Task 6's tests, now exercised through the real cut-over)**

```bash
cd /home/ict/dev/testkit
php artisan test --compact
```
Expected: all pass, including `tests/Feature/ServiceDesk/*`.

- [ ] **Step 7: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add app/Providers/Filament config/filament-service-desk.php database/seeders/ServiceDeskSeeder.php
git commit -m "$(cat <<'EOF'
Cut Filament panels over to App\Filament\* service desk plugins

All three panels (Admin, Agent, App) now serve ticket UI from app-owned
classes. Nothing in the live request path still calls into either vendor
package after this commit — they are only removed from Composer in a
later task so this can be reverted cheaply if something surfaces.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 12: Translations, views, and compiled assets

**Files:**
- Create: `lang/service-desk/*` (from `vendor/jeffersongoncalves/laravel-service-desk/resources/lang/`)
- Create: `lang/filament-service-desk/*` (from `vendor/jeffersongoncalves/filament-service-desk/resources/lang/`)
- Create: `resources/views/vendor/filament-service-desk/*` (from `vendor/jeffersongoncalves/filament-service-desk/resources/views/`)
- Create: `resources/css/filament-service-desk.css` (from `vendor/jeffersongoncalves/filament-service-desk/resources/dist/filament-service-desk.css` — the package's one compiled/built CSS file; confirmed via `FilamentServiceDeskServiceProvider::packageBooted()`, which registers exactly this one file through `FilamentAsset::register([Css::make('filament-service-desk', __DIR__.'/../resources/dist/filament-service-desk.css')], 'jeffersongoncalves/filament-service-desk')` — there is no `.js` asset to bring over)

**Interfaces:**
- Consumes: nothing new — `App\Providers\ServiceDeskServiceProvider::boot()` (Task 5) already calls `loadTranslationsFrom`/`loadViewsFrom`/`FilamentAsset::register` pointing at these exact paths; this task only has to create the files those calls expect.
- Produces: every `__('service-desk::...')` and `__('filament-service-desk::...')` translation call, and every `view('filament-service-desk::...')` call, across all previously-copied files resolves without falling back to "missing translation" placeholders; the one Filament panel CSS file (`fi-sd-kb`, `fi-sd-kb-content`, etc. — knowledge base styling) loads instead of 404ing.

- [ ] **Step 1: Copy translations, views, and the one compiled CSS file**

```bash
cd /home/ict/dev/testkit
mkdir -p lang/service-desk lang/filament-service-desk resources/views/vendor/filament-service-desk resources/css
cp -r vendor/jeffersongoncalves/laravel-service-desk/resources/lang/. lang/service-desk/
cp -r vendor/jeffersongoncalves/filament-service-desk/resources/lang/. lang/filament-service-desk/
cp -r vendor/jeffersongoncalves/filament-service-desk/resources/views/. resources/views/vendor/filament-service-desk/
cp vendor/jeffersongoncalves/filament-service-desk/resources/dist/filament-service-desk.css resources/css/filament-service-desk.css
```

None of the copied Blade views or lang files contain `JeffersonGoncalves` namespace references (confirmed during planning — the Blade views are plain HTML/Blade with no PHP `use` statements referencing the vendor classes), so no `sed` rewrite is needed for this task.

- [ ] **Step 2: Confirm the paths match what Task 5's provider already expects**

```bash
cd /home/ict/dev/testkit
grep -n "loadTranslationsFrom\|loadViewsFrom\|Css::make" app/Providers/ServiceDeskServiceProvider.php
```
Expected output (paths, not necessarily this exact formatting):
```
$this->loadTranslationsFrom(lang_path('service-desk'), 'service-desk');
$this->loadTranslationsFrom(lang_path('filament-service-desk'), 'filament-service-desk');
$this->loadViewsFrom(resource_path('views/vendor/filament-service-desk'), 'filament-service-desk');
Css::make('filament-service-desk', resource_path('css/filament-service-desk.css')),
```
`lang_path('service-desk')` resolves to `lang/service-desk`, `lang_path('filament-service-desk')` to `lang/filament-service-desk`, `resource_path('views/vendor/filament-service-desk')` to `resources/views/vendor/filament-service-desk`, `resource_path('css/filament-service-desk.css')` to `resources/css/filament-service-desk.css` — all four match Step 1's destinations exactly. If they don't (e.g. Task 5 was implemented with different path strings), fix Task 5's provider file to match Step 1's paths, don't change Step 1's paths to match a typo.

- [ ] **Step 3: Verify translations and views resolve, and a full panel page renders**

```bash
cd /home/ict/dev/testkit
php artisan tinker --execute='echo __("service-desk::notifications.ticket_created.subject");'
php artisan tinker --execute='echo view()->exists("filament-service-desk::pages.agent.ticket-queue") ? "view found" : "MISSING";'
```
Expected: the first prints the actual translated subject line (not the raw key echoed back), the second prints "view found". Per CLAUDE.md, this one-off tinker check is acceptable here specifically because no existing test asserts on translation-key resolution or view registration — it is not covered by Task 6's tests, which only assert on `toMail()->render()` content (which happens to already prove translations resolve for the 4 notifications it covers, but not the Filament view namespace).

Then run the full suite once more to make sure nothing regressed:
```bash
php artisan test --compact
```

- [ ] **Step 4: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add lang/service-desk lang/filament-service-desk resources/views/vendor/filament-service-desk resources/css/filament-service-desk.css
git commit -m "$(cat <<'EOF'
Own the service-desk translations, views, and compiled panel CSS

Re-registered under the same service-desk / filament-service-desk
namespaces from ServiceDeskServiceProvider, so none of the existing
__('service-desk::...') / view('filament-service-desk::...') call sites
needed to change.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 13: Remove the two Composer packages

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock` (regenerated by Composer, not hand-edited)

**Interfaces:**
- Consumes: everything from Tasks 1-12 must already be in place and passing tests — this task proves the app has zero remaining dependency on either vendor package.

- [ ] **Step 1: Final pre-removal check — nothing in `app/`, `config/`, `database/`, `routes/` still references either vendor namespace**

```bash
cd /home/ict/dev/testkit
grep -rn "JeffersonGoncalves\\\\ServiceDesk\|JeffersonGoncalves\\\\FilamentServiceDesk" app config database routes bootstrap
```
Expected: no output. If anything remains, fix it before proceeding — do not remove the package with a known dangling reference.

- [ ] **Step 2: Remove the package**

```bash
cd /home/ict/dev/testkit
composer remove jeffersongoncalves/filament-service-desk
```

- [ ] **Step 3: Confirm the transitive packages went with it**

```bash
cd /home/ict/dev/testkit
composer show --direct | grep -i jeffersongoncalves
ls vendor/jeffersongoncalves/ 2>/dev/null
composer show spatie/laravel-package-tools 2>&1
```
Expected: `composer show --direct` no longer lists `filament-service-desk`; `ls vendor/jeffersongoncalves/` either doesn't exist or no longer contains `laravel-service-desk`/`filament-service-desk`; `spatie/laravel-package-tools` reports "not installed" (or similar) since nothing else in `composer.lock` should require it — if something else does turn out to require it, that's fine, leave it installed, just confirm it's not still tied to the two removed packages.

- [ ] **Step 4: Full clean-slate verification**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --test --format agent
php artisan test --compact
```
Expected: phpstan clean, pint reports nothing to fix (Tasks 1-12 already ran `pint --dirty` after every change), full test suite passes.

- [ ] **Step 5: Commit**

```bash
cd /home/ict/dev/testkit
git add composer.json composer.lock
git commit -m "$(cat <<'EOF'
Remove jeffersongoncalves/filament-service-desk and its transitive deps

The service desk ticketing/notification/Filament-UI code is now fully
owned by this app under App\*. composer show --direct confirms neither
this package nor jeffersongoncalves/laravel-service-desk nor
spatie/laravel-package-tools remain.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01Fu8a8uYAcBYws7of2G1Yiy
EOF
)"
```

---

## Task 14: Manual smoke test (not automatable — do this in the browser)

**Files:** none — verification only.

- [ ] **Step 1: Start the app**

```bash
cd /home/ict/dev/testkit
composer run dev
```

- [ ] **Step 2: Create a ticket as a user**

Log into the `App` panel as a seeded `User`, create a new ticket. Confirm a job appears in the `jobs` table (or is processed immediately by the `queue:listen` process already running under `composer run dev`) and **not** in `failed_jobs`.

```bash
cd /home/ict/dev/testkit
php artisan tinker --execute='echo \DB::table("failed_jobs")->count();'
```
Expected: `0` (or unchanged from before this step, if there were pre-existing unrelated failures — the point is it does not increase).

- [ ] **Step 3: Claim it as an agent**

Log into the `Agent` panel as a seeded `Operator`, go to the Ticket Queue page, click "Claim" on the ticket created in Step 2. Confirm again `failed_jobs` count did not increase, and check the mail log (or Mailpit/log driver, whichever this app's `MAIL_MAILER` is set to) for a rendered "Ticket Assigned" email with a valid `Message-ID` header.

- [ ] **Step 4: Add a comment, then close the ticket**

As the agent, add a public reply comment, then change the ticket status to closed. Confirm the requester's mail log shows both the new-comment and status-changed emails, `failed_jobs` count unchanged.

```bash
cd /home/ict/dev/testkit
php artisan tinker --execute='echo \DB::table("failed_jobs")->count();'
```

- [ ] **Step 5: Report results**

This task has no commit — it's a verification checklist. If all four checks pass, the migration is functionally complete. If any step still fails, treat it as a bug in whichever Task 1-13 copied the affected code and go fix that task's output directly (do not patch around it here).

---

## Task 15: Fix duplicate event-listener registration (found during Task 14's live smoke test)

**Added after initial plan execution.** A live, non-mocked end-to-end check (creating a real ticket via `TicketService`, assigning it, then processing the real queue with `php artisan queue:work`) revealed that every listener in `app/Listeners/*.php` fires TWICE per event. Confirmed via `php artisan event:list`: e.g. `App\Events\TicketCreated` lists both `App\Listeners\SendTicketCreatedNotification (ShouldQueue)` (Laravel's automatic event discovery, which scans `app/Listeners/*.php` for public single-class-typed-parameter methods and auto-registers them — active by default in this Laravel version) AND `App\Listeners\SendTicketCreatedNotification@handle (ShouldQueue)` (Task 5's explicit `Event::listen()` call). This is a genuine regression introduced by this migration: the vendor package's listeners lived in `vendor/.../src/Listeners/`, a path Laravel's discovery never scans, so only explicit registration ever fired them. Moving them into the Laravel-idiomatic `app/Listeners/` (Task 3's deliberate choice) made them newly eligible for auto-discovery too, on top of the still-present explicit registration.

None of Task 6's tests caught this (`Event::fake()`, `Notification::fake()`, and direct-instantiation tests all bypass or don't distinguish this).

**Files:**
- Modify: `bootstrap/app.php`

**Interfaces:**
- Consumes: nothing new
- Produces: each event fires each of its listeners exactly once again, while preserving `config('service-desk.register_default_listeners')`'s ability to disable the explicit registrations (ruling out the alternative fix of deleting the explicit `Event::listen()` calls and relying on auto-discovery alone, which would silently break that toggle).

- [ ] **Step 1: Reproduce and confirm the exact scope of the bug**

```bash
cd /home/ict/dev/testkit
php artisan event:list | grep -B1 -A5 "TicketCreated \.\.\.\|TicketAssigned \.\.\.\|TicketStatusChanged \.\.\.\|CommentAdded \.\.\.\|InboundEmailReceived \.\.\."
```
Expected (the bug): each of these events lists its `App\Listeners\*` entries twice — once with `@handle` (or `@handleXxx` for `LogTicketHistory`, the subscriber) and once without a method suffix.

- [ ] **Step 2: Disable Laravel's automatic event discovery**

Edit `bootstrap/app.php`:
```diff
 use Illuminate\Foundation\Application;
 use Illuminate\Foundation\Configuration\Exceptions;
 use Illuminate\Foundation\Configuration\Middleware;

 return Application::configure(basePath: dirname(__DIR__))
     ->withRouting(
         web: __DIR__.'/../routes/web.php',
         commands: __DIR__.'/../routes/console.php',
         health: '/up',
     )
+    ->withEvents(discover: false)
     ->withMiddleware(function (Middleware $middleware) {
         //
     })
```

- [ ] **Step 3: Verify the fix**

```bash
cd /home/ict/dev/testkit
composer dump-autoload -o
php artisan event:list | grep -B1 -A3 "TicketCreated \.\.\.\|TicketAssigned \.\.\.\|TicketStatusChanged \.\.\.\|CommentAdded \.\.\.\|InboundEmailReceived \.\.\."
```
Expected: each `App\Listeners\*` entry now appears exactly once per event (the `@handle`/`@handleXxx`-suffixed, explicitly-registered form only — the unsuffixed auto-discovered duplicate is gone).

- [ ] **Step 4: Live re-verification — the same real end-to-end check that found the bug**

```bash
cd /home/ict/dev/testkit
php artisan tinker --execute='
$dept = \App\Models\Department::factory()->create();
$user = \App\Models\User::factory()->create();
$operator = \App\Models\Operator::factory()->create();
$ticket = app(\App\Services\TicketService::class)->create([
    "department_id" => $dept->id,
    "title" => "Task 15 fix verification",
    "description" => "Confirms each listener fires exactly once after disabling event auto-discovery.",
], $user);
app(\App\Services\TicketService::class)->assign($ticket, $operator, $operator);
echo "jobs queued: " . \DB::table("jobs")->count() . "\n";
'
php artisan queue:work --stop-when-empty --tries=1 -v
```
Expected: exactly 2 outer listener jobs queued (1x `SendTicketCreatedNotification`, 1x `SendTicketAssignedNotification` — not 4), each producing exactly 1 inner notification-send job (2 total send attempts, not 4). The sends will still fail with the same unrelated `mailpit` DNS-resolution `TransportException` in this environment — that's expected and not this task's concern (it's an environment/mail-config issue, not a code defect; see the ledger). What matters is the *count*: 2 attempts, not 4.

Clean up the test data afterward:
```bash
php artisan tinker --execute='
\DB::table("failed_jobs")->truncate();
\App\Models\Ticket::where("title", "Task 15 fix verification")->get()->each(fn ($t) => $t->forceDelete());
'
```

- [ ] **Step 5: Run the full test suite**

```bash
cd /home/ict/dev/testkit
php artisan test --compact
```
Expected: same as every prior task — all ServiceDesk tests pass, plus the one known pre-existing unrelated `ExampleTest` failure. (Task 6's tests use `Event::fake()`/`Notification::fake()`/direct instantiation, so they weren't sensitive to this bug and shouldn't change behavior now — this step just confirms disabling discovery didn't break anything else.)

- [ ] **Step 6: Format and commit**

```bash
cd /home/ict/dev/testkit
vendor/bin/pint --dirty --format agent
git add bootstrap/app.php
git commit -m "$(cat <<'EOF'
Disable Laravel event auto-discovery to fix duplicate listener firing

Every listener in app/Listeners/*.php was registered twice: once via
ServiceDeskServiceProvider's explicit Event::listen()/Event::subscribe()
calls, and again via Laravel's automatic event discovery (default-enabled,
scans app/Listeners/*.php for public single-class-typed-parameter methods).
The vendor package's listeners previously lived outside app/, where
discovery never looked, so only explicit registration ever fired them;
moving them into app/Listeners (Task 3) made them newly eligible for
auto-discovery too, causing every ticket notification to be queued and
sent twice. Found via a live, non-mocked end-to-end smoke test (event-fake
and notification-fake based tests don't distinguish "sent once" from
"sent twice").

Disabling discovery (rather than deleting the explicit registrations)
preserves the service-desk.register_default_listeners config toggle,
which gates the explicit path.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01T2BixwMGpdG3DcDidTnZHa
EOF
)"
```
