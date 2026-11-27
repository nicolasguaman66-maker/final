<?php
/*
 * PANEL DE ADMINISTRACIÓN — ANK Sports
 * Permite, sin tocar el código:
 *   - Subir, ordenar y eliminar fotos de "Uniformes deportivos" y "Otros".
 *   - Crear, editar, ocultar y eliminar Promociones.
 * Todo se guarda en ../data/contenido.json y las fotos en ../uploads/
 */
declare(strict_types=1);
define('ANK_PANEL', true);
require __DIR__ . '/config.php';

const DATA_FILE   = __DIR__ . '/../data/contenido.json';
const UPLOAD_DIR  = __DIR__ . '/../uploads';
const PRIV_DIR    = __DIR__ . '/privado';
const AUTH_FILE   = PRIV_DIR . '/acceso.json';
const TRIES_FILE  = PRIV_DIR . '/intentos.json';
const MAX_TRIES   = 5;      // intentos fallidos permitidos…
const LOCK_MIN    = 15;     // …antes de bloquear por estos minutos
const SESSION_MIN = 120;    // la sesión se cierra sola tras 2 horas sin uso

/* Instituciones educativas (el id debe coincidir con el de PRODUCTS.educativos en script.js) */
const INSTITUCIONES = [
    'guayllabamba' => 'Institución Educativa Guayllabamba',
    'sfq'          => 'Institución Educativa SFQ',
    'ingapirca'    => 'Institución Educativa Ingapirca',
    'ascazubi'     => 'Institución Educativa Ascázubi',
    'luis-pasteur' => 'Institución Educativa Luis Pasteur',
    'junin'        => 'Institución Educativa Junín',
];
const TIPOS_UNIFORME = ['deportivo' => 'Uniforme deportivo', 'lunes' => 'Uniforme día lunes', 'diario' => 'Uniforme diario'];

const HERO_MAX = 8;   // máximo de fotos en el fondo del inicio

/* ---------------- Utilidades ---------------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function leer_json(string $file, $def) {
    if (!is_file($file)) return $def;
    $fp = fopen($file, 'r'); if (!$fp) return $def;
    flock($fp, LOCK_SH); $txt = stream_get_contents($fp); flock($fp, LOCK_UN); fclose($fp);
    $d = json_decode((string)$txt, true);
    return is_array($d) ? $d : $def;
}
function guardar_json(string $file, $data): bool {
    $txt = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($txt === false) return false;
    return file_put_contents($file, $txt, LOCK_EX) !== false;
}
function contenido(): array {
    $d = leer_json(DATA_FILE, []);
    $d['secciones'] = $d['secciones'] ?? [];
    $d['secciones']['deportivos'] = $d['secciones']['deportivos'] ?? [];
    $d['secciones']['otros'] = $d['secciones']['otros'] ?? [];
    // Instituciones: se completan las que todavía no estén guardadas
    $guardadas = [];
    foreach (($d['secciones']['educativos'] ?? []) as $s) $guardadas[$s['id'] ?? ''] = $s;
    $edu = [];
    foreach (INSTITUCIONES as $id => $nombre) {
        $s = $guardadas[$id] ?? [];
        $s['id'] = $id; $s['nombre'] = $nombre; $s['portada'] = $s['portada'] ?? '';
        foreach (TIPOS_UNIFORME as $t => $_) $s['uniformes'][$t] = $s['uniformes'][$t] ?? '';
        $edu[] = $s;
    }
    $d['secciones']['educativos'] = $edu;
    $d['promociones'] = $d['promociones'] ?? [];
    $d['redes'] = array_merge(['facebook' => '', 'instagram' => ''], is_array($d['redes'] ?? null) ? $d['redes'] : []);
    $d['hero'] = is_array($d['hero'] ?? null) ? array_values(array_filter($d['hero'], 'is_string')) : [];
    return $d;
}
function flash(string $tipo, string $msg): void { $_SESSION['flash'][] = [$tipo, $msg]; }
function ir(string $qs = ''): void { header('Location: index.php' . ($qs ? '?' . $qs : '')); exit; }
function ip_key(): string { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|ank'); }
function id_nuevo(): string { return bin2hex(random_bytes(6)); }

/* Borra un archivo solo si está dentro de /uploads (las fotos originales de /img nunca se borran) */
function borrar_subida(?string $ruta): void {
    if (!$ruta || strpos($ruta, 'uploads/') !== 0) return;
    $base = realpath(UPLOAD_DIR);
    $file = realpath(__DIR__ . '/../' . $ruta);
    if ($base && $file && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) @unlink($file);
}

