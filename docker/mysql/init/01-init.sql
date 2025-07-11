-- MySQL initialization script for AMT CRM Backend
-- This script runs when the MySQL container starts for the first time

-- Set character set and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET character_set_connection=utf8mb4;

-- Create additional databases if needed
-- CREATE DATABASE IF NOT EXISTS amt_crm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant additional permissions if needed
-- GRANT ALL PRIVILEGES ON amt_crm_test.* TO 'amt_crm_user'@'%';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

-- Show current databases
SHOW DATABASES; 