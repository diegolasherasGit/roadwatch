SET NAMES utf8mb4;

CREATE TABLE roads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE incidents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  road_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  severity ENUM('low','medium','high') NOT NULL,
  status ENUM('open','in_progress','closed') DEFAULT 'open',
  km DECIMAL(6,2),
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (road_id) REFERENCES roads(id),
  INDEX idx_status_severity (status, severity)
);