/* Recibe una foto, la valida, la reduce de tamaño y la guarda en /uploads. Devuelve la ruta o un error. */
function procesar_foto(array $f, string &$error): ?string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = in_array($f['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'la foto es demasiado pesada' : 'no se pudo recibir la foto';
        return null;
    }
    if ($f['size'] > FOTO_MAX_MB * 1024 * 1024) { $error = 'pesa más de ' . FOTO_MAX_MB . ' MB'; return null; }
    $info = @getimagesize($f['tmp_name']);
    $tipos = [IMAGETYPE_JPEG => 'jpeg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($tipos[$info[2]])) { $error = 'no es una imagen JPG, PNG o WEBP'; return null; }
    $fn = 'imagecreatefrom' . $tipos[$info[2]];
    if (!function_exists($fn)) { $error = 'el servidor no admite ese formato'; return null; }
    $img = @$fn($f['tmp_name']);
    if (!$img) { $error = 'la imagen está dañada'; return null; }

    // Girar según la orientación del celular (fotos JPG)
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($f['tmp_name']);
        $o = (int)($exif['Orientation'] ?? 1);
        if ($o === 3) $img = imagerotate($img, 180, 0);
        elseif ($o === 6) $img = imagerotate($img, -90, 0);
        elseif ($o === 8) $img = imagerotate($img, 90, 0);
    }
    // Reducir tamaño
    $w = imagesx($img); $h = imagesy($img);
    $esc = min(1, FOTO_MAX_LADO / max($w, $h));
    $nw = max(1, (int)round($w * $esc)); $nh = max(1, (int)round($h * $esc));
    $alpha = in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true);
    $out = imagecreatetruecolor($nw, $nh);
    if ($alpha) { imagealphablending($out, false); imagesavealpha($out, true); }
    else { imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255)); }
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($img);

    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $nombre = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . ($alpha ? '.png' : '.jpg');
    $ok = $alpha ? imagepng($out, UPLOAD_DIR . '/' . $nombre, 6) : imagejpeg($out, UPLOAD_DIR . '/' . $nombre, 85);
    imagedestroy($out);
    if (!$ok) { $error = 'no se pudo guardar (revisa permisos de la carpeta uploads)'; return null; }
    return 'uploads/' . $nombre;
}

/* Normaliza el arreglo $_FILES de un input múltiple */
function lista_archivos(string $campo): array {
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return !empty($_FILES[$campo]['name']) ? [$_FILES[$campo]] : [];
    }
    $out = [];
    foreach ($_FILES[$campo]['name'] as $i => $n) {
        if ($n === '' && ($_FILES[$campo]['error'][$i] ?? 0) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'tmp_name' => $_FILES[$campo]['tmp_name'][$i], 'error' => $_FILES[$campo]['error'][$i], 'size' => $_FILES[$campo]['size'][$i]];
    }
    return $out;
}

/* ---------------- Sesión segura ---------------- */
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('ANKPANEL');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

if (!empty($_SESSION['ok']) && (time() - ($_SESSION['t'] ?? 0)) > SESSION_MIN * 60) { $_SESSION = []; session_regenerate_id(true); }
if (!empty($_SESSION['ok'])) $_SESSION['t'] = time();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

if (!is_dir(PRIV_DIR)) @mkdir(PRIV_DIR, 0755, true);
$auth = leer_json(AUTH_FILE, []);
$tienePass = !empty($auth['hash']);

