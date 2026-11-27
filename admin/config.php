<?php
/*
 * Configuración del panel de ANK.
 *
 * CLAVE_INICIAL: sirve SOLO para el primer ingreso al panel (tupagina.com/admin).
 * Apenas entres con ella, el panel te pedirá crear tu propia contraseña y esta
 * clave dejará de funcionar.
 */
if (!defined('ANK_PANEL')) { http_response_code(403); exit; }

// Zona horaria de Ecuador (para saber qué promociones están vigentes hoy).
date_default_timezone_set('America/Guayaquil');

const CLAVE_INICIAL = 'ANK-Z77D-QPLW';

// Tamaño máximo de cada foto que se sube (en megas) y lado más largo con que se guarda (en píxeles).
const FOTO_MAX_MB   = 10;
const FOTO_MAX_LADO = 1600;
