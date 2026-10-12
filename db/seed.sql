SET NAMES utf8mb4;

INSERT INTO roads (code, name) VALUES
  ('A-23',  'Autovía Mudéjar'),
  ('N-232', 'Nacional 232'),
  ('A-2',   'Autovía del Nordeste'),
  ('AP-68', 'Autopista del Ebro');

INSERT INTO incidents (road_id, type, severity, status, km, description) VALUES
  (1, 'accidente',   'high',   'open',        45.50, 'Colisión múltiple en el carril derecho'),
  (1, 'obras',       'medium', 'in_progress', 60.00, 'Reasfaltado en el carril lento'),
  (1, 'bache',       'low',    'closed',      12.30, 'Bache reparado en el arcén'),
  (2, 'desprendimiento', 'high', 'in_progress', 88.75, 'Rocas en la calzada, carril cortado'),
  (2, 'niebla',      'medium', 'open',        102.00, 'Visibilidad reducida en el puerto'),
  (3, 'accidente',   'medium', 'closed',      230.40, 'Alcance entre dos turismos, sin heridos'),
  (3, 'obras',       'low',    'open',        245.00, 'Señalización de obras en el arcén'),
  (3, 'vehículo averiado', 'low', 'closed',   198.20, 'Camión averiado en el carril derecho'),
  (4, 'accidente',   'high',   'open',        33.10, 'Vuelco de camión, tráfico cortado'),
  (4, 'bache',       'medium', 'in_progress', 71.60, 'Hundimiento del firme en el carril izquierdo');
