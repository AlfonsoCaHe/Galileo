# Manual de Usuario de la Aplicación Galileo

## 1. Introducción y Acceso Inicial
La plataforma **Galileo** es un Sistema de Gestión Educativa (SGE) especializado en la administración y seguimiento de la **Formación Dual**. El sistema centraliza la gestión de proyectos anuales, la vinculación con entidades colaboradoras y la monitorización operativa del alumnado mediante un flujo de trabajo basado en **Resultados de Aprendizaje (RAs)** y **Criterios de Evaluación**.

### Credenciales de Acceso (Administración)
> [!IMPORTANT]
> **Usuario:** `admin@dual.iesgalileoalmeria.es`  
> **Contraseña:** `root`

---

## 2. Perfil Administrador: Configuración Técnica y Gestión Global
El administrador es responsable del mantenimiento de la infraestructura de datos y la supervisión de las entidades de alto nivel del sistema.

### 2.1 Comandos de Terminal (Artisan)
El sistema dispone de comandos específicos para la inicialización y gestión de bases de datos dinámicas:

* **Instalación base:** `php artisan app:instalar`
* **Inicialización del núcleo:** `php artisan db:crear-galileo`
* **Generación de bases de datos por proyecto:** `php artisan db:crear-proyecto {year_start?}`

**Especificaciones del comando `db:crear-proyecto`:**
Este comando acepta el argumento opcional `year_start`. En caso de omitirse, el sistema captura el año actual del servidor mediante el `AdminController`. La ejecución es síncrona; si el proceso retorna un valor de salida **1**, indica un error lógico (generalmente una duplicidad en la base de datos). Ante este escenario, el administrador debe revisar los mensajes de error en la consola o en el log de Artisan para verificar conflictos de nombres.

### 2.2 Gestión de Usuarios y Recuperación
El panel de Gestión de Usuarios permite administrar el acceso global al sistema.
* **Filtro de Estado:** Permite segmentar la visualización entre "Usuarios Activos" y "Usuarios Inactivos".
* **Recuperación:** Las cuentas en estado inactivo pueden reactivarse desde la vista correspondiente, preservando la integridad del historial y su vinculación polimórfica (*rolable*) con los perfiles de profesor, alumno o tutor laboral.

### 2.3 Gestión de Proyectos y Empresas
* **Proyectos:** Permite administrar entornos anuales. El toggle **"Finalizar"** bloquea el acceso operativo al proyecto una vez concluido el ciclo académico.
* **Empresas:** Además de los datos registrales (CIF, Gerente), se gestiona la **Disponibilidad de Plazas (Cupos)**. Esta se parametriza de forma numérica y segmentada por periodos (1º Periodo y 2º Periodo), según la estructura del modelo `CupoEmpresa`.

### 2.4 Flujo de Matriculación y Asignación
La ruta crítica para la gestión del alumnado es: `ALUMNOS` -> `Botón "Ver"` -> `Botón "Matricular en Módulos"`.

Lógica técnico-operativa para la asignación de tutorías:
1.  **Tutor Docente:** Se selecciona desde el listado global de profesores activos.
2.  **Tutor Laboral:** Requiere la selección previa de una **Empresa** para activar el filtrado por AJAX (`getTutoresPorEmpresa`). Una vez seleccionada la empresa, el selector de tutores se actualizará con el personal vinculado exclusivamente a dicha entidad.
3.  **Bajas:** El proceso de **"Desmatricular"** realiza un *Soft Delete* (borrado lógico). Los datos de tareas y notas se ocultan pero permanecen en la base de datos, permitiendo su restauración completa mediante soporte técnico o administración si fuera necesario.

---

## 3. Perfil Profesorado: Gestión Académica y Evaluación

### 3.1 Navegación Principal
La interfaz docente se estructura en tres secciones clave:
* **Módulos:** Gestión de contenidos y evaluación de alumnos matriculados.
* **Tutor Docente:** Seguimiento de alumnos bajo tutela directa.
* **Datos Personales:** Configuración de perfil y credenciales.

### 3.2 Resultados de Aprendizaje (RAs) y Criterios
Dentro de cada módulo, el profesor debe configurar la arquitectura de evaluación:
* **RAs:** Se definen mediante un código alfanumérico y una descripción técnica.
* **Criterios de Evaluación:** Se desglosan a partir de cada RA, permitiendo una trazabilidad exacta de las competencias que el alumno registrará en sus tareas diarias.

### 3.3 Seguimiento y Evaluación de Actividad
Desde el **Historial de Tareas** de cada alumno, el profesor supervisa los registros enviados. Para evaluar, se deben gestionar los siguientes campos:
* **Calificación:** Campo numérico para la valoración de la tarea.
* **Apto:** Selector booleano para indicar si la actividad cumple con los requisitos mínimos.
* **Bloqueo:** Toggle crítico de finalización. Al activar el bloqueo, la tarea se consolida y el alumno pierde todos los permisos de edición o eliminación sobre el registro.

---

## 4. Perfil Alumnado: Registro de Actividad Diaria

### 4.1 Navegación y Control
* **Inicio:** Vista de módulos y tutores asignados.
* **Tareas Pendientes:** Registros en estado borrador (editables).
* **Tareas Realizadas:** Historial de actividades ya bloqueadas por el profesorado.

### 4.2 Creación de Tareas
Al registrar una actividad, el alumno debe completar obligatoriamente:
* **Actividad:** Selector desplegable que vincula la tarea con los RAs y criterios definidos por el profesor.
* **Anotaciones del alumno:** Descripción detallada del trabajo realizado.
* **Fecha:** Día de ejecución (mediante selector de calendario).
* **Duración:** Tiempo invertido, expresado en formato **HH:MM** (ej. `04:30`).

### 4.3 Estados de la Tarea

| Estado | Origen / Activador | Permisos del Alumno | Visibilidad |
| :--- | :--- | :--- | :--- |
| **Pendiente** | Creación inicial del alumno. | Editar, Actualizar y Eliminar. | Borrador editable. |
| **Realizada** | Activación del Bloqueo por el Profesor. | Solo Lectura. Bloqueo de edición y borrado. | Calificación y estado de "Apto" visible. |

---

## 5. Perfil Tutor Laboral: Supervisión en Empresa

### 5.1 Interfaz Operativa
El tutor laboral cuenta con un panel simplificado enfocado en la verificación:
* **Entorno de Empresa:** El sistema asocia automáticamente al tutor con su entidad empleadora mediante la relación `empresa_id`.
* **Supervisión de Alumnado:** Acceso exclusivo a los alumnos asignados a su empresa. El tutor puede visualizar las actividades registradas para validar que el aprendizaje en el centro de trabajo coincide con las tareas documentadas en la plataforma Galileo.