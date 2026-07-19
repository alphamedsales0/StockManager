<?php
// api.php
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Retirer le préfixe du chemin si nécessaire (ex: /api)
// Ici on suppose que le serveur est configuré pour que /api/... pointe vers ce fichier

if (preg_match('#^/api/employees$#', $request) && $method === 'GET') {
    require 'employees_list.php';
} elseif (preg_match('#^/api/employees/(\d+)$#', $request, $matches) && $method === 'GET') {
    $_GET['id'] = $matches[1];
    require 'employees_get.php';
} elseif (preg_match('#^/api/employees$#', $request) && $method === 'POST') {
    require 'employees_create.php';
} elseif (preg_match('#^/api/employees/(\d+)$#', $request, $matches) && $method === 'PUT') {
    $_GET['id'] = $matches[1];
    require 'employees_update.php';
} elseif (preg_match('#^/api/employees/(\d+)$#', $request, $matches) && $method === 'DELETE') {
    $_GET['id'] = $matches[1];
    require 'employees_delete.php';
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint nicht gefunden']);
}