# ANK — Sitio web

Sitio estático (HTML, CSS y JavaScript) de ANK: fabricación de uniformes escolares y deportivos.
No requiere instalación: abre `index.html` o publícalo en GitHub Pages, Netlify o Vercel.

## Archivos
- `index.html` — estructura y contenido
- `style.css` — estilos
- `script.js` — configuración (`CONFIG`), productos (`PRODUCTS`) y comportamiento

## Qué editar
- **Teléfonos de los locales:** `CONFIG.branches` en `script.js` (formato internacional sin `+`).
- **Redes sociales:** `CONFIG.social` en `script.js`.
- **Productos e imágenes:** `PRODUCTS` en `script.js` (campo `image` con la ruta de la foto).
- **Horarios y direcciones:** sección Contacto en `index.html`.

## Probar en local
```
python -m http.server 5500
```
Luego abre http://localhost:5500
