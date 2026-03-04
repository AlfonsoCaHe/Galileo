# 🛠️ Manual de Instalación y Configuración: Aplicación Galileo

Este manual describe el proceso de despliegue en servidor y la
configuración inicial de la base de datos multi-inquilino.

------------------------------------------------------------------------

## 1. Requisitos del Sistema (Infraestructura)

Se requieren los como mínimo los siguientes componentes:

-   **Servidor:** Windows Server con Internet Information Services (IIS) 10.
-   **Lenguaje:** PHP (versión Non-Thread Safe).
-   **Base de Datos:** MariaDB.
-   **Módulos de Servidor:** URL Rewrite 2.1 instalado en IIS para la gestión de rutas de Laravel.
-   **Conectividad:** Configuración de DNS (OneToOne) para el dominio y redirección de puertos en el router hacia el servidor.

------------------------------------------------------------------------

## 2. Proceso de Instalación (Comandos Artisan)

La aplicación cuenta con un sistema de comandos personalizados para automatizar el despliegue de la infraestructura de bases de datos.

### A. Instalación Automática Completa

Para un despliegue rápido, ejecute el comando maestro:

``` bash
php artisan app:instalar
```

Este comando realiza las siguientes acciones de forma secuencial:

-   Configura el sistema central creando la base de datos principal (**Galileo**).
-   Crea el proyecto del año actual de forma automática.

------------------------------------------------------------------------

### B. Despliegue Manual de Componentes

Si se requiere realizar la instalación por pasos, se utilizan los siguientes comandos:

#### Crear Base de Datos Principal

``` bash
php artisan db:crear-galileo
```

Crea físicamente la base de datos, ejecuta las migraciones (estructura de tablas de usuarios, empresas y proyectos) y siembra el usuario administrador inicial.

#### Crear Nuevo Proyecto (Base de Datos Dinámica)

``` bash
php artisan db:crear-proyecto {year_start?}
```

Genera una base de datos física independiente para un ciclo escolar (ej. `proyecto_2024_2026`).

**Importante:**\
Si no se especifica el año, el comando toma por defecto el año actual.

Este comando utiliza la clase `ProjectSchemaManager` para generar automáticamente todas las tablas locales (alumnos, módulos,  tareas, RAs y criterios) dentro de la nueva base de datos.

------------------------------------------------------------------------

## 3. Configuración del Entorno (.env)

Es fundamental configurar el archivo `.env` para que el sistema pueda gestionar las conexiones dinámicas:

-   **DB_CONNECTION:** Debe apuntar a `mysql` (conexión estática a la BD Galileo).
-   **Plantilla de Proyectos:** El sistema utiliza una configuración base llamada `course_template` en `database.php`.
    Esta plantilla se clona y modifica "en caliente" por el middleware `SetProjectConnection` para apuntar al nombre real de la base de datos del proyecto solicitado.

------------------------------------------------------------------------

## 4. Acceso Inicial y Seguridad

Una vez finalizada la instalación de la base de datos central y el primer proyecto, las credenciales de acceso se crean por defecto. Se recomienda modificarlas en el primer uso de la aplicación.

### Medidas de Seguridad Implementadas

-   **Control de Acceso:** Gestión mediante capas de Middleware (`AdminCheck`, `AlumnoCheck`, `ProfesorCheck`) que validan el rol polimórfico del usuario antes de permitir el acceso a las rutas.
-   **Protección de Datos:** Uso de hasheo obligatorio de contraseñas y protección de sesiones mediante tokens de Laravel.
-   **Integridad Transaccional:** Todas las operaciones de inserción o eliminación de usuarios y empresas utilizan transacciones  de base de datos (`DB::transaction`) para asegurar que no queden datos huérfanos en caso de error.

------------------------------------------------------------------------

## 5. Mantenimiento y Red

-   **DuckDNS:** En caso de usar una IP dinámica, se recomienda configurar un script que notifique periodicamente a DuckDNS para mantener la resolución del dominio.
-   **Recuperación de Usuarios:** El sistema utiliza *Soft Deletes* (borrado lógico).
    Los usuarios eliminados no desaparecen de la base de datos; pueden recuperarse desde el panel de administración filtrando por **"Usuarios Inactivos"**.
