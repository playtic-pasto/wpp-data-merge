# WPP Data Merge

WPP Data Merge es un plugin para WordPress que conecta los datos del ERP CINCO y sincroniza posts, creando o actualizando la información requerida en WordPress.

## Características

- Sincronización automática de datos desde CINCO ERP a WordPress.
- Creación y actualización de posts personalizados.
- Integración sencilla y segura.

## Instalación

1. Clona o copia este plugin en la carpeta `wp-content/plugins/wpp-data-merge` de tu instalación de WordPress.
2. Ejecuta `composer install` dentro del directorio del plugin para instalar las dependencias necesarias.
3. Activa el plugin desde el panel de administración de WordPress.

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- Composer

## Uso

Una vez activado, el plugin comenzará a sincronizar los datos automáticamente según la configuración definida en el código fuente.

## Desarrollo

- El código fuente principal se encuentra en la carpeta `src/`.
- El autoloading se gestiona mediante Composer.
- Para modificar la lógica de sincronización, edita las clases bajo el namespace `WPDM`.

## Créditos

- Autor: PlayTIC
- Basado en código de Dr. Max V. y Roy Orbison

## Licencia

GPLv2 o posterior. Consulta el archivo principal del plugin para más detalles.
