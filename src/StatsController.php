<?php

class StatsController
{
    public static function index(): void
    {
        $pdo = Database::connect();

        // Todas las claves a 0 de partida, para que siempre aparezcan
        $bySeverity = ['low' => 0, 'medium' => 0, 'high' => 0];
        $byStatus   = ['open' => 0, 'in_progress' => 0, 'closed' => 0];

        $rows = $pdo->query('SELECT severity, COUNT(*) AS total FROM incidents GROUP BY severity')->fetchAll();
        foreach ($rows as $row) {
            $bySeverity[$row['severity']] = (int) $row['total'];
        }

        $rows = $pdo->query('SELECT status, COUNT(*) AS total FROM incidents GROUP BY status')->fetchAll();
        foreach ($rows as $row) {
            $byStatus[$row['status']] = (int) $row['total'];
        }

        Response::json([
            'by_severity' => $bySeverity,
            'by_status'   => $byStatus,
        ]);
    }
}