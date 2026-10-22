# Frontend

Das Frontend ist eine Vue-3-/TypeScript-Anwendung, die durch Laravel Inertia-Seiten rendert. Laravel-Web-Routen bestimmen die Seitenkomponente; ein separater Vue Router ist nicht installiert.

## Einstieg und Struktur

| Pfad | Inhalt |
|---|---|
| [resources/js/app.ts](../resources/js/app.ts) | Initialisiert Inertia, Titel, Layoutwahl, Theme und Flash-Toasts |
| [resources/js/pages](../resources/js/pages) | Inertia-Seiten für Home, Auth, Teams, Pläne, Admin und Einstellungen |
| [resources/js/components](../resources/js/components) | Shared- und Fachbereichskomponenten, darunter `Auth`, `Plans`, `Teams`, `Settings` und `ui` |
| [resources/js/layouts](../resources/js/layouts) | App-, Auth-, Kontakt- und Settings-Layouts |
| [resources/js/composables](../resources/js/composables) | Composition-API-Helfer für Auth, Theme, Profile, Spiel-/Hallenplan und Navigation |
| [resources/js/lib](../resources/js/lib) | Kleine UI-Utilities und Flash-Toasts |
| [resources/js/types](../resources/js/types) | Typescript-Typen für globale Props, Auth, Teams und UI |
| [resources/css/app.css](../resources/css/app.css) | Tailwind-CSS-Einstieg, Design-Tokens und Dark-Mode-Varianten |
| [resources/views/app.blade.php](../resources/views/app.blade.php) | HTML-Root-View mit Vite- und Inertia-Einbindung |

## Frontend-Pakete

| Paket / Paketgruppe | Zweck |
|---|---|
| `vue`, `typescript`, `vue-tsc` | UI-Framework, Sprache und Typprüfung |
| `@inertiajs/vue3`, `@inertiajs/vite`, `laravel-vite-plugin` | Inertia-Vue-Adapter und Laravel/Vite-Integration |
| `@vitejs/plugin-vue`, `vite` | Vue-Compilerintegration und Asset-Build |
| `@tailwindcss/vite`, `tailwindcss`, `tw-animate-css` | Tailwind-CSS-Integration und Animationen |
| `@laravel/vite-plugin-wayfinder` | Vite-/Wayfinder-Integration für typisierte Routen |
| `@laravel/passkeys` | Clientseitige Passkey-/WebAuthn-Flows |
| `@lucide/vue` | Icon-Komponenten |
| `reka-ui`, `vue-input-otp`, `vue-sonner` | Headless UI-Grundbausteine, OTP-Eingabe und Toast-Anzeige |
| `@vueuse/core` | Wiederverwendbare Vue Composition-Utilities |
| `class-variance-authority`, `clsx`, `tailwind-merge` | Klassenvarianten, bedingte Klassen und Tailwind-Klassen-Merging |
| `fontaine` | Font-Metriken-/Font-Optimierungsintegration im Vite-Ökosystem |
| `eslint`, Vue-/TypeScript-/Import-Plugins | JavaScript-, Vue- und TypeScript-Linting |
| `prettier`, `prettier-plugin-tailwindcss`, `eslint-config-prettier` | Formatierung und ESLint/Prettier-Kompatibilität |
| `concurrently` | Mehrere lokale Prozesse parallel starten |

### Seitenübersicht

- Allgemein: `Welcome.vue`, `Startseite.vue`, `MeinTeam.vue`, `Profil.vue`, `NoClub.vue`, `Spielplan.vue`, `Hallenplan.vue`.
- Auth: `pages/auth/` mit Login, Registrierung, Passwort-Reset, Verifizierung, 2FA, Passwortbestätigung und Member-Profilvervollständigung.
- Teams/Admin: `pages/teams/`, `pages/admin/members/Index.vue` und `pages/admin/teams/Index.vue`.
- Settings: `pages/settings/` mit Profil, Sicherheit, Benachrichtigungen und Darstellung.
- Öffentliche Informationen: `AboutUs.vue`, `Kontakt.vue`, `Impressum.vue`, `Datenschutzerklaerung.vue`.
- Fehler: `pages/errors/PermissionDenied.vue`.

## Routing, Props und Zustand

Serverseitige URLs und Autorisierung liegen in [routes/web.php](../routes/web.php) und [routes/settings.php](../routes/settings.php). Inertia liefert HTML beim ersten Besuch und seitenbezogene Props bei Navigation/Formularen; [HandleInertiaRequests.php](../app/Http/Middleware/HandleInertiaRequests.php) teilt Benutzer, Teams, Tenant und Benachrichtigungen standardmäßig.

Es gibt kein Pinia- oder Vuex-Paket und keinen `createRouter`-Aufruf. Seiten und Komponenten verwalten lokalen Zustand mit der Vue Composition API; gemeinsam benötigter Serverzustand kommt überwiegend als Inertia-Prop. Laravel Wayfinder stellt generierte, typisierte Routen-/Controller-Hilfen bereit; die erzeugten Dateien unter `resources/js/actions` und `resources/js/routes` sind ignoriert und werden für Deployments generiert.

## API-Kommunikation

Die normale Kommunikation läuft über Inertia und Laravel-Formulare. Die Suche liefert JSON. Für CSV-Importvorschauen verwendet `CsvImportDialog.vue` `fetch` mit `FormData`; die Vorschau wird anschließend im Frontend editiert und an eine Plan-Speicherroute übergeben. Es gibt keinen Axios-Client und keine allgemeine Frontend-API-Schicht.

## Styling und Komponenten

Tailwind CSS 4 wird über `@tailwindcss/vite` eingebunden. `app.css` definiert Design-Tokens und Dark-Mode-Farben. Die Komponentenbibliothek verwendet unter anderem Reka UI, Lucide, `class-variance-authority`, `clsx`, `tailwind-merge`, `vue-sonner` und `tw-animate-css`. Die konkreten Vue-/Build-Abhängigkeiten stehen in [package.json](../package.json).

## Build und Qualität

| Befehl | Wirkung |
|---|---|
| `npm run dev` | Lokaler Vite-Server mit HMR; unter Windows wird Node 20.20.2 vorausgesetzt |
| `npm run build` | Produktionsbundle |
| `npm run build:ssr` | Client- und SSR-Build |
| `npm run lint:check` | ESLint-Prüfung ohne Änderungen |
| `npm run format:check` | Prettier-Prüfung für `resources/` |
| `npm run types:check` | `vue-tsc --noEmit` |

Die genaue Vite-Konfiguration steht in [vite.config.ts](../vite.config.ts); lokale Details stehen unter [Setup](./setup.md).

## Tests

`package.json` definiert kein Frontend-Testskript und es ist kein Vitest/Jest-Runner konfiguriert. Die Frontend-Absicherung in CI besteht derzeit aus ESLint, Prettier-Prüfung und TypeScript-Check; fachliche HTTP-Flows werden durch Laravel/Pest-Featuretests geprüft.
