# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Go (the board game) learning and playing platform built with Laravel 12 + Inertia.js + Vue 3 + TypeScript. The application integrates with KataGo AI engine for game analysis and AI opponents.

## Commands

### Development
```bash
composer dev              # Start all services: Laravel server, queue, logs, Vite
composer dev:ssr          # Development with SSR enabled
```

### Testing
```bash
composer test             # Run Pint linter + PHPUnit tests
php artisan test          # Run just PHPUnit/Pest tests
php artisan test --filter=TestName  # Run specific test
```

### Linting & Formatting
```bash
composer lint             # PHP: Run Pint formatter
composer test:lint        # PHP: Check Pint without fixing
npm run lint              # JS/Vue: ESLint with auto-fix
npm run format            # JS/Vue: Prettier formatting
npm run format:check      # JS/Vue: Check Prettier without fixing
```

### Build
```bash
npm run build             # Build frontend for production
npm run build:ssr         # Build with SSR support
```

### Database
```bash
php artisan migrate       # Run migrations (SQLite by default)
```

## Architecture

### Backend (Laravel)
- **Controllers**: `app/Http/Controllers/` - Handle HTTP requests
  - `GoAIController` - AI move generation via KataGo
  - `GoAnalysisController` - Position analysis via KataGo
  - `GoGameController` - Game CRUD and history
  - `AnalysisStudyController` - Saved analysis studies
  - `LessonController` - Go learning lessons
- **Models**: `app/Models/` - Eloquent models (GoGame, AnalysisStudy, Lesson, LessonProgress, User)
- **Services**: `app/Services/KataGoService.php` - KataGo GTP communication
- **Policies**: `app/Policies/` - Authorization policies

### Frontend (Vue 3 + TypeScript)
- **Pages**: `resources/js/pages/` - Inertia page components
  - `go/` - Main game pages (Play, Analysis, History, Learn, etc.)
- **Components**: `resources/js/components/`
  - `go/` - Game-specific components (GoBoard, GoStone, analysis panels)
  - `ui/` - shadcn/ui components (auto-generated, not linted)
- **Composables**: `resources/js/composables/go/` - Game state and logic
  - `useGoGame.ts` - Core game state management
  - `useGoAI.ts` - AI move requests
  - `useGoAnalysis.ts` - Position analysis
  - `useAnalysisTree.ts` - Analysis variation tree
  - `useGameSave.ts` - Game persistence
- **Types**: `resources/js/types/` - TypeScript interfaces for Go game concepts

### Routes
- **Web**: `routes/web.php` - Inertia page routes
- **API**: `routes/api.php` - JSON API endpoints (AI moves, analysis)

### KataGo Integration
- Config: `config/katago.php`
- Requires `KATAGO_BINARY`, `KATAGO_MODEL`, `KATAGO_CONFIG` environment variables
- Communicates via GTP protocol for AI moves and position analysis

## Key Conventions

### PHP
- Uses Laravel Pint with `laravel` preset for code style
- Pest for testing (not PHPUnit directly)
- SQLite database by default

### TypeScript/Vue
- ESLint with Vue/TypeScript configs
- Prettier with 4-space indentation, single quotes
- Import order: builtin → external → internal → parent → sibling → index
- UI components in `components/ui/` are excluded from linting (shadcn/ui)
- Tailwind CSS v4 with `clsx`, `cn`, `cva` utility functions
