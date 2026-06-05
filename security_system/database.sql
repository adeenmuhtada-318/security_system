-- =====================================================
-- SECURITY FIRM MANAGEMENT SYSTEM - DATABASE SETUP
-- Run this entire file in phpMyAdmin SQL tab
-- =====================================================

CREATE DATABASE IF NOT EXISTS security_firm;
USE security_firm;

-- =====================================================
-- TABLE 1: Guards (Security Personnel)
-- =====================================================
CREATE TABLE Guards (
    GuardID       INT AUTO_INCREMENT PRIMARY KEY,
    FullName      VARCHAR(100) NOT NULL,
    CNIC          VARCHAR(15) NOT NULL UNIQUE,
    Phone         VARCHAR(20),
    EmergencyPhone VARCHAR(20),
    BloodGroup    VARCHAR(5),
    Address       TEXT,
    JoiningDate   DATE NOT NULL,
    CurrentShift  ENUM('Morning','Evening','Night','Off Duty') DEFAULT 'Off Duty',
    Status        ENUM('Active','Inactive') DEFAULT 'Active',
    IsArchived    TINYINT(1) DEFAULT 0,
    CreatedAt     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 2: Attendance Records
-- =====================================================
CREATE TABLE Attendance (
    AttendanceID      INT AUTO_INCREMENT PRIMARY KEY,
    GuardID           INT NOT NULL,
    AttendanceDate    DATE NOT NULL,
    IsPresent         TINYINT(1) DEFAULT 1,
    UniformFine       DECIMAL(10,2) DEFAULT 0.00,
    WeaponFine        DECIMAL(10,2) DEFAULT 0.00,
    LateFine          DECIMAL(10,2) DEFAULT 0.00,
    ConductFine       DECIMAL(10,2) DEFAULT 0.00,
    TotalFine         DECIMAL(10,2) DEFAULT 0.00,
    IsLocked          TINYINT(1) DEFAULT 0,
    Remarks           TEXT,
    CreatedAt         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (GuardID) REFERENCES Guards(GuardID)
);

-- =====================================================
-- TABLE 3: Payroll
-- =====================================================
CREATE TABLE Payroll (
    PayrollID     INT AUTO_INCREMENT PRIMARY KEY,
    GuardID       INT NOT NULL,
    PayMonth      VARCHAR(7) NOT NULL,
    BaseSalary    DECIMAL(10,2) DEFAULT 45000.00,
    TotalFines    DECIMAL(10,2) DEFAULT 0.00,
    TotalAbsents  INT DEFAULT 0,
    AbsentDeduction DECIMAL(10,2) DEFAULT 0.00,
    NetSalary     DECIMAL(10,2) DEFAULT 0.00,
    IsPaid        TINYINT(1) DEFAULT 0,
    GeneratedAt   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (GuardID) REFERENCES Guards(GuardID)
);

-- =====================================================
-- TABLE 4: Inventory
-- =====================================================
CREATE TABLE Inventory (
    ItemID        INT AUTO_INCREMENT PRIMARY KEY,
    ItemName      VARCHAR(150) NOT NULL,
    Category      ENUM('Weapons & Gear','Office & Logistics','Bulk Reserves') NOT NULL,
    Quantity      INT DEFAULT 0,
    MinThreshold  INT DEFAULT 5,
    AssignedTo    VARCHAR(100) DEFAULT 'Warehouse',
    IsArchived    TINYINT(1) DEFAULT 0,
    CreatedAt     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- SAMPLE DATA - Guards
-- =====================================================
INSERT INTO Guards (FullName, CNIC, Phone, EmergencyPhone, BloodGroup, Address, JoiningDate, CurrentShift, Status) VALUES
('Ali Hassan',       '35201-1234567-1', '0300-1111111', '0301-1111112', 'B+',  'Rawalpindi, Punjab',   '2023-01-15', 'Morning',   'Active'),
('Muhammad Bilal',   '35202-2345678-2', '0311-2222222', '0312-2222223', 'O+',  'Islamabad, Pakistan',  '2023-03-20', 'Evening',   'Active'),
('Usman Tariq',      '35203-3456789-3', '0321-3333333', '0322-3333334', 'A+',  'Lahore, Punjab',       '2023-06-10', 'Night',     'Active'),
('Kamran Malik',     '35204-4567890-4', '0333-4444444', '0334-4444445', 'AB+', 'Rawalpindi, Punjab',   '2023-08-01', 'Morning',   'Active'),
('Zubair Ahmed',     '35205-5678901-5', '0345-5555555', '0346-5555556', 'B-',  'Peshawar, KPK',        '2024-01-05', 'Off Duty',  'Active'),
('Tariq Mehmood',    '35206-6789012-6', '0300-6666666', '0301-6666667', 'O-',  'Faisalabad, Punjab',   '2024-02-14', 'Evening',   'Active');

-- =====================================================
-- SAMPLE DATA - Attendance (Today)
-- =====================================================
INSERT INTO Attendance (GuardID, AttendanceDate, IsPresent, UniformFine, WeaponFine, LateFine, ConductFine, TotalFine, IsLocked) VALUES
(1, CURDATE(), 1, 0.00,    0.00,    0.00,    0.00,    0.00,    0),
(2, CURDATE(), 1, 500.00,  0.00,    0.00,    0.00,    500.00,  0),
(3, CURDATE(), 1, 0.00,    1000.00, 0.00,    0.00,    1000.00, 0),
(4, CURDATE(), 0, 0.00,    0.00,    0.00,    0.00,    0.00,    0),
(5, CURDATE(), 1, 0.00,    0.00,    300.00,  0.00,    300.00,  0),
(6, CURDATE(), 1, 0.00,    0.00,    0.00,    500.00,  500.00,  0);

-- =====================================================
-- SAMPLE DATA - Inventory
-- =====================================================
INSERT INTO Inventory (ItemName, Category, Quantity, MinThreshold, AssignedTo) VALUES
('Pistol 9mm',          'Weapons & Gear',    12,  5,  'Armory'),
('Guard Uniform Set',   'Weapons & Gear',    30,  10, 'Warehouse'),
('Baton / Lathi',       'Weapons & Gear',    20,  8,  'Armory'),
('Walkie Talkie',       'Weapons & Gear',    8,   5,  'Control Room'),
('Bulletproof Vest',    'Weapons & Gear',    3,   5,  'Armory'),
('Office Laptop',       'Office & Logistics',5,   2,  'Head Office'),
('CCTV Camera',         'Office & Logistics',15,  4,  'Warehouse'),
('Attendance Register', 'Office & Logistics',10,  3,  'Head Office'),
('Printer Paper Ream',  'Bulk Reserves',     2,   10, 'Warehouse'),
('First Aid Kit',       'Bulk Reserves',     6,   5,  'Medical Room'),
('Torch / Flashlight',  'Bulk Reserves',     25,  10, 'Warehouse');

-- =====================================================
-- DONE! Your database is ready.
-- =====================================================
