-- Insertar una API Key directamente en la BD
INSERT INTO api_keys (
    api_key, 
    key_name, 
    description,
    client_name, 
    client_email,
    is_active,
    rate_limit,
    allowed_endpoints, 
    allowed_methods,
    created_by,
    created_at
) VALUES (
    'sk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6',
    'Mi Primera API Key',
    'Clave para testing',
    'Cliente Test',
    'cliente@example.com',
    TRUE,
    100,
    '/contacts,/admissions',
    'GET,POST,PUT,DELETE',
    'admin',
    NOW()
);

-- Verificar que se creó correctamente
SELECT api_key, key_name, is_active, created_at FROM api_keys WHERE key_name = 'Mi Primera API Key';




-- Una verion mas simple
INSERT INTO api_keys (api_key, key_name, is_active, created_at) 
VALUES ('sk_test123456789abcdefghijklmnopqrstuvwxyz', 'Test API Key', TRUE, NOW());
