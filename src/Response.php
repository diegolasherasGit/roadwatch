<?php

class Response
{
    private static function send(array $body, int $status): void
    {
	http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');  
        echo json_encode($body, JSON_UNESCAPED_UNICODE); 
        exit;
    }

    public static function json(mixed $data, int $status = 200): void
    {
	self::send(['data' => $data], $status);
    }

    public static function error(string $message, int $status, array $details = []): void
    {
	self::send(['error' => ['message' => $message, 'details' => (object) $details]], $status);
    }
}
