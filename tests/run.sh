#!/bin/bash
# Pruebas de integración de Roadwatch: lanza peticiones reales y compara códigos HTTP.
# Uso (desde la raíz del proyecto): ./tests/run.sh

BASE="http://localhost:8080"
PASS=0
FAIL=0

# check "descripción" código_esperado método ruta [cuerpo]
check() {
    local desc="$1" expected="$2" method="$3" path="$4" body="$5"
    local code
    if [ -n "$body" ]; then
        code=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$BASE$path" \
               -H "Content-Type: application/json" -d "$body")
    else
        code=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$BASE$path")
    fi

    if [ "$code" = "$expected" ]; then
        echo "OK   $desc"
        PASS=$((PASS+1))
    else
        echo "FAIL $desc (esperado $expected, obtenido $code)"
        FAIL=$((FAIL+1))
    fi
}

# Partimos siempre de un estado conocido (el seed)
docker compose exec -T web php scripts/reset.php > /dev/null

echo "== Roads =="
check "GET /roads"                         200 GET  /roads
check "POST /roads válido"                 201 POST /roads '{"code":"TEST-1","name":"Prueba"}'
check "POST /roads código repetido"        409 POST /roads '{"code":"TEST-1","name":"Repetida"}'
check "POST /roads sin code"               422 POST /roads '{"name":"Sin código"}'
check "POST /roads JSON inválido"          400 POST /roads 'esto no es json'

echo
echo "== Incidents: lectura y filtros =="
check "GET /incidents"                     200 GET  /incidents
check "GET /incidents?status=open"         200 GET  "/incidents?status=open&severity=high"
check "GET /incidents?road_id=1"           200 GET  "/incidents?road_id=1"
check "GET filtro status inválido"         422 GET  "/incidents?status=cualquiera"
check "GET filtro road_id inválido"        422 GET  "/incidents?road_id=abc"
check "GET /incidents/1"                   200 GET  /incidents/1
check "GET /incidents/999"                 404 GET  /incidents/999

echo
echo "== Incidents: escritura =="
check "POST /incidents válido"             201 POST /incidents '{"road_id":1,"type":"bache","severity":"low","km":12.5,"description":"Prueba"}'
check "POST /incidents road_id inexistente" 422 POST /incidents '{"road_id":99,"type":"bache","severity":"low"}'
check "POST /incidents severity inválida"  422 POST /incidents '{"road_id":1,"type":"bache","severity":"enorme"}'
check "POST /incidents sin type"           422 POST /incidents '{"road_id":1,"severity":"low"}'
check "POST /incidents km fuera de rango"  422 POST /incidents '{"road_id":1,"type":"bache","severity":"low","km":99999}'
check "POST /incidents JSON inválido"      400 POST /incidents 'no es json'

check "PUT /incidents/1 status"            200 PUT  /incidents/1 '{"status":"closed"}'
check "PUT /incidents/1 varios campos"     200 PUT  /incidents/1 '{"severity":"low","km":50.5,"type":"obras"}'
check "PUT /incidents/1 status inválido"   422 PUT  /incidents/1 '{"status":"cualquiera"}'
check "PUT /incidents/1 vacío"             422 PUT  /incidents/1 '{}'
check "PUT /incidents/999 no existe"       404 PUT  /incidents/999 '{"status":"closed"}'
check "PUT /incidents/1 JSON inválido"     400 PUT  /incidents/1 'no es json'

check "DELETE /incidents/1"                200 DELETE /incidents/1
check "DELETE /incidents/1 otra vez"       404 DELETE /incidents/1
check "GET /incidents/1 tras borrar"       404 GET  /incidents/1

echo
echo "== Stats y rutas =="
check "GET /stats"                         200 GET  /stats
check "Ruta inexistente"                   404 GET  /nada
check "Método no soportado"                404 PATCH /roads

echo
echo "Pasaron: $PASS  Fallaron: $FAIL"
[ "$FAIL" -eq 0 ]