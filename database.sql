-- Crear base de datos
CREATE DATABASE IF NOT EXISTS db_wut_admissions;
USE db_wut_admissions;

-- Crear tabla users con seguridad empresarial
CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos básicos de autenticación
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Control de seguridad
    is_active BOOLEAN DEFAULT TRUE,
    block_until TIMESTAMP NULL,
    failed_attempts INT DEFAULT 0,
    last_ip_address VARCHAR(45),
    last_user_agent VARCHAR(500),
    status_id INT DEFAULT 1,
    
    -- Seguridad avanzada
    role VARCHAR(50) DEFAULT 'user',
    last_login_at TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    login_attempts_window TIMESTAMP NULL,
    account_locked_until TIMESTAMP NULL,
    
    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Índices para performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_block_until (block_until),
    INDEX idx_last_login (last_login_at),
    INDEX idx_status_id (status_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuario admin por defecto
-- Password: Admin@123 (hash: $2y$10$... - bcrypt)
INSERT INTO users (username, email, password_hash, role, is_active, status_id, created_at) 
VALUES (
    'admin',
    'admin@wut-admission.local',
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/BeC',
    'admin',
    TRUE,
    1,
    CURRENT_TIMESTAMP
);


-- Crear tabla admission
CREATE TABLE IF NOT EXISTS admission (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    type_document VARCHAR(50) NOT NULL,
    document VARCHAR(25) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150) NOT NULL,
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    program VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla contact
CREATE TABLE IF NOT EXISTS contact (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    affair VARCHAR(100) NOT NULL,
    message VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla rate_limiting para controlar peticiones por IP
CREATE TABLE IF NOT EXISTS rate_limiting (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE KEY,
    endpoint VARCHAR(255) NOT NULL,
    request_count INT DEFAULT 1,
    first_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    is_blocked BOOLEAN DEFAULT FALSE,
    block_reason VARCHAR(255),
    user_agent VARCHAR(500),
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_blocked (is_blocked),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_last_request (last_request_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla ip_logs para almacenar datos de IP, país, proveedor (Anti-Fraude)
CREATE TABLE IF NOT EXISTS ip_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    country_code VARCHAR(5),
    country_name VARCHAR(100),
    isp_provider VARCHAR(255),
    organization VARCHAR(255),
    timezone VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_vpn BOOLEAN DEFAULT FALSE,
    is_proxy BOOLEAN DEFAULT FALSE,
    is_bot BOOLEAN DEFAULT FALSE,
    user_agent VARCHAR(500),
    referer VARCHAR(500),
    request_body LONGTEXT,
    response_code INT,
    response_time INT,
    api_key_used VARCHAR(255),
    fraud_score INT DEFAULT 0,
    is_suspicious BOOLEAN DEFAULT FALSE,
    threat_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_address (ip_address),
    INDEX idx_country (country_code),
    INDEX idx_is_suspicious (is_suspicious),
    INDEX idx_is_vpn (is_vpn),
    INDEX idx_created_at (created_at),
    INDEX idx_fraud_score (fraud_score),
    INDEX idx_endpoint_method (endpoint, method),
    UNIQUE KEY uk_ip_endpoint_timestamp (ip_address, endpoint, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla api_keys para gestionar acceso a la API
CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    key_name VARCHAR(100) NOT NULL,
    description TEXT,
    client_name VARCHAR(150),
    client_email VARCHAR(150),
    client_phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    rate_limit INT DEFAULT 100,
    rate_limit_window VARCHAR(20) DEFAULT 'hourly',
    allowed_endpoints TEXT,
    allowed_methods VARCHAR(100) DEFAULT 'GET,POST,PUT,DELETE',
    allowed_ips TEXT,
    blocked_ips TEXT,
    last_used_at TIMESTAMP NULL,
    last_used_ip VARCHAR(45),
    created_by VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at),
    INDEX idx_client_email (client_email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla api_key_logs para auditar uso de claves
CREATE TABLE IF NOT EXISTS api_key_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    status_code INT,
    response_time INT,
    request_size INT,
    response_size INT,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key) REFERENCES api_keys(api_key) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_created_at (created_at),
    INDEX idx_endpoint (endpoint),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
