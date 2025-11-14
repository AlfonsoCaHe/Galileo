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

```

---

## 2. db:seed --class=PruebaRelacionesSeeder

### Propósito
Automatizar la creación y populación de las tablas de los elementos que se insertarán en la base de datos para poder generar pruebas a partir de ellos.

### Uso
El comando debe ejecutarse sin argumentos.

```bash

php artisan db:seed --class=PruebaRelacionesSeeder

```

---

## 3. db:test-prueba

### Propósito
Automatizar el proceso de creación de las bases de datos y la inclusión de los elementos en las tablas para preparar un entorno de pruebas completo y realista.

### Uso
El comando debe ejecutarse sin argumentos.

### Operaciones internas del comando
El comando emplea los dos comandos anteriores para generar en primer lugar la base de datos galileo, añadir otras dos bases de datos proyecto_2024_2026 y proyecto_2025_2027 e incluir información de pruebas en ellas.
```
php artisan db:create-proyecto 2024
php artisan db:create-proyecto 2025
php artisan db:seed --class=PruebaRelacionesSeeder
```

```bash

php artisan db:test-prueba

```

## 4. db:borrar-test

### Propósito
Eliminar el contenido del entorno de pruebas generado anteriormente. Se debe extremar la precaución al utilizar el comando, si el contenido de la base de datos no es de prueba, lo eliminará igualmente.

### Uso
El comando debe ejecutarse sin argumentos.

### Operaciones internas del comando
El comando recorre la tabla bases_de_datos de galileo y elimina las bases de datos contenidas. Posteriormente realiza un vaciado de las tablas bases_de_datos y profesores.
```
for(bd in bases_de_datos)
    DROP DATABASE IF EXIST bd
TRUNCATE galileo.bases_de_datos
TRUNCATE galileo.profesores
```
```bash

php artisan db:borrar-test

```