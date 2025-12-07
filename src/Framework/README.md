# Framework Configuration Directory

This directory contains **Symfony-specific configuration** for all Bounded Contexts, completely separated from domain logic and UI components.

## 🎯 Purpose

All framework configuration files (`services.yaml`, `routes.yaml`) are centralized here, organized by Bounded Context, to maintain a clear separation of concerns in our hexagonal architecture.

## 📁 Structure

```
Framework/
├── Analytics/
│   ├── services.yaml        # DI configuration
│   └── routes.yaml          # Routing configuration
├── Streaming/
├── ContentManagement/
│   ├── Series/
│   ├── Playlist/
│   ├── Taxonomy/
│   └── MultimediaObject/
├── IdentityAndAccess/
├── MediaProcessing/
├── Dashboard/
└── Shared/
```

## ✅ What Goes Here

- **services.yaml**: Dependency Injection container configuration
  - Service definitions
  - Autowiring rules
  - Resource declarations
  - Repository bindings

- **routes.yaml**: HTTP routing configuration
  - URL patterns
  - Controller mappings
  - Route requirements

## ❌ What DOES NOT Go Here

- ❌ Controllers (→ `src/UI/Backoffice/{BC}/Controller/`)
- ❌ Domain logic (→ `src/{BC}/Domain/`)
- ❌ Application services (→ `src/{BC}/Application/`)
- ❌ Repositories (→ `src/{BC}/Infrastructure/`)
- ❌ Views/Templates (→ `src/UI/Backoffice/{BC}/Views/`)

## 🏗️ Architecture Principle

This separation follows the **Ports & Adapters (Hexagonal Architecture)** principle:

| Layer | Location | Purpose |
|-------|----------|---------|
| **Domain** | `src/{BC}/Domain/` | Business logic (framework-agnostic) |
| **Application** | `src/{BC}/Application/` | Use cases (framework-agnostic) |
| **Infrastructure** | `src/{BC}/Infrastructure/` | Technical implementations |
| **UI** | `src/UI/Backoffice/{BC}/` | Presentation layer |
| **Framework** | `src/Framework/{BC}/` | **Symfony configuration** ⭐ |

## 📝 Example: services.yaml

```yaml
# src/Framework/Analytics/services.yaml
services:
  # UI Components (from src/UI/Backoffice/)
  App\UI\Backoffice\Analytics\Controller\:
    resource: '../../UI/Backoffice/Analytics/Controller'
    tags: ['controller.service_arguments']

  App\UI\Backoffice\Analytics\EventSubscriber\:
    resource: '../../UI/Backoffice/Analytics/EventSubscriber'
    tags: ['kernel.event_subscriber']
```

## 📝 Example: routes.yaml

```yaml
# src/Framework/Analytics/routes.yaml
analytics_index:
  path: /admin/backend/analytics
  controller: App\UI\Backoffice\Analytics\Controller\IndexController
```

## 🔗 Integration

These configuration files are imported in the main Symfony configuration:

```yaml
# config/services.yaml
imports:
  - { resource: '../src/Framework/Analytics/services.yaml' }
  - { resource: '../src/Framework/Streaming/services.yaml' }
  # ...

# config/routes.yaml
analytics_routes:
  resource: ../src/Framework/Analytics/routes.yaml
```

## ✅ Benefits

1. **Clear Separation**: Framework config is isolated from business logic
2. **Easy to Find**: All Symfony configuration in one place
3. **Maintainable**: Easy to add/modify/remove BCs
4. **Testable**: Domain and Application layers remain framework-agnostic
5. **Scalable**: Adding new BCs is straightforward

## 📚 Related Documentation

- **Architecture Guide**: `/.github/architecture.md`
- **Naming Conventions**: `/.github/naming-conventions.md`
- **UI Components**: `/.github/ui-components.md`

---

**Remember:** This directory contains **infrastructure configuration only**. All business logic must reside in the appropriate Bounded Context under `src/{BC}/`.