/* ---------------- Acciones (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fotos demasiado pesadas en conjunto: PHP descarta todo el envío
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'Las fotos que intentaste subir pesan demasiado juntas. Sube menos fotos a la vez.');
        ir($_SERVER['QUERY_STRING'] ?? '');
    }
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { flash('error', 'La página caducó. Inténtalo de nuevo.'); ir(); }
    $accion = (string)($_POST['accion'] ?? '');

    /* --- Ingresar --- */
    if ($accion === 'login') {
        $intentos = leer_json(TRIES_FILE, []);
        $k = ip_key(); $reg = $intentos[$k] ?? ['n' => 0, 'hasta' => 0];
        if ($reg['hasta'] > time()) {
            flash('error', 'Demasiados intentos fallidos. Espera ' . ceil(($reg['hasta'] - time()) / 60) . ' minutos.'); ir();
        }
        $pass = (string)($_POST['clave'] ?? '');
        $valida = $tienePass ? password_verify($pass, $auth['hash']) : hash_equals(CLAVE_INICIAL, trim($pass));
        if (!$valida) {
            $reg['n']++;
            if ($reg['n'] >= MAX_TRIES) { $reg = ['n' => 0, 'hasta' => time() + LOCK_MIN * 60]; }
            $intentos[$k] = $reg; guardar_json(TRIES_FILE, $intentos);
            usleep(600000);
            flash('error', 'Contraseña incorrecta.'); ir();
        }
        unset($intentos[$k]); guardar_json(TRIES_FILE, $intentos);
        session_regenerate_id(true);
        $_SESSION['ok'] = true; $_SESSION['t'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        $_SESSION['cambiar'] = !$tienePass;   // primer ingreso: obligar a crear contraseña
        ir($tienePass ? '' : 'tab=cuenta');
    }

    if (empty($_SESSION['ok'])) ir();

    if ($accion === 'salir') { $_SESSION = []; session_destroy(); session_start(); flash('ok', 'Sesión cerrada.'); ir(); }

    /* --- Cambiar contraseña --- */
    if ($accion === 'clave') {
        $nueva = (string)($_POST['nueva'] ?? ''); $rep = (string)($_POST['repetir'] ?? '');
        if ($tienePass && !password_verify((string)($_POST['actual'] ?? ''), $auth['hash'])) { flash('error', 'La contraseña actual no es correcta.'); ir('tab=cuenta'); }
        if (mb_strlen($nueva) < 8) { flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.'); ir('tab=cuenta'); }
        if ($nueva !== $rep) { flash('error', 'Las dos contraseñas no coinciden.'); ir('tab=cuenta'); }
        if (!guardar_json(AUTH_FILE, ['hash' => password_hash($nueva, PASSWORD_DEFAULT), 'fecha' => date('c')])) {
            flash('error', 'No se pudo guardar la contraseña (revisa permisos de admin/privado).'); ir('tab=cuenta');
        }
        $_SESSION['cambiar'] = false;
        flash('ok', 'Contraseña guardada. Desde ahora entra con tu nueva contraseña.'); ir();
    }

    if (!empty($_SESSION['cambiar'])) { flash('error', 'Primero crea tu contraseña.'); ir('tab=cuenta'); }

    $d = contenido();

    /* --- Fotos de un apartado (deportivos / otros) --- */
    if (in_array($accion, ['subir', 'borrar_foto', 'mover_foto'], true)) {
        $grupo = (string)($_POST['grupo'] ?? ''); $sid = (string)($_POST['sid'] ?? '');
        if (!isset($d['secciones'][$grupo])) ir();
        $pos = null;
        foreach ($d['secciones'][$grupo] as $i => $s) if (($s['id'] ?? '') === $sid) $pos = $i;
        if ($pos === null) ir('tab=' . urlencode($grupo));
        $fotos = $d['secciones'][$grupo][$pos]['fotos'] ?? [];
        $nombre = $d['secciones'][$grupo][$pos]['nombre'] ?? '';

        if ($accion === 'subir') {
            $ok = 0; $errores = [];
            foreach (array_slice(lista_archivos('fotos'), 0, 20) as $f) {
                $err = ''; $ruta = procesar_foto($f, $err);
                if ($ruta) { $fotos[] = $ruta; $ok++; } else { $errores[] = e($f['name']) . ': ' . $err; }
            }
            if ($ok) flash('ok', "Se agregaron $ok foto(s) a «" . e($nombre) . "».");
            if ($errores) flash('error', 'No se subieron: ' . implode('; ', $errores));
            if (!$ok && !$errores) flash('error', 'No elegiste ninguna foto.');
        }
        if ($accion === 'borrar_foto') {
            $i = (int)($_POST['i'] ?? -1);
            if (isset($fotos[$i])) { borrar_subida($fotos[$i]); array_splice($fotos, $i, 1); flash('ok', 'Foto eliminada.'); }
        }
        if ($accion === 'mover_foto') {
            $i = (int)($_POST['i'] ?? -1); $j = $i + ((($_POST['dir'] ?? '') === 'arriba') ? -1 : 1);
            if (isset($fotos[$i], $fotos[$j])) { [$fotos[$i], $fotos[$j]] = [$fotos[$j], $fotos[$i]]; }
        }
        $d['secciones'][$grupo][$pos]['fotos'] = array_values($fotos);
        if (!guardar_json(DATA_FILE, $d)) flash('error', 'No se pudo guardar (revisa permisos de la carpeta data).');
        ir('tab=' . urlencode($grupo) . '#s-' . urlencode($sid));
    }

    /* --- Portada de una tarjeta y fotos de uniformes escolares --- */
    if (in_array($accion, ['portada_subir', 'portada_quitar', 'uniforme_subir', 'uniforme_quitar'], true)) {
        $grupo = (string)($_POST['grupo'] ?? ''); $sid = (string)($_POST['sid'] ?? ''); $tipo = (string)($_POST['tipo'] ?? '');
        if (!in_array($grupo, ['educativos', 'deportivos', 'otros'], true) || !isset($d['secciones'][$grupo])) ir();
        $esUniforme = strpos($accion, 'uniforme_') === 0;
        if ($esUniforme && ($grupo !== 'educativos' || !isset(TIPOS_UNIFORME[$tipo]))) ir('tab=' . urlencode($grupo));
        $pos = null;
        foreach ($d['secciones'][$grupo] as $i => $s) if (($s['id'] ?? '') === $sid) $pos = $i;
        if ($pos === null) ir('tab=' . urlencode($grupo));
        $item = &$d['secciones'][$grupo][$pos];
        $nombre = $item['nombre'] ?? '';
        $etq = $esUniforme ? TIPOS_UNIFORME[$tipo] : 'Portada';
        $actual = $esUniforme ? ($item['uniformes'][$tipo] ?? '') : ($item['portada'] ?? '');
        $nuevo = $actual;
        if (substr($accion, -5) === 'subir') {
            $archivo = lista_archivos('foto')[0] ?? null;
            if (!$archivo) { flash('error', 'No elegiste ninguna foto.'); }
            else {
                $err = ''; $ruta = procesar_foto($archivo, $err);
                if ($ruta) { borrar_subida($actual); $nuevo = $ruta; flash('ok', e($etq) . ' de «' . e($nombre) . '» actualizada.'); }
                else { flash('error', 'No se subió la foto: ' . $err . '.'); }
            }
        } else {
            borrar_subida($actual); $nuevo = '';
            flash('ok', e($etq) . ' de «' . e($nombre) . '» eliminada.');
        }
        if ($esUniforme) $item['uniformes'][$tipo] = $nuevo; else $item['portada'] = $nuevo;
        unset($item);
        if (!guardar_json(DATA_FILE, $d)) flash('error', 'No se pudo guardar (revisa permisos de la carpeta data).');
        ir('tab=' . urlencode($grupo) . '#s-' . urlencode($sid));
    }

    /* --- Fondo del inicio (fotos de deportistas) --- */
    if (in_array($accion, ['hero_subir', 'hero_borrar', 'hero_mover'], true)) {
        $hero = array_values($d['hero'] ?? []);
        if ($accion === 'hero_subir') {
            $ok = 0; $errores = [];
            foreach (array_slice(lista_archivos('fotos'), 0, HERO_MAX) as $f) {
                if (count($hero) >= HERO_MAX) { $errores[] = 'ya hay ' . HERO_MAX . ' fotos (el máximo); elimina alguna para subir otra'; break; }
                $err = ''; $ruta = procesar_foto($f, $err);
                if ($ruta) { $hero[] = $ruta; $ok++; } else { $errores[] = e($f['name']) . ': ' . $err; }
            }
            if ($ok) flash('ok', "Se agregaron $ok foto(s) al fondo del inicio.");
            if ($errores) flash('error', 'No se subieron: ' . implode('; ', $errores));
            if (!$ok && !$errores) flash('error', 'No elegiste ninguna foto.');
        }
        if ($accion === 'hero_borrar') {
            $i = (int)($_POST['i'] ?? -1);
            if (isset($hero[$i])) { borrar_subida($hero[$i]); array_splice($hero, $i, 1); flash('ok', 'Foto eliminada.'); }
        }
        if ($accion === 'hero_mover') {
            $i = (int)($_POST['i'] ?? -1); $j = $i + ((($_POST['dir'] ?? '') === 'arriba') ? -1 : 1);
            if (isset($hero[$i], $hero[$j])) { [$hero[$i], $hero[$j]] = [$hero[$j], $hero[$i]]; }
        }
        $d['hero'] = array_values($hero);
        if (!guardar_json(DATA_FILE, $d)) flash('error', 'No se pudo guardar (revisa permisos de la carpeta data).');
        ir('tab=hero');
    }

    /* --- Redes sociales --- */
    if ($accion === 'redes_guardar') {
        $dominios = ['facebook' => ['facebook.com', 'fb.com', 'fb.me'], 'instagram' => ['instagram.com']];
        $res = []; $errs = [];
        foreach ($dominios as $red => $hosts) {
            $u = trim(mb_substr((string)($_POST[$red] ?? ''), 0, 300));
            if ($u === '') { $res[$red] = ''; continue; }
            if (!preg_match('#^https?://#i', $u)) $u = 'https://' . $u;
            $host = preg_replace('/^(www|m|web)\./', '', strtolower((string)parse_url($u, PHP_URL_HOST)));
            $ok = false;
            foreach ($hosts as $h) if ($host === $h || substr($host, -strlen('.' . $h)) === '.' . $h) $ok = true;
            if (!$ok || !filter_var($u, FILTER_VALIDATE_URL)) { $errs[] = 'el enlace de ' . ucfirst($red) . ' no es válido (debe ser una dirección de ' . $hosts[0] . ')'; continue; }
            $res[$red] = $u;
        }
        if ($errs) { flash('error', 'No se guardó: ' . implode('; ', $errs) . '.'); ir('tab=redes'); }
        $d['redes'] = $res;
        if (!guardar_json(DATA_FILE, $d)) flash('error', 'No se pudo guardar (revisa permisos de la carpeta data).');
        else flash('ok', 'Redes sociales guardadas.');
        ir('tab=redes');
    }

    /* --- Promociones --- */
    if (in_array($accion, ['promo_guardar', 'promo_borrar', 'promo_mover', 'promo_activar'], true)) {
        $pid = (string)($_POST['pid'] ?? '');
        $idx = null;
        foreach ($d['promociones'] as $i => $p) if (($p['id'] ?? '') === $pid) $idx = $i;

        if ($accion === 'promo_guardar') {
            $num = function ($v) { $v = str_replace([',', '$', ' '], ['.', '', ''], trim((string)$v)); return $v === '' ? '' : (is_numeric($v) && $v >= 0 ? number_format((float)$v, 2, '.', '') : null); };
            $p = [
                'id'           => $idx !== null ? $pid : id_nuevo(),
                'titulo'       => trim(mb_substr((string)($_POST['titulo'] ?? ''), 0, 80)),
                'descripcion'  => trim(mb_substr((string)($_POST['descripcion'] ?? ''), 0, 400)),
                'beneficio'    => trim(mb_substr((string)($_POST['beneficio'] ?? ''), 0, 80)),
                'precio_antes' => $num($_POST['precio_antes'] ?? ''),
                'precio_ahora' => $num($_POST['precio_ahora'] ?? ''),
                'desde'        => (string)($_POST['desde'] ?? ''),
                'hasta'        => (string)($_POST['hasta'] ?? ''),
                'condiciones'  => trim(mb_substr((string)($_POST['condiciones'] ?? ''), 0, 1000)),
                'activo'       => !empty($_POST['activo']),
                'foto'         => $idx !== null ? ($d['promociones'][$idx]['foto'] ?? '') : '',
            ];
            $errs = [];
            if ($p['titulo'] === '') $errs[] = 'escribe el título';
            $fecha = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
            if (!$fecha($p['desde']) || !$fecha($p['hasta'])) $errs[] = 'pon la fecha de inicio y la de fin';
            elseif ($p['hasta'] < $p['desde']) $errs[] = 'la fecha de fin no puede ser antes que la de inicio';
            if ($p['precio_antes'] === null || $p['precio_ahora'] === null) $errs[] = 'los precios deben ser números (ej. 25.50)';
            elseif ($p['precio_antes'] !== '' && $p['precio_ahora'] === '') $errs[] = 'si pones el precio anterior, pon también el precio de promoción';
            elseif ($p['precio_antes'] !== '' && (float)$p['precio_ahora'] >= (float)$p['precio_antes']) $errs[] = 'el precio de promoción debe ser menor que el anterior';
            if ($p['beneficio'] === '' && ($p['precio_antes'] === '' || $p['precio_ahora'] === '')) $errs[] = 'indica el beneficio (ej. "20% de descuento") o el precio anterior y el de promoción — la ley lo exige';
            if ($errs) {
                $_SESSION['form_promo'] = $_POST;
                flash('error', 'No se guardó: ' . implode('; ', $errs) . '.');
                ir('tab=promociones&editar=' . urlencode($idx !== null ? $pid : 'nueva') . '#form');
            }
            $archivo = lista_archivos('foto')[0] ?? null;
            if ($archivo) {
                $err = ''; $ruta = procesar_foto($archivo, $err);
                if ($ruta) { borrar_subida($p['foto']); $p['foto'] = $ruta; }
                else flash('error', 'La promoción se guardó, pero la foto no: ' . $err . '.');
            } elseif (!empty($_POST['quitar_foto'])) { borrar_subida($p['foto']); $p['foto'] = ''; }
            if ($idx !== null) $d['promociones'][$idx] = $p; else array_unshift($d['promociones'], $p);
            unset($_SESSION['form_promo']);
            flash('ok', 'Promoción «' . e($p['titulo']) . '» guardada.');
        }
        if ($accion === 'promo_borrar' && $idx !== null) {
            borrar_subida($d['promociones'][$idx]['foto'] ?? '');
            flash('ok', 'Promoción «' . e($d['promociones'][$idx]['titulo'] ?? '') . '» eliminada.');
            array_splice($d['promociones'], $idx, 1);
        }
        if ($accion === 'promo_activar' && $idx !== null) {
            $d['promociones'][$idx]['activo'] = empty($d['promociones'][$idx]['activo']);
            flash('ok', $d['promociones'][$idx]['activo'] ? 'Promoción visible.' : 'Promoción ocultada.');
        }
        if ($accion === 'promo_mover' && $idx !== null) {
            $j = $idx + ((($_POST['dir'] ?? '') === 'arriba') ? -1 : 1);
            if (isset($d['promociones'][$j])) { [$d['promociones'][$idx], $d['promociones'][$j]] = [$d['promociones'][$j], $d['promociones'][$idx]]; }
        }
        $d['promociones'] = array_values($d['promociones']);
        if (!guardar_json(DATA_FILE, $d)) flash('error', 'No se pudo guardar (revisa permisos de la carpeta data).');
        ir('tab=promociones');
    }
    ir();
}

/* ---------------- Pantallas (GET) ---------------- */
$logueado = !empty($_SESSION['ok']);
$tab = (string)($_GET['tab'] ?? 'deportivos');
if (!empty($_SESSION['cambiar'])) $tab = 'cuenta';
if (!in_array($tab, ['educativos', 'deportivos', 'otros', 'promociones', 'hero', 'redes', 'cuenta'], true)) $tab = 'deportivos';
$mensajes = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);
$d = $logueado ? contenido() : [];
$hoy = date('Y-m-d');

