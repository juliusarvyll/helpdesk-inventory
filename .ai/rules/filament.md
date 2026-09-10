---
paths:
  - 'app/Providers/Filament/**'
---

# Filament

## Don't discoverResources/Pages/Widgets over ServiceDeskPlugin-managed directories
The ServiceDesk Filament plugins (`app/Filament/{Admin,Agent,App}/ServiceDeskPlugin.php`) explicitly and conditionally register their own resources/pages/widgets, gated by feature toggles (`->sla()`, `->knowledgeBase()`, `->emailChannels()`, `->serviceCatalog()`) and by `config/filament-service-desk.php`'s per-resource override map.

If a PanelProvider's `discoverResources()`/`discoverPages()`/`discoverWidgets()` scans a directory the plugin also manages, auto-discovery bypasses both the toggles and the override map (a toggled-off resource still gets registered; a config override produces a duplicate registration instead of a clean swap).

Current fix: `AgentPanelProvider` and `AppPanelProvider` have no `discoverResources/Pages/Widgets` calls at all — their entire scope is ServiceDeskPlugin-managed. `AdminPanelProvider` narrows `discoverResources` to scan `Resources/{Admins,Users,Operators}` individually (its own pre-existing resources) and has no `discoverWidgets` call (all Admin widgets are ServiceDesk-managed). When adding a new non-ServiceDesk resource/page/widget to a panel, either add it to one of these narrowed discover calls (Admin) or register it explicitly (Agent/App), and when adding a new ServiceDesk resource/page/widget, register it in the relevant `ServiceDeskPlugin::register()`, not by widening a PanelProvider's discover scope.
