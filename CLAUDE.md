# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AssetHub is an enterprise-grade asset management system built on Symfony 7.0 with PHP 8.2+. It features a sophisticated modular architecture using Service Layer Pattern, CQRS, and Event-Driven Architecture with comprehensive audit trails and authorization systems.

## Common Development Commands

### Database Operations
```bash
# Database setup and migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Create new migration after entity changes
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Database maintenance (via AdminService)
php bin/console doctrine:database:drop --force  # Use with extreme caution
```

### Development Server
```bash
# Start development server
symfony server:start
# Or using PHP built-in server
php -S localhost:8000 -t public/
```

### Testing
```bash
# Run all tests
php bin/phpunit

# Run specific test groups
php bin/phpunit tests/Service/
php bin/phpunit tests/Controller/

# Run single test file
php bin/phpunit tests/Service/AuthorizationServiceTest.php

# Run with coverage (if configured)
php bin/phpunit --coverage-html coverage/
```

### Asset Management
```bash
# Install and build assets (Symfony way)
php bin/console assets:install public

# Clear cache
php bin/console cache:clear
php bin/console cache:warmup
```

### Code Generation
```bash
# Create new entity
php bin/console make:entity

# Create new controller
php bin/console make:controller

# Create new form
php bin/console make:form

# Create new service (manual - no maker command)
# Follow the Service Layer Pattern as shown in existing services
```

### Administrative Commands
```bash
# Create admin user
php bin/console app:create-admin

# Email cleanup (if EmailService command exists)
php bin/console app:email:cleanup 30 --dry-run
php bin/console app:email:cleanup 90 --force
```

## Architecture and Development Patterns

### Service Layer Architecture
All business logic resides in service classes, never in controllers. Controllers handle only HTTP concerns and delegate to services.

**Key Services:**
- `AuthorizationService` - Centralized authorization replacing legacy PermissionService
- `AuditService` - Comprehensive logging with multiple levels (INFO, ERROR, NOTICE)
- `EquipmentService` - Main business logic for equipment management  
- `AdminService` - System administration, backups, maintenance
- `EmailService` - Centralized email with automatic history tracking

**Pattern Example:**
```php
// Controller (thin layer)
public function create(Request $request): Response
{
    $user = $this->getUser();
    $this->authorizationService->checkPermission($user, 'equipment', 'CREATE', $request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $equipment = $this->equipmentService->createEquipment($data, $user);
        // Event dispatch, audit logging handled in service
        return $this->redirectToRoute('equipment_index');
    }
}

// Service (business logic)
public function createEquipment(array $data, User $user): Equipment
{
    // Validation, persistence, audit logging, events
    $this->auditService->logCrudOperation($user, 'Equipment', $id, 'CREATE', $data);
    return $equipment;
}
```

### CQRS Implementation  
The application separates Commands (write operations) and Queries (read operations):
- **Commands**: `CreateEquipmentCommand`, `UpdateUserCommand`
- **Queries**: `GetEquipmentQuery`, `SearchUsersQuery`
- **Handlers**: Dedicated handlers in service methods

### Event-Driven Architecture
Domain events with subscribers for loose coupling:
- **Events**: `EquipmentCreatedEvent`, `UserUpdatedEvent`, `SecurityEvent`
- **Subscribers**: Located in `EventSubscriber/` directories
- **Usage**: Audit trails, email notifications, business logic side effects

### Authorization System
**AuthorizationService** provides centralized, granular permissions:
```php
// Module access control
$this->authorizationService->checkModuleAccess($user, 'equipment', $request);

// Permission checks
$this->authorizationService->checkPermission($user, 'equipment', 'CREATE', $request);
$canEdit = $this->authorizationService->hasPermission($user, 'equipment', 'EDIT');

// Multi-permission checks
$canEditAny = $this->authorizationService->hasAnyPermission($user, 'equipment', ['EDIT', 'DELETE']);
```

### Audit System
**AuditService** provides comprehensive logging with multiple channels:
- **User Actions** (INFO): `$auditService->logUserAction($user, 'action', $data, $request)`
- **Security Events** (ERROR): `$auditService->logSecurityEvent('event', $user, $data, $request, 'high')`
- **Admin Actions** (NOTICE): `$auditService->logAdminAction($admin, 'action', $data, $request)`
- **Database Operations** (NOTICE/ERROR): `$auditService->logDatabaseOperation($user, 'backup', true, $data)`

## Modular Structure