function campo_csrf(string $csrf): string { return '<input type="hidden" name="csrf" value="' . e($csrf) . '">'; }
function estado_promo(array $p, string $hoy): array {
    if (empty($p['activo'])) return ['Oculta', 'gris'];
    if (($p['hasta'] ?? '') < $hoy) return ['Vencida', 'rojo'];
    if (($p['desde'] ?? '') > $hoy) return ['Programada', 'azul'];
    return ['Visible en la página', 'verde'];
}
function fecha_corta(string $s): string { $p = explode('-', $s); return count($p) === 3 ? "$p[2]/$p[1]/$p[0]" : ''; }

/* Recuadro con una foto y sus botones para cambiarla o quitarla (portadas y uniformes) */
function ranura_foto(string $csrf, string $tab, string $sid, string $etq, ?string $ruta, string $subir, string $quitar, string $tipo = '', string $ayuda = ''): string {
    $campos = campo_csrf($csrf) . '<input type="hidden" name="grupo" value="' . e($tab) . '"><input type="hidden" name="sid" value="' . e($sid) . '"><input type="hidden" name="tipo" value="' . e($tipo) . '">';
    $h  = '<div class="slot"><div class="slot__img">' . ($ruta ? '<img src="../' . e($ruta) . '" alt="" loading="lazy">' : '<span>Sin foto</span>') . '</div>';
    $h .= '<div class="slot__body"><b>' . e($etq) . '</b>' . ($ayuda ? '<small>' . e($ayuda) . '</small>' : '');
    $h .= '<form method="post" action="index.php?tab=' . e($tab) . '" enctype="multipart/form-data">' . $campos . '<input type="hidden" name="accion" value="' . e($subir) . '">';
    $h .= '<label class="upload__pick">＋ ' . ($ruta ? 'Cambiar foto' : 'Elegir foto') . '<input type="file" name="foto" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()"></label></form>';
    if ($ruta) {
        $h .= '<form method="post" action="index.php?tab=' . e($tab) . '" onsubmit="return confirm(\'¿Quitar esta foto?\');">' . $campos . '<input type="hidden" name="accion" value="' . e($quitar) . '"><button class="btn btn--danger btn--sm" type="submit">Quitar</button></form>';
    }
    return $h . '</div></div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Panel ANK Sports</title>
  <link rel="stylesheet" href="panel.css?v=1">
</head>
<body>
<?php if (!$logueado): ?>
  <main class="login">
    <form class="login__box" method="post" action="index.php">
      <img src="../img/logo-ank-nav.png" alt="ANK Sports" class="login__logo">
      <h1>Panel de administración</h1>
      <?php foreach ($mensajes as [$t, $m]): ?><p class="msg msg--<?= e($t) ?>"><?= $m ?></p><?php endforeach; ?>
      <?= campo_csrf($csrf) ?>
      <input type="hidden" name="accion" value="login">
      <label>Contraseña<?= $tienePass ? '' : ' (clave inicial)' ?>
        <input type="password" name="clave" required autofocus autocomplete="current-password">
      </label>
      <button class="btn btn--primary" type="submit">Entrar</button>
      <a class="login__back" href="../">← Volver a la página</a>
    </form>
  </main>
<?php else: ?>
  <header class="top">
    <a href="index.php" class="top__brand"><img src="../img/logo-ank-nav.png" alt=""><span>Panel</span></a>
    <nav class="tabs">
      <?php foreach (['educativos' => 'Uniformes escolares', 'deportivos' => 'Uniformes deportivos', 'otros' => 'Otros', 'promociones' => 'Promociones', 'hero' => 'Fondo del inicio', 'redes' => 'Redes sociales', 'cuenta' => 'Contraseña'] as $k => $v): ?>
        <a href="?tab=<?= $k ?>" class="tabs__a<?= $tab === $k ? ' is-on' : '' ?>"><?= e($v) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="top__right">
      <a class="btn btn--light" href="../" target="_blank" rel="noopener">Ver página ↗</a>
      <form method="post" action="index.php"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="salir"><button class="btn btn--ghost" type="submit">Salir</button></form>
    </div>
  </header>

  <main class="wrap">
    <?php foreach ($mensajes as [$t, $m]): ?><p class="msg msg--<?= e($t) ?>"><?= $m ?></p><?php endforeach; ?>

    <?php if ($tab === 'deportivos' || $tab === 'otros'): ?>
      <h1><?= $tab === 'deportivos' ? 'Uniformes deportivos' : 'Otros' ?></h1>
      <p class="hint">Estas son las fotos que aparecen al pulsar <b>"Ver productos"</b> en cada recuadro de la página. La primera foto es la que se ve primero.</p>
      <?php foreach ($d['secciones'][$tab] ?? [] as $s): $fotos = $s['fotos'] ?? []; ?>
        <section class="box" id="s-<?= e($s['id']) ?>">
          <div class="box__head">
            <h2><?= e($s['nombre'] ?? $s['id']) ?></h2>
            <span class="pill"><?= count($fotos) ?> foto(s)</span>
          </div>
          <div class="slots"><?= ranura_foto($csrf, $tab, $s['id'], 'Portada de la tarjeta', $s['portada'] ?? '', 'portada_subir', 'portada_quitar', '', 'Es la imagen que se ve en la tarjeta de la página. Si no subes una, se usa la primera foto del álbum.') ?></div>
          <h3 class="sub">Álbum de "Ver productos"</h3>
          <?php if (!$fotos): ?><p class="empty">Todavía no hay fotos. En la página se muestra "Fotos próximamente".</p><?php endif; ?>
          <div class="grid">
            <?php foreach ($fotos as $i => $f): ?>
              <figure class="ph">
                <img src="../<?= e($f) ?>" alt="" loading="lazy">
                <figcaption>
                  <span class="ph__n"><?= $i + 1 ?></span>
                  <form method="post" action="index.php?tab=<?= e($tab) ?>"><?= campo_csrf($csrf) ?>
                    <input type="hidden" name="accion" value="mover_foto"><input type="hidden" name="grupo" value="<?= e($tab) ?>"><input type="hidden" name="sid" value="<?= e($s['id']) ?>"><input type="hidden" name="i" value="<?= $i ?>"><input type="hidden" name="dir" value="arriba">
                    <button class="ico" title="Mover antes" <?= $i === 0 ? 'disabled' : '' ?>>◀</button>
                  </form>
                  <form method="post" action="index.php?tab=<?= e($tab) ?>"><?= campo_csrf($csrf) ?>
                    <input type="hidden" name="accion" value="mover_foto"><input type="hidden" name="grupo" value="<?= e($tab) ?>"><input type="hidden" name="sid" value="<?= e($s['id']) ?>"><input type="hidden" name="i" value="<?= $i ?>"><input type="hidden" name="dir" value="abajo">
                    <button class="ico" title="Mover después" <?= $i === count($fotos) - 1 ? 'disabled' : '' ?>>▶</button>
                  </form>
                  <form method="post" action="index.php?tab=<?= e($tab) ?>" onsubmit="return confirm('¿Eliminar esta foto? No se puede deshacer.');"><?= campo_csrf($csrf) ?>
                    <input type="hidden" name="accion" value="borrar_foto"><input type="hidden" name="grupo" value="<?= e($tab) ?>"><input type="hidden" name="sid" value="<?= e($s['id']) ?>"><input type="hidden" name="i" value="<?= $i ?>">
                    <button class="ico ico--del" title="Eliminar">🗑</button>
                  </form>
                </figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
          <form class="upload" method="post" action="index.php?tab=<?= e($tab) ?>" enctype="multipart/form-data"><?= campo_csrf($csrf) ?>
            <input type="hidden" name="accion" value="subir"><input type="hidden" name="grupo" value="<?= e($tab) ?>"><input type="hidden" name="sid" value="<?= e($s['id']) ?>">
            <label class="upload__pick">＋ Elegir fotos<input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required onchange="this.closest('form').querySelector('.upload__info').textContent = this.files.length + ' foto(s) elegida(s)'"></label>
            <span class="upload__info">Puedes elegir varias a la vez (máx. 20).</span>
            <button class="btn btn--primary" type="submit">Subir</button>
          </form>
        </section>
      <?php endforeach; ?>

    <?php elseif ($tab === 'promociones'):
      $editar = (string)($_GET['editar'] ?? '');
      $actual = null;
      foreach ($d['promociones'] as $p) if (($p['id'] ?? '') === $editar) $actual = $p;
      $mostrarForm = $editar !== '';
      $v = $_SESSION['form_promo'] ?? ($actual ?? ['activo' => true, 'desde' => $hoy]);
      unset($_SESSION['form_promo']);
    ?>
      <div class="row"><h1>Promociones</h1><a class="btn btn--primary" href="?tab=promociones&editar=nueva#form">＋ Nueva promoción</a></div>
      <p class="hint">Solo se ven en la página las promociones <b>activas</b> y que están <b>dentro de sus fechas</b>. Cuando pasa la fecha de fin, desaparecen solas. Si no hay ninguna vigente, el apartado "Promociones" no aparece en la página.</p>

      <?php if (!$d['promociones'] && !$mostrarForm): ?><p class="empty box">Todavía no hay promociones. Pulsa "Nueva promoción" para crear la primera.</p><?php endif; ?>
      <div class="promos">
        <?php foreach ($d['promociones'] as $i => $p): [$est, $col] = estado_promo($p, $hoy); ?>
          <article class="promo">
            <div class="promo__img"><?php if (!empty($p['foto'])): ?><img src="../<?= e($p['foto']) ?>" alt=""><?php else: ?><span>Sin foto</span><?php endif; ?></div>
            <div class="promo__body">
              <span class="tag tag--<?= $col ?>"><?= $est ?></span>
              <h2><?= e($p['titulo']) ?></h2>
              <p class="muted">Del <?= fecha_corta($p['desde'] ?? '') ?> al <?= fecha_corta($p['hasta'] ?? '') ?>
                <?= !empty($p['beneficio']) ? ' · ' . e($p['beneficio']) : '' ?>
                <?= ($p['precio_ahora'] ?? '') !== '' ? ' · $' . e($p['precio_ahora']) : '' ?></p>
              <div class="promo__actions">
                <a class="btn btn--light" href="?tab=promociones&editar=<?= e($p['id']) ?>#form">Editar</a>
                <form method="post" action="index.php?tab=promociones"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="promo_activar"><input type="hidden" name="pid" value="<?= e($p['id']) ?>"><button class="btn btn--light"><?= !empty($p['activo']) ? 'Ocultar' : 'Mostrar' ?></button></form>
                <form method="post" action="index.php?tab=promociones"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="promo_mover"><input type="hidden" name="pid" value="<?= e($p['id']) ?>"><input type="hidden" name="dir" value="arriba"><button class="ico" title="Subir" <?= $i === 0 ? 'disabled' : '' ?>>▲</button></form>
                <form method="post" action="index.php?tab=promociones"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="promo_mover"><input type="hidden" name="pid" value="<?= e($p['id']) ?>"><input type="hidden" name="dir" value="abajo"><button class="ico" title="Bajar" <?= $i === count($d['promociones']) - 1 ? 'disabled' : '' ?>>▼</button></form>
                <form method="post" action="index.php?tab=promociones" onsubmit="return confirm('¿Eliminar esta promoción? No se puede deshacer.');"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="promo_borrar"><input type="hidden" name="pid" value="<?= e($p['id']) ?>"><button class="btn btn--danger">Eliminar</button></form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($mostrarForm): ?>
        <form class="box form" id="form" method="post" action="index.php?tab=promociones" enctype="multipart/form-data"><?= campo_csrf($csrf) ?>
          <input type="hidden" name="accion" value="promo_guardar">
          <input type="hidden" name="pid" value="<?= e($actual['id'] ?? '') ?>">
          <h2><?= $actual ? 'Editar promoción' : 'Nueva promoción' ?></h2>
          <label>Título *<input name="titulo" maxlength="80" required value="<?= e($v['titulo'] ?? '') ?>" placeholder="Ej. Promo camisetas de fútbol"></label>
          <label>Descripción<textarea name="descripcion" maxlength="400" rows="3" placeholder="Ej. Juego completo de camiseta y short sublimado con nombre y número."><?= e($v['descripcion'] ?? '') ?></textarea></label>
          <div class="cols">
            <label>Precio anterior (USD)<input name="precio_antes" inputmode="decimal" value="<?= e($v['precio_antes'] ?? '') ?>" placeholder="Ej. 25.00"></label>
            <label>Precio de promoción (USD)<input name="precio_ahora" inputmode="decimal" value="<?= e($v['precio_ahora'] ?? '') ?>" placeholder="Ej. 20.00"></label>
          </div>
          <label>Beneficio<input name="beneficio" maxlength="80" value="<?= e($v['beneficio'] ?? '') ?>" placeholder='Ej. "20% de descuento" o "Estampado gratis"'></label>
          <p class="hint">La ley pide indicar el <b>precio anterior y el nuevo</b>, o el <b>beneficio</b> que obtiene el cliente. Escribe siempre el precio <b>final, con IVA incluido</b>.</p>
          <div class="cols">
            <label>Desde *<input type="date" name="desde" required value="<?= e($v['desde'] ?? '') ?>"></label>
            <label>Hasta *<input type="date" name="hasta" required value="<?= e($v['hasta'] ?? '') ?>"></label>
          </div>
          <label>Condiciones<textarea name="condiciones" maxlength="1000" rows="4" placeholder="Ej. Mínimo 10 uniformes por pedido. Válido para tallas S a XL. Unidades limitadas: 50 juegos. No acumulable con otras promociones."><?= e($v['condiciones'] ?? '') ?></textarea></label>
          <p class="hint">Escribe aquí todo lo que limite la promoción: cantidad mínima, tallas, modelos, unidades disponibles, etc. Lo que no esté escrito aquí no se le puede exigir al cliente.</p>
          <label>Foto<?= !empty($actual['foto']) ? ' (déjalo vacío para mantener la actual)' : '' ?><input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label>
          <?php if (!empty($actual['foto'])): ?>
            <div class="cur"><img src="../<?= e($actual['foto']) ?>" alt=""><label class="check"><input type="checkbox" name="quitar_foto" value="1"> Quitar la foto actual</label></div>
          <?php endif; ?>
          <label class="check"><input type="checkbox" name="activo" value="1" <?= !empty($v['activo']) ? 'checked' : '' ?>> Mostrar en la página (dentro de sus fechas)</label>
          <div class="row">
            <button class="btn btn--primary" type="submit">Guardar promoción</button>
            <a class="btn btn--light" href="?tab=promociones">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>

    <?php elseif ($tab === 'educativos'): ?>
      <h1>Uniformes escolares</h1>
      <p class="hint">Para cada institución sube su <b>portada o escudo</b> (la imagen de su tarjeta) y las fotos de sus tres uniformes, que se ven al pulsar <b>"Ver uniformes"</b>. La foto se guarda apenas la eliges. Si un uniforme no tiene foto, la página muestra "Foto próximamente".</p>
      <?php foreach ($d['secciones']['educativos'] as $s): ?>
        <section class="box" id="s-<?= e($s['id']) ?>">
          <div class="box__head"><h2><?= e($s['nombre']) ?></h2></div>
          <div class="slots">
            <?= ranura_foto($csrf, $tab, $s['id'], 'Portada / escudo', $s['portada'] ?? '', 'portada_subir', 'portada_quitar', '', 'Imagen de la tarjeta.') ?>
            <?php foreach (TIPOS_UNIFORME as $t => $etq): ?>
              <?= ranura_foto($csrf, $tab, $s['id'], $etq, $s['uniformes'][$t] ?? '', 'uniforme_subir', 'uniforme_quitar', $t) ?>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

    <?php elseif ($tab === 'hero'): $fotos = $d['hero'] ?? []; ?>
      <h1>Fondo del inicio</h1>
      <p class="hint">Fotos de deportistas que se ven de fondo en la parte principal de la página y <b>cambian solas</b> cada pocos segundos. Usa fotos <b>horizontales</b>, de buena calidad y donde se vea bien al deportista. Máximo <?= HERO_MAX ?>. Si no hay ninguna, se usa la imagen principal por defecto del sitio.</p>
      <section class="box">
        <div class="box__head"><h2>Fotos del fondo</h2><span class="pill"><?= count($fotos) ?> / <?= HERO_MAX ?></span></div>
        <?php if (!$fotos): ?><p class="empty">Todavía no hay fotos.</p><?php endif; ?>
        <div class="grid">
          <?php foreach ($fotos as $i => $f): ?>
            <figure class="ph">
              <img src="../<?= e($f) ?>" alt="" loading="lazy">
              <figcaption>
                <span class="ph__n"><?= $i + 1 ?></span>
                <form method="post" action="index.php?tab=hero"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="hero_mover"><input type="hidden" name="i" value="<?= $i ?>"><input type="hidden" name="dir" value="arriba"><button class="ico" title="Mover antes" <?= $i === 0 ? 'disabled' : '' ?>>◀</button></form>
                <form method="post" action="index.php?tab=hero"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="hero_mover"><input type="hidden" name="i" value="<?= $i ?>"><input type="hidden" name="dir" value="abajo"><button class="ico" title="Mover después" <?= $i === count($fotos) - 1 ? 'disabled' : '' ?>>▶</button></form>
                <form method="post" action="index.php?tab=hero" onsubmit="return confirm('¿Eliminar esta foto? No se puede deshacer.');"><?= campo_csrf($csrf) ?><input type="hidden" name="accion" value="hero_borrar"><input type="hidden" name="i" value="<?= $i ?>"><button class="ico ico--del" title="Eliminar">🗑</button></form>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
        <?php if (count($fotos) < HERO_MAX): ?>
          <form class="upload" method="post" action="index.php?tab=hero" enctype="multipart/form-data"><?= campo_csrf($csrf) ?>
            <input type="hidden" name="accion" value="hero_subir">
            <label class="upload__pick">＋ Elegir fotos<input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required onchange="this.closest('form').querySelector('.upload__info').textContent = this.files.length + ' foto(s) elegida(s)'"></label>
            <span class="upload__info">Puedes elegir varias a la vez.</span>
            <button class="btn btn--primary" type="submit">Subir</button>
          </form>
        <?php endif; ?>
      </section>

    <?php elseif ($tab === 'redes'): $r = $d['redes'] ?? []; ?>
      <h1>Redes sociales</h1>
      <p class="hint">Pega el enlace de tu página de Facebook y de tu perfil de Instagram. Los iconos del pie de la página llevarán a esas direcciones. Si dejas un campo vacío, ese icono no se muestra.</p>
      <form class="box form form--narrow" method="post" action="index.php?tab=redes"><?= campo_csrf($csrf) ?>
        <input type="hidden" name="accion" value="redes_guardar">
        <label>Facebook<input type="text" name="facebook" maxlength="300" value="<?= e($r['facebook'] ?? '') ?>" placeholder="https://www.facebook.com/tu-pagina"></label>
        <label>Instagram<input type="text" name="instagram" maxlength="300" value="<?= e($r['instagram'] ?? '') ?>" placeholder="https://www.instagram.com/tu-usuario"></label>
        <button class="btn btn--primary" type="submit">Guardar</button>
      </form>

    <?php else: /* cuenta */ ?>
      <h1><?= !empty($_SESSION['cambiar']) ? 'Crea tu contraseña' : 'Cambiar contraseña' ?></h1>
      <?php if (!empty($_SESSION['cambiar'])): ?><p class="hint">Es tu primer ingreso. Crea una contraseña propia; la clave inicial dejará de funcionar.</p><?php endif; ?>
      <form class="box form form--narrow" method="post" action="index.php?tab=cuenta"><?= campo_csrf($csrf) ?>
        <input type="hidden" name="accion" value="clave">
        <?php if ($tienePass): ?><label>Contraseña actual<input type="password" name="actual" required autocomplete="current-password"></label><?php endif; ?>
        <label>Nueva contraseña (mínimo 8 caracteres)<input type="password" name="nueva" minlength="8" required autocomplete="new-password"></label>
        <label>Repite la nueva contraseña<input type="password" name="repetir" minlength="8" required autocomplete="new-password"></label>
        <button class="btn btn--primary" type="submit">Guardar contraseña</button>
      </form>
    <?php endif; ?>
  </main>
<?php endif; ?>
</body>
</html>
