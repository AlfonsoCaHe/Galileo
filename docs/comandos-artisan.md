# Manual de Comandos Artisan Personalizados para la aplicación Galileo

Este documento detalla los comandos Artisan específicos de la aplicación Galileo, su uso, y la automatización que proporcionan.

---

## 1. db:crear-curso

### Propósito
Automatizar la creación de una nueva base de datos y su estructura de tablas (migraciones) para cada nuevo ciclo académico bianual. Esto permite que la aplicación trabaje con múltiples bases de datos de cursos simultáneamente.

### Uso
El comando puede ejecutarse sin argumentos (usando el año actual por defecto) o especificando el año de inicio del curso mediante argumento.

```bash
# Opción 1: Usar el año actual (ej: si es 2025, crea GALILEO_2025_2027)
php artisan db:crear-proyecto

# Opción 2: Especificar el año de inicio (Crea GALILEO_2026_2028)
php artisan db:crear-proyecto 2026