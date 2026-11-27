# ANK Sports — Sitio web

Sitio de ANK: catálogo de uniformes deportivos, educativos y otros productos, con cotización por WhatsApp.
Se publica en **Hostinger** (necesita PHP para el panel de administración).

## Carpetas y archivos
- `index.html`, `style.css`, `script.js` — la página.
- `img/` — imágenes fijas de la página (logos, escudos, uniformes de instituciones).
- `admin/` — **panel de administración** (tupagina.com/admin). No modificar.
  - `admin/config.php` — clave inicial del primer ingreso.
  - `admin/privado/` — contraseña cifrada del panel (protegida).
- `data/contenido.json` — lo que el panel guarda: fotos de Deportivos/Otros y Promociones.
- `uploads/` — fotos subidas desde el panel.

## Qué se cambia desde el panel (sin código)
- **Uniformes escolares**: portada/escudo de cada institución y las fotos de sus 3 uniformes (deportivo, lunes, diario).
- **Uniformes deportivos** y **Otros**: portada de cada tarjeta y fotos de "Ver productos" (subir, ordenar, eliminar).
- **Fondo del inicio**: fotos de deportistas que cambian solas de fondo en la portada (máx. 8).
- **Redes sociales**: enlaces de Facebook e Instagram (si un campo queda vacío, el icono no se muestra).
- **Promociones**: crear, editar, ocultar, eliminar (con fechas de vigencia).

## Qué sigue en el código
- Teléfonos: `CONFIG` en `script.js` (los enlaces de redes se ponen desde el panel; `CONFIG.social` es solo respaldo).
- Nombres/descripciones de los recuadros e instituciones educativas: `PRODUCTS` en `script.js`. Si cambias el nombre o añades una institución, hazlo también en `INSTITUCIONES` de `admin/index.php` (el `id` debe ser el mismo).
- Textos legales: `index.html` (sección "Información Legal").

## Importante al actualizar la página en Hostinger
Si subes una versión nueva del sitio, **no reemplaces** `data/contenido.json`, la carpeta `uploads/`
ni `admin/privado/`: ahí están las fotos, promociones y la contraseña que cargaron los dueños.

## Al actualizar CSS o JS
Cambia el número de `?v=` en `index.html` (por ejemplo `style.css?v=20261201`) para que los visitantes reciban la versión nueva y no la guardada en su navegador.
