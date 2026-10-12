<?php
require __DIR__ . '/../src/Response.php';
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/RoadController.php';
require __DIR__ . '/../src/IncidentController.php';
require __DIR__ . '/../src/StatsController.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($method=== 'GET' && $path === '/roads') {
   	    RoadController::index(); 
    }

   if ($method=== 'POST' && $path === '/roads') {
        RoadController::store(); 
    }

    if ($method=== 'GET' && $path === '/incidents') {
        IncidentController::index(); 
    }

    if ($method === 'GET' && preg_match('#^/incidents/(\d+)$#', $path, $m)) {
    	IncidentController::show((int) $m[1]);
    }

    if ($method === 'POST' && $path === '/incidents') {
        IncidentController::store();
    }

    if ($method === 'DELETE' && preg_match('#^/incidents/(\d+)$#', $path, $m)) {
        IncidentController::destroy((int) $m[1]);
    }


    if ($method === 'PUT' && preg_match('#^/incidents/(\d+)$#', $path, $m)) {
        IncidentController::update((int) $m[1]);
    }

    if ($method === 'GET' && $path === '/stats') {
        StatsController::index();
    }

	Response::error('Ruta no encontrada', 404);
   
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::error('Error interno', 500);
}
