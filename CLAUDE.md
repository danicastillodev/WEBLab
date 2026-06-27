# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**NETLab** — a veterinary clinical laboratory management system (Sistema de Gestión de Diagnósticos Clínicos). The domain is in Spanish. The central entity is `HistoriaClinica` (clinical record), which ties together a `Propietario` (owner), `Direccion` (address), `Especie` (species), `Raza` (breed), and ultimately `Analisis` (lab analyses) and `Diagnostico` (diagnosis). `netlab_class_diagram.html` and `netlab_schema.sql` in the project root document the original domain model — use them as the authoritative reference for entity names and relationships.

## Stack

- **Backend:** Laravel 13 (PHP 8.3), Eloquent ORM, Spatie Laravel Permission for roles/permissions
- **Frontend:** React 18 + Inertia.js (no API layer — controllers render Inertia responses directly), Tailwind CSS v3, Ziggy for named routes in JS (`route('name')`)
- **Build:** Vite 7
- **Auth:** Laravel Breeze (session-based)
- **DB:** MariaDB 11 in Docker, SQLite in-memory for tests

## Commands

```bash
# First-time setup
composer run setup

# Dev server (PHP + queue + logs + Vite, all via concurrently)
composer run dev

# Run all tests
composer run test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# PHP linting
./vendor/bin/pint

# Frontend build
npm run build
```

Docker is available (`docker-compose.yml`) with services: `app` (PHP-FPM), `nginx` (port 8000), `db` (MariaDB, port 3306), `node` (Vite, port 5173).

## Architecture

### Request flow

Browser → `routes/web.php` → Controller → `Inertia::render('PageName', $props)` → React page component receives props directly as function arguments.

All routes require auth middleware. Controllers return either `Inertia\Response` (renders a page) or `Illuminate\Http\RedirectResponse` (after mutations). Flash messages use `->with('success', '...')` and are read in the frontend via `usePage().props.flash`.

### Frontend page resolution

Inertia resolves page names from `resources/js/Pages/`. The string passed to `Inertia::render()` maps directly to a `.jsx` file:
- `'HistoriasClinicas/Create'` → `resources/js/Pages/HistoriasClinicas/Create.jsx`
- `'Catalogos/Pruebas/Index'` → `resources/js/Pages/Catalogos/Pruebas/Index.jsx`

### Layout

All authenticated pages wrap in `<AuthenticatedLayout header={...}>`. The layout is a sidebar with collapsible nav groups: **Historias Clínicas**, **Catálogos**, **Administración**. When adding a new section, update the sidebar in `resources/js/Layouts/AuthenticatedLayout.jsx`.

### Tailwind custom tokens

- `bg-brand` / `hover:bg-brand-hover` → indigo (`#4f46e5` / `#4338ca`) — used for primary action buttons and active nav state.

### Inline modals for related record creation

The `HistoriasClinicas/Create.jsx` pattern (and its siblings) use inline `Modal` components + `axios.post()` to create related records (propietario, dirección) without leaving the page, then update local React state to reflect the new option. This avoids a full page reload and keeps the user's form state intact.

### Catálogos

Simple lookup tables (Estados, Municipios, Especies, Razas, Pruebas, Propietarios) live under `resources/js/Pages/Catalogos/` and follow a straightforward Index/Create/Edit pattern with `useForm` from `@inertiajs/react` and `router.delete` for deletions.

### Permissions

`spatie/laravel-permission` is installed. Roles/permissions are managed via `RoleController` and `UserController`. The `User` model uses the `HasRoles` trait.
