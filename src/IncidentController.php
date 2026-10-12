<?php

class IncidentController
{
    
   public static function index(): void
   {
        $pdo    = Database::connect();
        $where  = [];
        $params = [];
        $errors = [];

        // status
        if (isset($_GET['status'])) {
            if (!is_string($_GET['status']) || !in_array($_GET['status'], ['open', 'in_progress', 'closed'], true)) {
                $errors['status'] = 'valor no permitido';
            } else {
                $where[]          = 'status = :status';
                $params['status'] = $_GET['status'];
            }
        }

        if (isset($_GET['severity'])) {
            if (!is_string($_GET['severity']) || !in_array($_GET['severity'], ['low', 'medium', 'high'], true)) {
                $errors['severity'] = 'valor no permitido';
            } else {
                $where[]          = 'severity = :severity';
                $params['severity'] = $_GET['severity'];
            }
        }


        // road_id
        if (isset($_GET['road_id'])) {
            if (!ctype_digit((string) $_GET['road_id']) || (int) $_GET['road_id'] < 1) {
                $errors['road_id'] = 'debe ser un entero positivo';
            } else {
                $where[]           = 'road_id = :road_id';
                $params['road_id'] = (int) $_GET['road_id'];
            }
        }

        if ($errors) {
            Response::error('Filtros no válidos', 422, $errors);
        }

        $sql = 'SELECT id, road_id, type, severity, status, km, description, created_at FROM incidents';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    public static function show(int $id): void
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT id, road_id, type, severity, status, km, description, created_at FROM incidents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            Response::error('Incidencia no encontrada', 404);
        }

        Response::json($row);
    }



    public static function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            Response::error('El cuerpo debe ser un JSON válido', 400);
        }

        $errors = [];
        $pdo    = Database::connect();


        $type = isset($input['type']) && is_string($input['type']) ? trim($input['type']) : '';
        if ($type === '') {
            $errors['type'] = 'es obligatorio';
        } elseif (mb_strlen($type) > 50) {
            $errors['type'] = 'máximo 50 caracteres';
        }

        // severity: obligatoria, lista blanca
        $severity = $input['severity'] ?? null;
        if ($severity === null) {
            $errors['severity'] = 'es obligatoria';
        } elseif (!is_string($severity) || !in_array($severity, ['low', 'medium', 'high'], true)) {
            $errors['severity'] = 'valor no permitido';
        }

        // status: opcional, por defecto 'open'
        $status = $input['status'] ?? 'open';
        if (!is_string($status) || !in_array($status, ['open', 'in_progress', 'closed'], true)) {
            $errors['status'] = 'valor no permitido';
        }

        // road_id: entero positivo y que exista
        $roadId = $input['road_id'] ?? null;
        if ($roadId === null) {
            $errors['road_id'] = 'es obligatorio';
        } elseif (!is_int($roadId) || $roadId < 1) {
            $errors['road_id'] = 'debe ser un entero positivo';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM roads WHERE id = :id');
            $stmt->execute(['id' => $roadId]);
            if (!$stmt->fetch()) {
                $errors['road_id'] = 'la carretera no existe';
            }
        }

        // km: opcional, numérico, entre 0 y 9999.99
        $km = $input['km'] ?? null;
        if ($km !== null) {
            if (!is_numeric($km) || $km < 0 || $km > 9999.99) {
                $errors['km'] = 'debe ser un número entre 0 y 9999.99';
            } else {
                $km = (float) $km;
            }
        }

        // description: opcional, string
        $description = $input['description'] ?? null;
        if ($description !== null && !is_string($description)) {
            $errors['description'] = 'debe ser texto';
        }

        
        if ($errors) {
            Response::error('Datos no válidos', 422, $errors);
        }

        $stmt = $pdo->prepare('INSERT INTO incidents (road_id, type, severity, status, km, description) 
                                VALUES (:road_id, :type, :severity, :status, :km, :description)');
        
        $stmt->execute([
            'road_id'     => $roadId,
            'type'        => $type,
            'severity'    => $severity,
            'status'      => $status,
            'km'          => $km,
            'description' => $description,
        ]);

        $id   = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, road_id, type, severity, status, km, description, created_at FROM incidents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        Response::json($stmt->fetch(), 201);
    }

    public static function destroy(int $id): void
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('DELETE FROM incidents WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            Response::error('Incidencia no encontrada', 404);
        }

        Response::json(['deleted' => $id]);
    }


    public static function update(int $id): void
    {
        $pdo = Database::connect();

        // 1. ¿existe?
        $stmt = $pdo->prepare('SELECT id FROM incidents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Incidencia no encontrada', 404);
        }

        // 2. cuerpo JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            Response::error('El cuerpo debe ser un JSON válido', 400);
        }

        $sets   = [];
        $params = [];
        $errors = [];

        // status
        if (array_key_exists('status', $input)) {
            if (!is_string($input['status']) || !in_array($input['status'], ['open', 'in_progress', 'closed'], true)) {
                $errors['status'] = 'valor no permitido';
            } else {
                $sets[]           = 'status = :status';
                $params['status'] = $input['status'];
            }
        }

        // severity
        if (array_key_exists('severity', $input)) {
            if (!is_string($input['severity']) || !in_array($input['severity'], ['low', 'medium', 'high'], true)) {
                $errors['severity'] = 'valor no permitido';
            } else {
                $sets[]             = 'severity = :severity';
                $params['severity'] = $input['severity'];
            }
        }

        // type
        if (array_key_exists('type', $input)) {
            $type = is_string($input['type']) ? trim($input['type']) : '';
            if ($type === '') {
                $errors['type'] = 'no puede estar vacío';
            } elseif (mb_strlen($type) > 50) {
                $errors['type'] = 'máximo 50 caracteres';
            } else {
                $sets[]         = 'type = :type';
                $params['type'] = $type;
            }
        }

        // km
        if (array_key_exists('km', $input)) {
            if (!is_numeric($input['km']) || $input['km'] < 0 || $input['km'] > 9999.99) {
                $errors['km'] = 'debe ser un número entre 0 y 9999.99';
            } else {
                $sets[]       = 'km = :km';
                $params['km'] = (float) $input['km'];
            }
        }

        // description
        if (array_key_exists('description', $input)) {
            if (!is_string($input['description'])) {
                $errors['description'] = 'debe ser texto';
            } else {
                $sets[]                = 'description = :description';
                $params['description'] = $input['description'];
            }
        }

        if ($errors) {
            Response::error('Datos no válidos', 422, $errors);
        }
        if (!$sets) {
            Response::error('No hay campos que actualizar', 422);
        }

        // UPDATE dinámico: $sets solo lleva fragmentos fijos, los valores van en $params
        $params['id'] = $id;
        $sql  = 'UPDATE incidents SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // devolver la fila actualizada
        self::show($id);
    }
}
