# 🛡️ FAST SecureForce Management System

**FAST SecureForce** is a robust, end-to-end management platform designed for security firms. It provides a comprehensive suite of tools to manage personnel, track attendance with integrated fine management, automate payroll based on performance, and monitor inventory levels with automated stock alerts.

Built with a focus on operational efficiency and a modern **Cyber-Noir aesthetic**, it ensures that security firms can manage their workforce and assets with precision.

---

## 🚀 Key Features

### 👮 Personnel Management
- **Centralized Roster**: Add, view, and archive security personnel.
- **Detailed Profiles**: Store CNIC, Blood Group, Emergency Contacts, and more.
- **Dynamic Shifts**: Quickly reassign guards between Morning, Evening, and Night shifts.

### 📋 Operations & Attendance
- **Smart Attendance Grid**: Daily register with integrated fine toggles for Uniform, Weapon, Late, and Conduct violations.
- **Lockable Records**: Prevent modifications after the shift data is finalized.

### 💰 Automated Payroll
- **One-Click Generation**: Generate monthly salary slips for all active guards.
- **Smart Deductions**: Auto-calculates deductions for absences and accumulated fines.
- **Payout Management**: Track paid/unpaid status for each personnel.

### 📦 Inventory Hub
- **Asset Tracking**: Categorized tracking for Weapons, Office Gear, and Logistics.
- **Critical Alerts**: Automated warnings when stock levels fall below thresholds.

### 🤝 Recruitment Portal
- **Applicant Tracking**: Manage the hiring pipeline from submission to shortlisting.
- **Seamless Hiring**: Convert successful applicants directly into active guard records.

### 🔐 Security
- **Admin Authentication**: Secure login system with hashed credentials.
- **Role-Based Access**: Centralized dashboard for administrative control.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (PDO for secure database interactions)
- **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (Vanilla ES6)
- **Database**: MySQL / MariaDB
- **Design**: Cyber-Noir Aesthetic with 'Rajdhani', 'Inter', and 'Exo 2' typography.

---

## 📁 Project Structure

```text
security_system/
├── index.php               # Entry point (Redirects to Login)
├── login.php               # Admin Authentication
├── dashboard.php           # Main Command Center & Roster
├── recruitment.php         # Applicant Tracking & Hiring
├── attendance_grid.php     # Daily Attendance & Fine Toggles
├── payroll_hub.php         # Monthly Salary Management
├── inventory_hub.php       # Asset & Stock Management
├── database.sql            # Main Database Schema
├── add_login_table.sql     # Admin Authentication Schema
├── api/
│   ├── api_router.php      # Central API for Core Modules
│   └── recruitment_api.php # API for Recruitment Flow
├── includes/
│   ├── db_connect.php      # PDO Database Connection
│   └── nav.php             # Shared Sidebar Navigation
├── css/                    # Stylesheets (Vanilla CSS)
└── js/                     # Frontend Logic (ES6)
```

---

## ⚙️ Installation & Setup

1.  **Clone the Repository**:
    Place the project folder in your local server directory (e.g., `C:/xampp/htdocs/`).

2.  **Setup Database**:
    -   Open **phpMyAdmin**.
    -   Create a database named `security_firm`.
    -   Import `database.sql`.
    -   Import `add_login_table.sql`.
    -   *Note: Ensure the `Recruitment` table is created (see schema below if missing).*

3.  **Configure Connection**:
    -   Open `includes/db_connect.php`.
    -   Update `DB_USER` and `DB_PASS` to match your MySQL credentials.

4.  **Default Credentials**:
    -   **Username**: `admin`
    -   **Password**: `admin123`

5.  **Launch**:
    -   Start Apache and MySQL in XAMPP.
    -   Navigate to `http://localhost/security_system` in your browser.

---

## 📝 Database Schema (Recruitment)
If your `database.sql` does not include the recruitment table, run the following:

```sql
CREATE TABLE Recruitment (
    ApplicationID   INT AUTO_INCREMENT PRIMARY KEY,
    FullName        VARCHAR(100) NOT NULL,
    FatherName      VARCHAR(100) NOT NULL,
    CNIC            VARCHAR(15) NOT NULL UNIQUE,
    Phone           VARCHAR(20) NOT NULL,
    Email           VARCHAR(100),
    DateOfBirth     DATE NOT NULL,
    Address         TEXT NOT NULL,
    Education       VARCHAR(50) NOT NULL,
    ExperienceYears INT DEFAULT 0,
    AppliedShift    ENUM('Any','Morning','Evening','Night') DEFAULT 'Any',
    Status          ENUM('Pending','Shortlisted','Hired','Rejected') DEFAULT 'Pending',
    Notes           TEXT,
    AppliedAt       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## ⚖️ License
This project is developed as an academic management system. All rights reserved.

