# Estructura del Proyecto

## Arquitectura: Clean Architecture con Slim Framework

```
slim_test/
├── app/                          # Configuración de la aplicación
│   ├── dependencies.php          # Inyección de dependencias
│   ├── middleware.php            # Configuración de middleware
│   ├── repositories.php         # Configuración de repositorios
│   ├── routes.php               # Definición de rutas
│   └── settings.php             # Configuración general
│
├── public/                       # Punto de entrada web
│   ├── index.php                # Archivo principal
│   └── .htaccess                # Configuración Apache
│
├── src/                         # Código fuente (Clean Architecture)
│   ├── Application/             # Capa de aplicación
│   │   ├── Actions/             # Acciones/Controladores
│   │   │   ├── Action.php       # Clase base de acción
│   │   │   ├── ActionError.php  # Manejo de errores
│   │   │   ├── ActionPayload.php # Respuestas estructuradas
│   │   │   └── User/
│   │   │       ├── ListUsersAction.php
│   │   │       ├── UserAction.php
│   │   │       └── ViewUserAction.php
│   │   ├── Handlers/            # Manejadores de errores
│   │   │   ├── HttpErrorHandler.php
│   │   │   └── ShutdownHandler.php
│   │   ├── Middleware/          # Middleware personalizado
│   │   │   └── SessionMiddleware.php
│   │   ├── ResponseEmitter/     # Emisor de respuestas
│   │   │   └── ResponseEmitter.php
│   │   └── Settings/            # Configuración de la app
│   │       ├── Settings.php
│   │       └── SettingsInterface.php
│   │
│   ├── Domain/                  # Capa de dominio (Entidades)
│   │   ├── DomainException/     # Excepciones de dominio
│   │   │   ├── DomainException.php
│   │   │   └── DomainRecordNotFoundException.php
│   │   └── User/
│   │       ├── User.php              # Entidad User
│   │       ├── UserNotFoundException.php
│   │       └── UserRepository.php    # Interfaz de repositorio
│   │
│   └── Infrastructure/          # Capa de infraestructura
│       └── Persistence/
│           └── User/
│               └── InMemoryUserRepository.php  # Implementación en memoria
│
├── tests/                       # Pruebas unitarias
│   ├── TestCase.php             # Clase base de pruebas
│   ├── bootstrap.php            # Configuración de pruebas
│   ├── Application/
│   │   └── Actions/
│   │       ├── ActionTest.php
│   │       └── User/
│   │           ├── ListUserActionTest.php
│   │           └── ViewUserActionTest.php
│   ├── Domain/
│   │   └── User/
│   │       └── UserTest.php
│   └── Infrastructure/
│       └── Persistence/
│           └── User/
│               └── InMemoryUserRepositoryTest.php
│
├── var/                         # Archivos variables
│   └── cache/                   # Caché de la aplicación
│
├── logs/                        # Archivos de logs
│
├── .github/                     # Configuración de GitHub
│   ├── workflows/
│   │   └── tests.yml           # Pipeline de CI/CD
│   └── dependabot.yml          # Configuración de dependencias
│
├── composer.json                # Dependencias PHP
├── composer.lock                # Lock de dependencias
├── phpunit.xml                  # Configuración de PHPUnit
├── phpstan.neon.dist            # Configuración de PHPStan
├── phpcs.xml                    # Configuración de CodeSniffer
├── docker-compose.yml           # Configuración de Docker
├── .htaccess                    # Configuración Apache
├── .gitignore                   # Archivos ignorados por Git
├── .coveralls.yml               # Configuración de cobertura
├── README.md                    # Documentación
└── CONTRIBUTING.md             # Guía de contribuciones
```

## Capas de la Arquitectura

| Capa | Descripción |
|------|-------------|
| **Domain** | Entidades y lógica de negocio pura |
| **Application** | Casos de uso, acciones y handlers |
| **Infrastructure** | Implementaciones concretas (BD, APIs) |
| **Presentation** | Rutas, middleware y entrada web |

## Tecnologías

- **Framework**: Slim 4
- **Testing**: PHPUnit
- **Análisis estático**: PHPStan

