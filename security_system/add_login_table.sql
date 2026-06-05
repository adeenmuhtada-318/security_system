-- =====================================================
-- STEP 1: Run this SQL in phpMyAdmin
-- Adds the AdminUsers table + one default user
-- =====================================================

USE security_firm;

CREATE TABLE IF NOT EXISTS AdminUsers (
    UserID        INT AUTO_INCREMENT PRIMARY KEY,
    Username      VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash  VARCHAR(255) NOT NULL,
    FullName      VARCHAR(100) NOT NULL,
    CreatedAt     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default login: username = admin, password = admin123
-- (password_hash of 'admin123' using PHP PASSWORD_DEFAULT / bcrypt)
INSERT INTO AdminUsers (Username, PasswordHash, FullName)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System Administrator'
);

-- =====================================================
-- Default Credentials:
-- Username : admin
-- Password : admin123
-- =====================================================