### Main Modules
1. **Core** (`src/`) - Base entities, services, controllers
2. **Asekuracja** (`src/AsekuracyjnySPM/`) - Safety equipment management (COMPLETE)
3. **AparaturaPomiarowa** (`src/AparaturaPomiarowa/`) - Measurement equipment module

### Module Development Pattern
Each module follows this structure:
```
src/ModuleName/
├── Controller/          # HTTP layer, thin controllers
├── Entity/             # Doctrine entities
├── Repository/         # Data access with custom queries  
├── Service/           # Business logic layer
├── Form/              # Symfony forms
└── EventSubscriber/   # Domain event handlers
```

### Adding New Modules
1. Create module directory under `src/`
2. Add entities with proper relationships
3. Create business service following Service Layer Pattern
4. Create thin controllers that delegate to services
5. Add to module system via database (`modules` table)
6. Configure authorization roles (`roles` table)
7. Add menu entries in templates
8. Write comprehensive unit tests

## Database and Migrations

The application uses **MySQL 8.0+** with Doctrine ORM. Migration files follow chronological naming `Version{YYYYMMDDHHMMSS}.php`.

**Key Tables:**
- `users`, `roles`, `user_roles` - Authorization system
- `modules` - Module management
- `equipment`, `equipment_categories` - Core equipment
- `asekuracyjny_*` - Safety equipment module tables
- `aparatura_pomiarowa_*` - Measurement equipment tables
- `email_history` - Email tracking
- `dictionaries` - System-wide lookup tables

## Testing Architecture

Comprehensive test coverage with enterprise patterns:
```
tests/
├── Controller/     # Integration tests with mocked services
├── Service/        # Unit tests for business logic
└── Entity/        # Entity validation tests
```

**Test Patterns:**
- **Service Tests**: Mock dependencies, test business logic
- **Controller Tests**: WebTestCase with service mocks
- **Integration Tests**: Test service interactions
- **Coverage**: Aim for 100% service layer coverage

## Security and Best Practices

### Authorization Flow
1. **Authentication**: User login via SecurityController
2. **Module Access**: AuthorizationService checks module permissions
3. **Action Permissions**: Granular CREATE/READ/UPDATE/DELETE checks
4. **Audit Trail**: All actions logged via AuditService

### File Upload Security
- **Avatar uploads**: `public/uploads/avatars/` (JPG, PNG, GIF, WebP)
- **Equipment files**: `public/uploads/equipment/`
- **Review attachments**: `public/uploads/reviews/`
- **Asekuracja files**: `public/uploads/asekuracja/{equipment,sets,transfers}/`
- All uploads have size limits and file type validation

### Environment Configuration
- **Production**: `APP_ENV=prod`, `APP_DEBUG=false`
- **Database**: MySQL connection via `DATABASE_URL`
- **Mail**: SMTP configuration via `MAILER_DSN`
- **Security**: Strong `APP_SECRET` required

## Enterprise Features

### Email System (EmailService)
- Centralized service replacing direct MailerInterface usage
- Automatic email history tracking in `email_history` table
- SMTP configuration from database settings
- Built-in templates: welcome emails, password reset, notifications

### Dynamic Theme System
- Live color customization via admin panel
- CSS generation at `/assets/css/dynamic-theme.css`
- Bootstrap 5.3 integration with custom variables
- Logo upload and branding management

### LDAP Integration (LdapService)
- Active Directory synchronization
- User data mapping and import
- Automatic account creation/updates
- Secure credential handling

### Backup System (AdminService)
- MySQL database backups via `mysqldump`
- Automatic retention management
- Backup verification and cleanup
- Full audit trail

## Development Workflow

1. **Start**: Run `symfony server:start` or configure Apache/Nginx
2. **Database**: Ensure migrations are up to date
3. **Testing**: Write tests for new features (especially services)
4. **Authorization**: Always implement AuthorizationService checks
5. **Audit**: Log significant actions via AuditService
6. **Events**: Use domain events for cross-module communication
7. **Documentation**: Update this file for architectural changes

## Error Handling

All controllers use centralized exception handling:
- `ValidationException` - Business rule violations
- `UnauthorizedAccessException` - Permission denied
- `BusinessLogicException` - Domain-specific errors
- Generic `\Exception` - Unexpected errors (logged, user-friendly message)

## Performance Considerations

- **Caching**: Authorization permissions are cached
- **Pagination**: Use `PaginatorInterface` for large datasets
- **Database**: Optimize queries in Repository classes
- **Assets**: Use Symfony's asset pipeline
- **Production**: Enable OPcache, disable debug mode