<?php

class RoadController
{
    public static function index(): void
    {
        $pdo   = Database::connect();
        $roads = $pdo->query('SELECT id, code, name FROM roads ORDER BY code') ->fetchAll();

        Response::json($roads);
    }

    public static function store(): void
    {
        // Leer el cuerpo JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            Response::error('El cuerpo debe ser un JSON válido', 400);
        }

        // Normalizar
        $code = isset($input['code']) && is_string($input['code']) ? trim($input['code']) : '';
        $name = isset($input['name']) && is_string($input['name']) ? trim($input['name']) : '';

        // Validar
        $errors = [];

        if ($code === '') {
            $errors['code'] = 'es obligatorio';
        } elseif (mb_strlen($code) > 20) {
            $errors['code'] = 'máximo 20 caracteres';
        }

        if ($name === '') {
            $errors['name'] = 'es obligatorio';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'máximo 100 caracteres';
        }

        if ($errors) {
            Response::error('Datos no válidos', 422, $errors);
        }

        // Insertar con consulta preparada
        $pdo = Database::connect();

        try {
            $stmt = $pdo->prepare('INSERT INTO roads (code, name) VALUES (:code, :name)');
            $stmt->execute(['code' => $code, 'name' => $name]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                Response::error('Datos no válidos', 409, ['code' => 'ya existe']);
            }
            throw $e;
        }

         Response::json([
            'id'   => (int) $pdo->lastInsertId(),
            'code' => $code,
            'name' => $name,
        ], 201);
    }
}
    
