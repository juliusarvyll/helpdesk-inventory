---
paths:
  - 'app/Listeners/**'
---

# Listeners

## Event auto-discovery is disabled app-wide
`bootstrap/app.php` calls `->withEvents(discover: false)`. This was added because Laravel's automatic event discovery was double-registering every `app/Listeners/*.php` class on top of the explicit registrations in `App\Providers\ServiceDeskServiceProvider::boot()`, causing each event to fire its listeners (and each notification to send) twice.

Because of this, a new listener class dropped into `app/Listeners/*.php` will NOT fire automatically — it needs an explicit `Event::listen(SomeEvent::class, SomeListener::class)` (or `Event::subscribe(...)` for subscriber classes) added to a service provider's `boot()` method (see `ServiceDeskServiceProvider::boot()` for the existing pattern). Forgetting this means the listener silently never runs — no error, no exception, it just never fires.
