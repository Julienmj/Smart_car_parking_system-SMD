-- Smart Car Parking System Database Schema
-- Database: parking_system

CREATE DATABASE IF NOT EXISTS parking_system;
USE parking_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parking slots table
CREATE TABLE parking_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_code VARCHAR(10) UNIQUE NOT NULL,
    slot_type ENUM('standard', 'VIP', 'disabled') NOT NULL DEFAULT 'standard',
    status ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available',
    floor INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parking sessions table
CREATE TABLE parking_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    plate_number VARCHAR(20) NOT NULL DEFAULT '',
    checkin_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    checkout_time DATETIME NULL,
    fee_amount DECIMAL(10, 2) NULL,
    status ENUM('active', 'completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id) ON DELETE CASCADE
);

-- Add plate_number if upgrading existing install
ALTER TABLE parking_sessions ADD COLUMN IF NOT EXISTS plate_number VARCHAR(20) NOT NULL DEFAULT '' AFTER slot_id;

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile') NOT NULL,
    payment_status ENUM('paid', 'pending') NOT NULL DEFAULT 'paid',
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES parking_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed data

-- Insert admin user (password: admin123)
INSERT INTO users (full_name, email, password, role, is_active) VALUES 
('System Administrator', 'admin@parking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);

-- Insert sample parking slots (20 slots across 3 floors)
INSERT INTO parking_slots (slot_code, slot_type, status, floor) VALUES
-- Floor 1 - Standard slots
('A1', 'standard', 'available', 1),
('A2', 'standard', 'available', 1),
('A3', 'standard', 'occupied', 1),
('A4', 'standard', 'available', 1),
('A5', 'standard', 'available', 1),
('A6', 'standard', 'available', 1),
('A7', 'disabled', 'available', 1),
('A8', 'disabled', 'available', 1),

-- Floor 2 - Mix of standard and VIP
('B1', 'standard', 'available', 2),
('B2', 'standard', 'occupied', 2),
('B3', 'VIP', 'available', 2),
('B4', 'VIP', 'available', 2),
('B5', 'standard', 'available', 2),
('B6', 'standard', 'available', 2),
('B7', 'disabled', 'available', 2),

-- Floor 3 - VIP and standard
('C1', 'VIP', 'available', 3),
('C2', 'VIP', 'occupied', 3),
('C3', 'standard', 'available', 3),
('C4', 'standard', 'available', 3),
('C5', 'standard', 'available', 3);

-- Insert some sample sessions for testing
INSERT INTO parking_sessions (user_id, slot_id, status, fee_amount) VALUES
(1, 3, 'completed', 400.00),
(1, 10, 'completed', 200.00),
(1, 18, 'active', NULL);

-- Insert corresponding payments
INSERT INTO payments (session_id, user_id, amount, payment_method) VALUES
(1, 1, 400.00, 'card'),
(2, 1, 200.00, 'cash');
