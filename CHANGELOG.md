# Historial de Cambios (CHANGELOG)

## WPP Data Merge - WordPress Post Data Merge

Este archivo documenta todos los cambios versionados realizados en **WPP Data Merge**.  
El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) y la numeración de versiones sigue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0]

### Fixed fernando.jaramillo - 2026-04-28

- Version estable del plugin, con funcionalidades completas de sincronización de datos entre WordPress y la API externa.
- Se implementa un sistema de logging detallado para monitorear el proceso de sincronización.

---

## [1.0.1]

### Fixed luis.daniel - 2026-05-11

- Se corrige warning repetido de credenciales faltantes al activar el plugin: ahora solo se registra una vez por request.
- El campo Endpoint ahora solo acepta la URL base, se normaliza automáticamente al guardar (elimina rutas como `/V3/API/Auth/Usuario`).
- Se reemplaza el input numérico del intervalo del Cron Job por un select con opciones predefinidas: cada hora, cada 2 horas, cada 4 horas, cada 8 horas, 2 veces al día, 1 vez al día.
- Se corrige bug donde los filtros por proyecto aparecían visibles cuando los filtros globales estaban activos por defecto (primera configuración).
- Se agrega purga automática del historial de ejecuciones: se eliminan registros con más de 30 días de antigüedad.
- Los campos ID Macroproyecto, Proyectos Asociados e ID Proyecto ya no son obligatorios, permitiendo publicar un proyecto sin tener configurados los IDs de SINCO.

---

## [0.0.1-alpha.2]

### Fixed fernando.jaramillo - 2026-04-08

- Se ajusta documentacion de metodos y Clases para proyecto WPP Data Merge.
- Valida Conexion con Api
- Se impleenta la configuracion de endpoint, usuario y contraseña para la API en la interfaz de administración del plugin.

---

## [0.0.1-alpha.1]

### Fixed fernando.jaramillo - 2026-03-16

- **Versión Inicial del Plugin:** Lanzamiento de la primera versión estable del plugin.
- **Interfaz de Usuario Sencilla:** Añadida una página de administración básica para iniciar el proceso de sincronización.
- **Documentación Inicial:** Archivos `README.md`, `CONTRIBUTIONS.md` y `LICENSE.md` incluidos.

---
