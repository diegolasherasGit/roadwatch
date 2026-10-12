# Roadwatch

API REST para gestionar carreteras e incidencias viarias (accidentes, obras, baches...), con filtros, estadísticas y validación. Está inspirada en el dominio de la gestión de incidencias en carreteras y escrita en **PHP 8.3 puro (sin framework)**, con **PDO** y **MySQL 8**, todo ejecutable con **Docker Compose**.

## Arranque

Requisitos: [Docker](https://docs.docker.com/get-docker/) con Docker Compose.

```bash
docker compose up --build
```

- La API queda disponible en `http://localhost:8080`.
- Los datos de ejemplo (4 carreteras y 10 incidencias) se cargan solos la primera vez que arranca MySQL, desde `db/init.sql` (esquema) y `db/seed.sql` (datos).
- Para borrar los datos y empezar de cero: `docker compose down -v`.
- Para devolver los datos al estado inicial sin reiniciar los contenedores: `docker compose exec web php scripts/reset.php`.

> Las credenciales de `docker-compose.yml` son solo para desarrollo local.

## Estructura

```
roadwatch/
├── .gitignore
├── docker-compose.yml
├── Dockerfile
├── db/
│   ├── init.sql                 # tablas
│   └── seed.sql                 # datos de ejemplo
├── public/
│   ├── index.php                # punto de entrada y router
│   └── .htaccess                # envía todas las rutas a index.php
├── src/
│   ├── Database.php             # conexión PDO
│   ├── Response.php             # respuestas JSON
│   ├── RoadController.php
│   ├── IncidentController.php
│   └── StatsController.php
├── scripts/
│   └── reset.php                # restablece los datos de ejemplo
└── tests/
    └── run.sh                   # pruebas de integración con curl
```

## Endpoints

| Método | Ruta | Descripción | Códigos |
| -------- | ------ | ------------- | --------- |
| GET | `/roads` | Lista las carreteras | 200 |
| POST | `/roads` | Crea una carretera | 201, 400, 409, 422 |
| GET | `/incidents` | Lista incidencias, con filtros opcionales | 200, 422 |
| GET | `/incidents/{id}` | Detalle de una incidencia | 200, 404 |
| POST | `/incidents` | Crea una incidencia | 201, 400, 422 |
| PUT | `/incidents/{id}` | Edición parcial de una incidencia | 200, 400, 404, 422 |
| DELETE | `/incidents/{id}` | Borra una incidencia | 200, 404 |
| GET | `/stats` | Recuento por gravedad y por estado | 200 |

**Filtros de `GET /incidents`** (combinables): `status` (`open`, `in_progress`, `closed`), `severity` (`low`, `medium`, `high`) y `road_id` (entero positivo).

**Campos de una incidencia:**

| Campo | Obligatorio en POST | Regla |
| ------- | --------------------- | ------- |
| `road_id` | sí | entero positivo de una carretera existente |
| `type` | sí | texto, máximo 50 caracteres |
| `severity` | sí | `low`, `medium` o `high` |
| `status` | no (por defecto `open`) | `open`, `in_progress` o `closed` |
| `km` | no | número entre 0 y 9999.99 |
| `description` | no | texto |

En `PUT` todos los campos son opcionales (actualización parcial), pero debe llegar al menos uno. `road_id` no se puede modificar.

Otros códigos: `404` para rutas inexistentes y `500` para errores internos (sin detalles para el cliente).

## Ejemplos

Los valores de `created_at` dependen del momento en que se arranque la base de datos, así que en tu máquina serán distintos.

**Listar incidencias abiertas y graves:**

```bash
curl "http://localhost:8080/incidents?status=open&severity=high"
```

```json
{"data":[
  {"id":9,"road_id":4,"type":"accidente","severity":"high","status":"open","km":"33.10","description":"Vuelco de camión, tráfico cortado","created_at":"2026-10-12 08:03:48"},
  {"id":1,"road_id":1,"type":"accidente","severity":"high","status":"open","km":"45.50","description":"Colisión múltiple en el carril derecho","created_at":"2026-10-12 08:03:48"}
]}
```

**Crear una incidencia:**

```bash
curl -X POST http://localhost:8080/incidents \
  -H "Content-Type: application/json" \
  -d '{"road_id":1,"type":"bache","severity":"low","km":12.5,"description":"Bache en el arcén"}'
```

```json
{"data":{"id":11,"road_id":1,"type":"bache","severity":"low","status":"open","km":"12.50","description":"Bache en el arcén","created_at":"2026-10-12 08:04:43"}}
```

**Cambiar el estado con PUT:**

```bash
curl -X PUT http://localhost:8080/incidents/11 \
  -H "Content-Type: application/json" \
  -d '{"status":"closed"}'
```

```json
{"data":{"id":11,"road_id":1,"type":"bache","severity":"low","status":"closed","km":"12.50","description":"Bache en el arcén","created_at":"2026-10-12 08:04:43"}}
```

**Estadísticas** (tras crear y cerrar la incidencia 11 sobre los datos de ejemplo):

```bash
curl http://localhost:8080/stats
```

```json
{"data":{"by_severity":{"low":4,"medium":4,"high":3},"by_status":{"open":4,"in_progress":3,"closed":4}}}
```

> `km` se devuelve como texto (`"12.50"`): PDO devuelve así las columnas `DECIMAL` para no perder precisión.

## Formato de respuestas

Todas las respuestas son JSON, también los errores.

Éxito:

```json
{"data": ...}
```

Error:

```json
{"error": {"message": "Datos no válidos", "details": {"severity": "valor no permitido"}}}
```

`details` es un objeto con el motivo por campo en los errores de validación (422), y `{}` en el resto.

## Tests

Con la API en marcha (`docker compose up -d`), desde la raíz del proyecto:

```bash
chmod +x tests/run.sh
./tests/run.sh
```

El script restablece los datos con `scripts/reset.php`, así que cada ejecución parte del mismo estado. Lanza 30 peticiones reales con `curl` (lectura, filtros, creación, edición, borrado, `/stats` y rutas inexistentes), compara el código HTTP obtenido con el esperado y muestra `OK` o `FAIL`. Termina con código de salida distinto de 0 si algo falla.

Limitación: solo comprueba códigos HTTP, no el contenido de las respuestas.

## Decisiones de diseño

- **Consultas preparadas en todo.** Todas las consultas con datos del usuario usan `prepare` + `execute` con marcadores, y PDO está configurado con `EMULATE_PREPARES => false` para que las prepare el propio servidor MySQL. Los valores nunca se concatenan en el SQL.
- **Filtros y `UPDATE` dinámicos, pero seguros.** El `WHERE` de `/incidents` y el `SET` de `PUT` se construyen concatenando solo fragmentos fijos escritos en el código (`status = :status`); los valores viajan aparte, en el array de parámetros.
- **Validación con listas blancas.** `status` y `severity` solo aceptan valores permitidos, `road_id` debe ser un entero positivo de una carretera existente (se comprueba antes de insertar para devolver un 422 claro en vez de un error de clave foránea), y los campos se validan por tipo y longitud. La base de datos añade una segunda barrera (`ENUM`, `UNIQUE`, `FOREIGN KEY`).
- **Códigos HTTP con significado.** `400` para JSON mal formado, `422` para datos bien formados pero incorrectos, `409` para un código de carretera duplicado (error 1062 de MySQL), `404` para recursos o rutas inexistentes.
- **`public/` como raíz web.** Apache solo sirve esa carpeta, así que `src/`, `db/` y los scripts no son accesibles por URL. Todas las peticiones pasan por `index.php` (que hace de router) gracias a `.htaccess`.
- **Sin exponer errores internos.** Un `try/catch` global registra el detalle con `error_log` y responde un `500` genérico, sin SQL, rutas ni trazas.
- **Actualización parcial con `array_key_exists`.** En `PUT` se distingue "el campo no vino" (no se toca) de "vino como `null`" (se rechaza con 422), algo que `isset` no permite.
- **Índice `idx_status_severity`.** Acelera los filtros por estado y gravedad y el `GROUP BY status` de `/stats`.
