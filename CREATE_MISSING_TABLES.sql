-- Script para crear las tablas faltantes
-- Ejecutar en MySQL:
-- mysql -u root -p db_wut_admissions < CREATE_MISSING_TABLES.sql

USE db_wut_admissions;

-- Crear tabla rate_limiting si no existe
CREATE TABLE IF NOT EXISTS rate_limiting (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE KEY,
    endpoint VARCHAR(150) NOT NULL,
    request_count INT DEFAULT 1,
    first_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    is_blocked BOOLEAN DEFAULT FALSE,
    block_reason VARCHAR(155),
    user_agent VARCHAR(200),
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_blocked (is_blocked),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_last_request (last_request_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla ip_logs si no existe
CREATE TABLE IF NOT EXISTS ip_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    country_code VARCHAR(5),
    country_name VARCHAR(100),
    isp_provider VARCHAR(255),
    organization VARCHAR(155),
    timezone VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_vpn BOOLEAN DEFAULT FALSE,
    is_proxy BOOLEAN DEFAULT FALSE,
    is_bot BOOLEAN DEFAULT FALSE,
    user_agent VARCHAR(500),
    referer VARCHAR(100),
    request_body LONGTEXT,
    response_code INT,
    response_time INT,
    api_key_used VARCHAR(155),
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

-- Crear tabla api_keys si no existe
CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(155) NOT NULL UNIQUE,
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

-- Crear tabla api_key_logs si no existe
CREATE TABLE IF NOT EXISTS api_key_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(155) NOT NULL,
    endpoint VARCHAR(155) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    status_code INT,
    response_time INT,
    request_size INT,
    response_size INT,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_created_at (created_at),
    INDEX idx_endpoint (endpoint),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verificar que las tablas fueron creadas
SHOW TABLES;
