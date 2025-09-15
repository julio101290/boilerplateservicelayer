<?php
// scripts/install-assets.php

// Ejecutado desde la raíz del proyecto (getcwd() devuelve el project root)
$projectRoot = getcwd();

// Ruta de los assets dentro del paquete (este archivo está en vendor/.../scripts)
$packageAssets = realpath(__DIR__ . '/../assets'); // ajusta si usas otra carpeta, ej: src/Assets

if (!$packageAssets || !is_dir($packageAssets)) {
    echo "No se encontraron assets en {$packageAssets}\n";
    exit(0);
}

// Leer composer.json del proyecto para permitir override de public dir
$composerJsonPath = $projectRoot . '/composer.json';
$publicDir = 'public';
if (file_exists($composerJsonPath)) {
    $cj = json_decode(file_get_contents($composerJsonPath), true);
    if (!empty($cj['extra']['public-dir'])) {
        $publicDir = $cj['extra']['public-dir'];
    }
}

$destBase = $projectRoot . DIRECTORY_SEPARATOR . $publicDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'julio101290' . DIRECTORY_SEPARATOR . 'boilerplateproducts';

// Intentar crear symlink (útil en desarrollo), si falla copiar recursivamente
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object == "." || $object == "..") continue;
        $path = $dir . DIRECTORY_SEPARATOR . $object;
        if (is_dir($path)) rrmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}

function rcopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while(false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcPath)) {
                rcopy($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    closedir($dir);
}

// Remover destino anterior (opcional) y desplegar
if (file_exists($destBase) || is_link($destBase)) {
    // eliminar carpeta/vínculo anterior
    if (is_link($destBase)) {
        @unlink($destBase);
    } else {
        rrmdir($destBase);
    }
}

echo "Instalando assets desde {$packageAssets} → {$destBase}\n";

// Intentar symlink (sólo si el sistema y permisos lo permiten)
$linked = false;
if (function_exists('symlink')) {
    try {
        // El symlink puede fallar en Windows sin privilegios; por eso lo envolvemos en try
        @symlink($packageAssets, $destBase);
        if (is_link($destBase) || file_exists($destBase)) {
            echo "Se creó un symlink en {$destBase}\n";
            $linked = true;
        }
    } catch (\Throwable $e) {
        $linked = false;
    }
}

if (!$linked) {
    // Copiar archivos (modo seguro y cross-platform)
    rcopy($packageAssets, $destBase);
    echo "Assets copiados a {$destBase}\n";
}

echo "Instalación de assets finalizada.\n";
