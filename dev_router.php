<?php
// dev_router.php
// Script de roteamento para servidor embutido do PHP
// Uso: php -S localhost:8000 -t public dev_router.php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Se o arquivo solicitado existir em public/, sirva-o diretamente
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Caso contrário, encaminhe para index.php
require_once __DIR__ . '/public/index.php';
