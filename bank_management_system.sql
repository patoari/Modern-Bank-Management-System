-- ============================================================================
-- Modern Bank Management System - Comprehensive Database Schema
-- Database: MySQL 8.0+
-- Version: 1.0.0
-- Date: June 2026
-- Description: Complete database schema for enterprise banking platform
-- ============================================================================

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Create Database
DROP DATABASE IF EXISTS bank_management_system;
CREATE DATABASE bank_management_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bank_management_system;

-- ============================================================================
-- SECTION 1: USER MANAGEMENT & AUTHENTICATION
-- ============================================================================

-- Users Table (Staff & Customers)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'staff', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'suspended', 'locked', 'pending_verification') DEFAULT 'pending_verification',
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    force_password_change BOOLEAN DEFAULT FALSE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_type ENUM('sms', 'email', 'totp', 'hardware_token') NULL,
    two_factor_secret VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_user_type (user_type),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Roles Table
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    level INT NOT NULL COMMENT 'Hierarchy level: 1=Super Admin, 10=Customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Permissions Table
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_module (module)
) ENGINE=InnoDB;

-- User Roles (Many-to-Many)
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB;

-- Role Permissions (Many-to-Many)
CREATE TABLE role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB;

-- Sessions Table
CREATE TABLE sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    refresh_token VARCHAR(255) UNIQUE,
    device_type VARCHAR(50),
    device_fingerprint VARCHAR(255),
    browser VARCHAR(100),
    os VARCHAR(100),
    ip_address VARCHAR(45),
    location VARCHAR(255),
    is_trusted_device BOOLEAN DEFAULT FALSE,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- Password Reset Tokens
CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- OTP Tokens
CREATE TABLE otp_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    identifier VARCHAR(255) NOT NULL COMMENT 'Email or Phone',
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('login', 'transaction', 'registration', 'password_reset', 'verification') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    attempts INT DEFAULT 0,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_identifier (identifier),
    INDEX idx_otp_code (otp_code),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 2: CUSTOMER MANAGEMENT
-- ============================================================================

-- Customers Table
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    customer_id VARCHAR(50) UNIQUE NOT NULL COMMENT 'CIF Number',
    customer_type ENUM('individual', 'business', 'corporate') NOT NULL,
    segment ENUM('retail', 'sme', 'corporate', 'hni', 'premier') DEFAULT 'retail',
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    nationality VARCHAR(100),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    occupation VARCHAR(255),
    employer_name VARCHAR(255),
    annual_income DECIMAL(15, 2),
    income_source VARCHAR(255),
    risk_rating ENUM('low', 'medium', 'high', 'very_high') DEFAULT 'low',
    kyc_status ENUM('pending', 'under_review', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    kyc_verified_at TIMESTAMP NULL,
    kyc_expiry_date DATE,
    is_pep BOOLEAN DEFAULT FALSE COMMENT 'Politically Exposed Person',
    aml_status ENUM('clear', 'flagged', 'blocked') DEFAULT 'clear',
    relationship_manager_id BIGINT UNSIGNED,
    customer_since DATE,
    customer_lifetime_value DECIMAL(15, 2) DEFAULT 0,
    nps_score INT COMMENT 'Net Promoter Score',
    preferred_language VARCHAR(10) DEFAULT 'en',
    communication_preferences JSON COMMENT 'SMS, Email, Push preferences',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (relationship_manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_kyc_status (kyc_status),
    INDEX idx_segment (segment),
    INDEX idx_risk_rating (risk_rating)
) ENGINE=InnoDB;


-- Business Customers Extension
CREATE TABLE business_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED UNIQUE NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    business_type VARCHAR(100),
    registration_number VARCHAR(100) UNIQUE,
    tax_id VARCHAR(100),
    incorporation_date DATE,
    business_email VARCHAR(255),
    business_phone VARCHAR(20),
    website VARCHAR(255),
    annual_turnover DECIMAL(18, 2),
    employee_count INT,
    industry VARCHAR(100),
    authorized_signatory_name VARCHAR(255),
    authorized_signatory_designation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Customer Addresses
CREATE TABLE customer_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    address_type ENUM('primary', 'correspondence', 'business', 'billing') NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    landmark VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_primary BOOLEAN DEFAULT FALSE,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_address_type (address_type)
) ENGINE=InnoDB;

-- KYC Documents
CREATE TABLE kyc_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('national_id', 'passport', 'drivers_license', 'utility_bill', 'bank_statement', 'business_registration', 'photo', 'signature', 'other') NOT NULL,
    document_number VARCHAR(100),
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by BIGINT UNSIGNED,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by BIGINT UNSIGNED,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT,
    expiry_date DATE,
    ocr_data JSON COMMENT 'Extracted data from OCR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_verification_status (verification_status)
) ENGINE=InnoDB;

-- Customer Relationships (Family/Group Linking)
CREATE TABLE customer_relationships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    related_customer_id BIGINT UNSIGNED NOT NULL,
    relationship_type ENUM('spouse', 'parent', 'child', 'sibling', 'business_partner', 'guarantor', 'nominee', 'authorized_signatory') NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (related_customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_relationship_type (relationship_type)
) ENGINE=InnoDB;

-- Customer Complaints & Grievances
CREATE TABLE customer_complaints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    category ENUM('account', 'transaction', 'loan', 'card', 'service', 'fraud', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed', 'rejected') DEFAULT 'open',
    assigned_to BIGINT UNSIGNED,
    resolution TEXT,
    resolved_at TIMESTAMP NULL,
    satisfaction_rating INT COMMENT '1-5 stars',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 3: BRANCH & ATM MANAGEMENT
-- ============================================================================

-- Branches
CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_code VARCHAR(50) UNIQUE NOT NULL,
    branch_name VARCHAR(255) NOT NULL,
    branch_type ENUM('head_office', 'zonal', 'regional', 'branch', 'sub_branch', 'extension_counter') NOT NULL,
    parent_branch_id BIGINT UNSIGNED,
    ifsc_code VARCHAR(20) UNIQUE,
    swift_code VARCHAR(20),
    email VARCHAR(255),
    phone VARCHAR(20),
    fax VARCHAR(20),
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    manager_id BIGINT UNSIGNED,
    business_hours JSON COMMENT 'Opening hours by day',
    working_days JSON COMMENT 'Array of working days',
    status ENUM('active', 'inactive', 'temporarily_closed') DEFAULT 'active',
    opened_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_branch_code (branch_code),
    INDEX idx_ifsc_code (ifsc_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Staff Members
CREATE TABLE staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    branch_id BIGINT UNSIGNED,
    designation VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    reporting_to BIGINT UNSIGNED,
    date_of_joining DATE NOT NULL,
    date_of_leaving DATE,
    employment_type ENUM('permanent', 'contract', 'temporary', 'intern') DEFAULT 'permanent',
    salary DECIMAL(12, 2),
    pin_code VARCHAR(10) COMMENT 'Quick login PIN for teller',
    ip_whitelist TEXT COMMENT 'Comma-separated IPs for admin access',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (reporting_to) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_branch_id (branch_id)
) ENGINE=InnoDB;

-- ATMs
CREATE TABLE atms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atm_id VARCHAR(50) UNIQUE NOT NULL,
    atm_name VARCHAR(255) NOT NULL,
    branch_id BIGINT UNSIGNED,
    location_type ENUM('on_site', 'off_site') NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('active', 'inactive', 'under_maintenance', 'out_of_service') DEFAULT 'active',
    cash_available DECIMAL(15, 2) DEFAULT 0,
    cash_capacity DECIMAL(15, 2),
    last_cash_replenishment TIMESTAMP NULL,
    last_maintenance_date DATE,
    operational_24x7 BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    INDEX idx_atm_id (atm_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Teller Cash Management
CREATE TABLE teller_cash_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teller_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    business_date DATE NOT NULL,
    opening_balance DECIMAL(15, 2) NOT NULL DEFAULT 0,
    cash_received DECIMAL(15, 2) DEFAULT 0,
    cash_paid DECIMAL(15, 2) DEFAULT 0,
    closing_balance DECIMAL(15, 2),
    cash_difference DECIMAL(15, 2) COMMENT 'Discrepancy',
    status ENUM('open', 'closed', 'balanced', 'unbalanced') DEFAULT 'open',
    opened_at TIMESTAMP NOT NULL,
    closed_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teller_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_teller_id (teller_id),
    INDEX idx_business_date (business_date)
) ENGINE=InnoDB;

-- Branch Holidays
CREATE TABLE branch_holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('public', 'bank', 'regional', 'optional') NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    INDEX idx_holiday_date (holiday_date)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 4: ACCOUNT MANAGEMENT
-- ============================================================================

-- Account Types Configuration
CREATE TABLE account_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category ENUM('savings', 'current', 'salary', 'nre', 'nro', 'joint', 'minor', 'trust', 'escrow', 'overdraft') NOT NULL,
    description TEXT,
    min_opening_balance DECIMAL(12, 2) DEFAULT 0,
    min_balance DECIMAL(12, 2) DEFAULT 0,
    interest_rate DECIMAL(5, 2) DEFAULT 0,
    interest_calculation_method ENUM('daily_product', 'monthly_average', 'quarterly_average') DEFAULT 'daily_product',
    interest_posting_frequency ENUM('monthly', 'quarterly', 'annually') DEFAULT 'monthly',
    max_withdrawal_limit DECIMAL(15, 2),
    max_daily_transaction_limit DECIMAL(15, 2),
    monthly_service_charge DECIMAL(8, 2) DEFAULT 0,
    free_transactions_per_month INT DEFAULT 0,
    transaction_charge_after_limit DECIMAL(6, 2) DEFAULT 0,
    allow_cheque_book BOOLEAN DEFAULT TRUE,
    allow_atm_card BOOLEAN DEFAULT TRUE,
    allow_internet_banking BOOLEAN DEFAULT TRUE,
    allow_mobile_banking BOOLEAN DEFAULT TRUE,
    allow_overdraft BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- Accounts
CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(50) UNIQUE NOT NULL,
    account_type_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'USD',
    account_title VARCHAR(255) NOT NULL,
    account_status ENUM('active', 'frozen', 'dormant', 'closed', 'blocked') DEFAULT 'active',
    opening_date DATE NOT NULL,
    closing_date DATE,
    last_transaction_date DATE,
    available_balance DECIMAL(18, 2) DEFAULT 0,
    ledger_balance DECIMAL(18, 2) DEFAULT 0,
    hold_amount DECIMAL(18, 2) DEFAULT 0 COMMENT 'Amount on hold',
    overdraft_limit DECIMAL(15, 2) DEFAULT 0,
    min_balance DECIMAL(12, 2) DEFAULT 0,
    interest_rate DECIMAL(5, 2) DEFAULT 0,
    accrued_interest DECIMAL(12, 2) DEFAULT 0,
    last_interest_posted_date DATE,
    allow_debit BOOLEAN DEFAULT TRUE,
    allow_credit BOOLEAN DEFAULT TRUE,
    is_joint_account BOOLEAN DEFAULT FALSE,
    joint_account_type ENUM('either_or_survivor', 'former_or_survivor', 'jointly') NULL,
    is_minor_account BOOLEAN DEFAULT FALSE,
    guardian_customer_id BIGINT UNSIGNED,
    nominee_customer_id BIGINT UNSIGNED,
    iban VARCHAR(50),
    linked_account_id BIGINT UNSIGNED COMMENT 'For sweep-in/sweep-out',
    statement_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'annually') DEFAULT 'monthly',
    statement_delivery ENUM('email', 'post', 'both', 'none') DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (guardian_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (nominee_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (linked_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_account_number (account_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_account_status (account_status),
    INDEX idx_currency_code (currency_code)
) ENGINE=InnoDB;

-- Joint Account Holders
CREATE TABLE joint_account_holders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    relationship VARCHAR(100),
    authorization_level ENUM('full', 'view_only', 'limited') DEFAULT 'full',
    added_date DATE NOT NULL,
    removed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id)
) ENGINE=InnoDB;

-- Standing Instructions
CREATE TABLE standing_instructions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instruction_number VARCHAR(50) UNIQUE NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    instruction_type ENUM('auto_debit', 'auto_credit', 'sweep_in', 'sweep_out') NOT NULL,
    beneficiary_account_id BIGINT UNSIGNED,
    beneficiary_account_number VARCHAR(50),
    beneficiary_name VARCHAR(255),
    amount DECIMAL(15, 2),
    amount_type ENUM('fixed', 'percentage', 'balance_threshold') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'annually', 'event_based') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    next_execution_date DATE,
    last_execution_date DATE,
    execution_day INT COMMENT 'Day of month or week',
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    failure_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiary_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_account_id (account_id),
    INDEX idx_next_execution_date (next_execution_date)
) ENGINE=InnoDB;


-- ============================================================================
-- SECTION 5: TRANSACTION MANAGEMENT
-- ============================================================================

-- Transactions
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(100) UNIQUE NOT NULL,
    transaction_type ENUM('cash_deposit', 'cash_withdrawal', 'cheque_deposit', 'cheque_withdrawal', 'internal_transfer', 'neft', 'rtgs', 'imps', 'upi', 'swift', 'bill_payment', 'salary', 'merchant_payment', 'tax_payment', 'loan_emi', 'reversal', 'forex', 'other') NOT NULL,
    transaction_mode ENUM('branch', 'atm', 'internet_banking', 'mobile_banking', 'pos', 'api') NOT NULL,
    from_account_id BIGINT UNSIGNED,
    to_account_id BIGINT UNSIGNED,
    from_account_number VARCHAR(50),
    to_account_number VARCHAR(50),
    amount DECIMAL(18, 2) NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'USD',
    exchange_rate DECIMAL(12, 6) DEFAULT 1,
    converted_amount DECIMAL(18, 2),
    transaction_fee DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(18, 2) NOT NULL,
    transaction_status ENUM('pending', 'processing', 'completed', 'failed', 'reversed', 'cancelled') DEFAULT 'pending',
    initiated_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    description TEXT,
    remarks TEXT,
    reference_number VARCHAR(100) COMMENT 'External reference',
    value_date DATE NOT NULL,
    posting_date TIMESTAMP,
    scheduled_date DATE COMMENT 'For future-dated transactions',
    failure_reason TEXT,
    is_reversal BOOLEAN DEFAULT FALSE,
    original_transaction_id BIGINT UNSIGNED,
    requires_approval BOOLEAN DEFAULT FALSE,
    approval_status ENUM('pending', 'approved', 'rejected') NULL,
    approved_at TIMESTAMP NULL,
    metadata JSON COMMENT 'Additional transaction details',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (to_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (original_transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_transaction_ref (transaction_ref),
    INDEX idx_from_account (from_account_id),
    INDEX idx_to_account (to_account_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_status (transaction_status),
    INDEX idx_value_date (value_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Transaction Approvals (Maker-Checker)
CREATE TABLE transaction_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    level INT NOT NULL COMMENT 'Approval level/sequence',
    approver_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB;

-- Cheques
CREATE TABLE cheques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cheque_number VARCHAR(50) NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    cheque_book_id BIGINT UNSIGNED,
    amount DECIMAL(15, 2),
    payee_name VARCHAR(255),
    issue_date DATE,
    cheque_date DATE,
    clearance_date DATE,
    status ENUM('issued', 'presented', 'cleared', 'bounced', 'stopped', 'cancelled') DEFAULT 'issued',
    bounce_reason VARCHAR(255),
    bounce_charges DECIMAL(10, 2),
    micr_code VARCHAR(50),
    is_positive_pay BOOLEAN DEFAULT FALSE,
    transaction_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_cheque_number (cheque_number),
    INDEX idx_account_id (account_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Cheque Books
CREATE TABLE cheque_books (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cheque_book_number VARCHAR(50) UNIQUE NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    number_of_leaves INT NOT NULL,
    start_cheque_number VARCHAR(50) NOT NULL,
    end_cheque_number VARCHAR(50) NOT NULL,
    issue_date DATE NOT NULL,
    status ENUM('active', 'exhausted', 'blocked', 'lost') DEFAULT 'active',
    requested_by BIGINT UNSIGNED,
    issued_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_account_id (account_id)
) ENGINE=InnoDB;

-- Beneficiaries
CREATE TABLE beneficiaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    beneficiary_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    ifsc_code VARCHAR(20),
    bank_name VARCHAR(255),
    branch_name VARCHAR(255),
    beneficiary_type ENUM('internal', 'domestic', 'international') NOT NULL,
    swift_code VARCHAR(20),
    iban VARCHAR(50),
    nickname VARCHAR(100),
    is_favorite BOOLEAN DEFAULT FALSE,
    verification_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
    verified_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_account_number (account_number)
) ENGINE=InnoDB;

-- Bill Payments
CREATE TABLE bill_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED UNIQUE NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    biller_category ENUM('electricity', 'water', 'gas', 'internet', 'mobile', 'dth', 'insurance', 'loan', 'credit_card', 'tax', 'education', 'other') NOT NULL,
    biller_name VARCHAR(255) NOT NULL,
    biller_id VARCHAR(100),
    consumer_number VARCHAR(100) NOT NULL,
    bill_number VARCHAR(100),
    bill_date DATE,
    due_date DATE,
    bill_amount DECIMAL(15, 2) NOT NULL,
    late_fee DECIMAL(8, 2) DEFAULT 0,
    is_autopay BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_biller_category (biller_category)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 6: LOAN MANAGEMENT
-- ============================================================================

-- Loan Products
CREATE TABLE loan_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_type ENUM('home_loan', 'personal_loan', 'auto_loan', 'education_loan', 'business_loan', 'gold_loan', 'agriculture_loan', 'lap', 'loan_against_fd', 'overdraft', 'working_capital', 'microfinance', 'bnpl') NOT NULL,
    description TEXT,
    min_amount DECIMAL(15, 2) NOT NULL,
    max_amount DECIMAL(15, 2) NOT NULL,
    min_tenure_months INT NOT NULL,
    max_tenure_months INT NOT NULL,
    interest_rate_min DECIMAL(5, 2) NOT NULL,
    interest_rate_max DECIMAL(5, 2) NOT NULL,
    interest_rate_type ENUM('fixed', 'floating', 'hybrid') DEFAULT 'fixed',
    rate_benchmark VARCHAR(50) COMMENT 'MCLR, Base Rate, SOFR',
    processing_fee_percentage DECIMAL(5, 2) DEFAULT 0,
    processing_fee_flat DECIMAL(10, 2) DEFAULT 0,
    prepayment_allowed BOOLEAN DEFAULT TRUE,
    prepayment_penalty_percentage DECIMAL(5, 2) DEFAULT 0,
    moratorium_allowed BOOLEAN DEFAULT FALSE,
    max_moratorium_months INT DEFAULT 0,
    collateral_required BOOLEAN DEFAULT FALSE,
    guarantor_required BOOLEAN DEFAULT FALSE,
    min_cibil_score INT,
    min_age INT,
    max_age INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_type (product_type)
) ENGINE=InnoDB;

-- Loan Applications
CREATE TABLE loan_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    loan_product_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    applied_amount DECIMAL(15, 2) NOT NULL,
    applied_tenure_months INT NOT NULL,
    purpose TEXT NOT NULL,
    monthly_income DECIMAL(12, 2),
    existing_liabilities DECIMAL(12, 2),
    employment_type ENUM('salaried', 'self_employed', 'business', 'professional') NOT NULL,
    cibil_score INT,
    application_status ENUM('draft', 'submitted', 'document_verification', 'credit_assessment', 'field_investigation', 'committee_review', 'sanctioned', 'rejected', 'cancelled') DEFAULT 'draft',
    sanctioned_amount DECIMAL(15, 2),
    sanctioned_tenure_months INT,
    sanctioned_interest_rate DECIMAL(5, 2),
    rejection_reason TEXT,
    credit_officer_id BIGINT UNSIGNED,
    sanctioned_by BIGINT UNSIGNED,
    sanctioned_at TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (credit_officer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sanctioned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_application_number (application_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_application_status (application_status)
) ENGINE=InnoDB;

-- Loan Application Documents
CREATE TABLE loan_application_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id)
) ENGINE=InnoDB;

-- Loans
CREATE TABLE loans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_account_number VARCHAR(50) UNIQUE NOT NULL,
    application_id BIGINT UNSIGNED NOT NULL,
    loan_product_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    principal_amount DECIMAL(15, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    interest_rate_type ENUM('fixed', 'floating') NOT NULL,
    tenure_months INT NOT NULL,
    emi_amount DECIMAL(12, 2) NOT NULL,
    emi_day INT NOT NULL COMMENT 'Day of month for EMI',
    disbursement_date DATE NOT NULL,
    first_emi_date DATE NOT NULL,
    maturity_date DATE NOT NULL,
    moratorium_months INT DEFAULT 0,
    outstanding_principal DECIMAL(15, 2) NOT NULL,
    outstanding_interest DECIMAL(15, 2) DEFAULT 0,
    total_outstanding DECIMAL(15, 2) NOT NULL,
    total_paid DECIMAL(15, 2) DEFAULT 0,
    prepayment_amount DECIMAL(15, 2) DEFAULT 0,
    processing_fee DECIMAL(10, 2) DEFAULT 0,
    loan_status ENUM('active', 'disbursed', 'closed', 'npa', 'written_off') DEFAULT 'active',
    npa_classification ENUM('standard', 'substandard', 'doubtful_1', 'doubtful_2', 'doubtful_3', 'loss') DEFAULT 'standard',
    npa_date DATE,
    overdue_days INT DEFAULT 0,
    penalty_amount DECIMAL(10, 2) DEFAULT 0,
    disbursement_account_id BIGINT UNSIGNED NOT NULL,
    repayment_account_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES loan_applications(id),
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (disbursement_account_id) REFERENCES accounts(id),
    FOREIGN KEY (repayment_account_id) REFERENCES accounts(id),
    INDEX idx_loan_account_number (loan_account_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_loan_status (loan_status),
    INDEX idx_npa_classification (npa_classification)
) ENGINE=InnoDB;

-- Loan Repayment Schedule
CREATE TABLE loan_repayment_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(12, 2) NOT NULL,
    interest_amount DECIMAL(12, 2) NOT NULL,
    emi_amount DECIMAL(12, 2) NOT NULL,
    outstanding_balance DECIMAL(15, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'partial', 'overdue') DEFAULT 'pending',
    paid_amount DECIMAL(12, 2) DEFAULT 0,
    paid_date DATE,
    penalty_amount DECIMAL(8, 2) DEFAULT 0,
    transaction_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_loan_id (loan_id),
    INDEX idx_due_date (due_date),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB;

-- Loan Collaterals
CREATE TABLE loan_collaterals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    collateral_type ENUM('property', 'vehicle', 'gold', 'securities', 'fd', 'other') NOT NULL,
    description TEXT NOT NULL,
    valuation_amount DECIMAL(15, 2) NOT NULL,
    valuation_date DATE NOT NULL,
    valuer_name VARCHAR(255),
    document_path VARCHAR(500),
    status ENUM('pledged', 'released', 'foreclosed') DEFAULT 'pledged',
    pledged_date DATE NOT NULL,
    released_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_loan_id (loan_id)
) ENGINE=InnoDB;

-- Loan Guarantors
CREATE TABLE loan_guarantors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    guarantor_customer_id BIGINT UNSIGNED NOT NULL,
    guarantor_type ENUM('personal', 'corporate', 'bank_guarantee') DEFAULT 'personal',
    guarantee_amount DECIMAL(15, 2) NOT NULL,
    guarantee_date DATE NOT NULL,
    guarantee_validity_date DATE,
    status ENUM('active', 'released', 'invoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (guarantor_customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_loan_id (loan_id)
) ENGINE=InnoDB;


-- ============================================================================
-- SECTION 7: CARD MANAGEMENT
-- ============================================================================

-- Card Products
CREATE TABLE card_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    card_type ENUM('debit', 'credit', 'prepaid', 'virtual', 'corporate') NOT NULL,
    card_network ENUM('visa', 'mastercard', 'rupay', 'amex', 'other') NOT NULL,
    card_variant ENUM('classic', 'silver', 'gold', 'platinum', 'signature', 'infinite') DEFAULT 'classic',
    annual_fee DECIMAL(8, 2) DEFAULT 0,
    joining_fee DECIMAL(8, 2) DEFAULT 0,
    credit_limit_min DECIMAL(12, 2),
    credit_limit_max DECIMAL(12, 2),
    interest_rate DECIMAL(5, 2) COMMENT 'For credit cards',
    grace_period_days INT COMMENT 'Interest-free period for credit cards',
    reward_points_rate DECIMAL(5, 2) COMMENT 'Points per 100 currency',
    cashback_percentage DECIMAL(5, 2),
    foreign_transaction_fee_percentage DECIMAL(5, 2) DEFAULT 3.5,
    contactless_enabled BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_card_type (card_type)
) ENGINE=InnoDB;

-- Cards
CREATE TABLE cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_number VARCHAR(19) UNIQUE NOT NULL,
    card_product_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    cardholder_name VARCHAR(255) NOT NULL,
    card_type ENUM('debit', 'credit', 'prepaid', 'virtual', 'corporate') NOT NULL,
    card_status ENUM('active', 'blocked', 'expired', 'lost', 'stolen', 'closed') DEFAULT 'active',
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    cvv_hash VARCHAR(255) NOT NULL,
    pin_hash VARCHAR(255),
    credit_limit DECIMAL(12, 2) COMMENT 'For credit cards',
    available_limit DECIMAL(12, 2),
    outstanding_amount DECIMAL(12, 2) DEFAULT 0,
    billing_cycle_day INT COMMENT 'Day of month for statement generation',
    payment_due_day INT COMMENT 'Days after billing for payment',
    last_statement_date DATE,
    next_statement_date DATE,
    min_payment_percentage DECIMAL(5, 2) DEFAULT 5,
    reward_points DECIMAL(10, 2) DEFAULT 0,
    daily_atm_limit DECIMAL(12, 2),
    daily_pos_limit DECIMAL(12, 2),
    daily_online_limit DECIMAL(12, 2),
    international_usage_enabled BOOLEAN DEFAULT FALSE,
    online_transaction_enabled BOOLEAN DEFAULT TRUE,
    contactless_enabled BOOLEAN DEFAULT TRUE,
    replaced_by_card_id BIGINT UNSIGNED,
    activation_date DATE,
    block_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_product_id) REFERENCES card_products(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (replaced_by_card_id) REFERENCES cards(id) ON DELETE SET NULL,
    INDEX idx_card_number (card_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_account_id (account_id),
    INDEX idx_card_status (card_status)
) ENGINE=InnoDB;

-- Card Transactions
CREATE TABLE card_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(100) UNIQUE NOT NULL,
    card_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('purchase', 'withdrawal', 'refund', 'emi_conversion', 'fee', 'interest', 'reward_redemption') NOT NULL,
    merchant_name VARCHAR(255),
    merchant_category VARCHAR(100),
    merchant_country VARCHAR(100),
    amount DECIMAL(15, 2) NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'USD',
    billing_amount DECIMAL(15, 2),
    transaction_date TIMESTAMP NOT NULL,
    posting_date DATE,
    authorization_code VARCHAR(50),
    transaction_status ENUM('authorized', 'settled', 'declined', 'reversed') DEFAULT 'authorized',
    decline_reason VARCHAR(255),
    is_international BOOLEAN DEFAULT FALSE,
    is_contactless BOOLEAN DEFAULT FALSE,
    is_online BOOLEAN DEFAULT FALSE,
    reward_points_earned DECIMAL(8, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    INDEX idx_card_id (card_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_transaction_status (transaction_status)
) ENGINE=InnoDB;

-- Card EMI Conversions
CREATE TABLE card_emi_conversions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id BIGINT UNSIGNED NOT NULL,
    card_transaction_id BIGINT UNSIGNED,
    principal_amount DECIMAL(12, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    tenure_months INT NOT NULL,
    emi_amount DECIMAL(10, 2) NOT NULL,
    processing_fee DECIMAL(8, 2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    remaining_installments INT NOT NULL,
    status ENUM('active', 'closed', 'foreclosed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (card_transaction_id) REFERENCES card_transactions(id) ON DELETE SET NULL,
    INDEX idx_card_id (card_id)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 8: FIXED DEPOSITS & RECURRING DEPOSITS
-- ============================================================================

-- FD/RD Products
CREATE TABLE deposit_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_type ENUM('fixed_deposit', 'recurring_deposit', 'tax_saving_fd') NOT NULL,
    fd_type ENUM('cumulative', 'non_cumulative', 'regular', 'senior_citizen') COMMENT 'For FD only',
    min_amount DECIMAL(12, 2) NOT NULL,
    max_amount DECIMAL(15, 2),
    min_tenure_days INT NOT NULL,
    max_tenure_days INT NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    senior_citizen_additional_rate DECIMAL(5, 2) DEFAULT 0.5,
    interest_payout_frequency ENUM('monthly', 'quarterly', 'annually', 'on_maturity') DEFAULT 'on_maturity',
    premature_withdrawal_allowed BOOLEAN DEFAULT TRUE,
    premature_penalty_percentage DECIMAL(5, 2) DEFAULT 1,
    loan_allowed BOOLEAN DEFAULT TRUE,
    loan_percentage DECIMAL(5, 2) DEFAULT 90 COMMENT 'Max loan as % of FD value',
    auto_renewal_allowed BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_type (product_type)
) ENGINE=InnoDB;

-- Fixed Deposits
CREATE TABLE fixed_deposits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fd_account_number VARCHAR(50) UNIQUE NOT NULL,
    deposit_product_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    linked_account_id BIGINT UNSIGNED NOT NULL COMMENT 'Savings account for debit/credit',
    branch_id BIGINT UNSIGNED NOT NULL,
    principal_amount DECIMAL(15, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    tenure_days INT NOT NULL,
    maturity_amount DECIMAL(15, 2) NOT NULL,
    interest_amount DECIMAL(15, 2) NOT NULL,
    interest_payout_frequency ENUM('monthly', 'quarterly', 'annually', 'on_maturity') NOT NULL,
    deposit_date DATE NOT NULL,
    maturity_date DATE NOT NULL,
    last_interest_paid_date DATE,
    next_interest_payment_date DATE,
    total_interest_paid DECIMAL(15, 2) DEFAULT 0,
    tds_deducted DECIMAL(10, 2) DEFAULT 0,
    fd_status ENUM('active', 'matured', 'prematurely_closed', 'auto_renewed') DEFAULT 'active',
    is_auto_renewal BOOLEAN DEFAULT FALSE,
    renewed_from_fd_id BIGINT UNSIGNED,
    auto_renewed_to_fd_id BIGINT UNSIGNED,
    premature_closure_date DATE,
    premature_closure_amount DECIMAL(15, 2),
    penalty_amount DECIMAL(10, 2),
    nominee_customer_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (deposit_product_id) REFERENCES deposit_products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (linked_account_id) REFERENCES accounts(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (renewed_from_fd_id) REFERENCES fixed_deposits(id) ON DELETE SET NULL,
    FOREIGN KEY (auto_renewed_to_fd_id) REFERENCES fixed_deposits(id) ON DELETE SET NULL,
    FOREIGN KEY (nominee_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_fd_account_number (fd_account_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_fd_status (fd_status),
    INDEX idx_maturity_date (maturity_date)
) ENGINE=InnoDB;

-- Recurring Deposits
CREATE TABLE recurring_deposits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rd_account_number VARCHAR(50) UNIQUE NOT NULL,
    deposit_product_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    linked_account_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    monthly_installment DECIMAL(12, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    tenure_months INT NOT NULL,
    maturity_amount DECIMAL(15, 2) NOT NULL,
    deposit_date DATE NOT NULL,
    maturity_date DATE NOT NULL,
    installment_day INT NOT NULL COMMENT 'Day of month for auto-debit',
    installments_paid INT DEFAULT 0,
    total_deposited DECIMAL(15, 2) DEFAULT 0,
    missed_installments INT DEFAULT 0,
    penalty_amount DECIMAL(8, 2) DEFAULT 0,
    rd_status ENUM('active', 'matured', 'prematurely_closed') DEFAULT 'active',
    premature_closure_date DATE,
    premature_closure_amount DECIMAL(15, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (deposit_product_id) REFERENCES deposit_products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (linked_account_id) REFERENCES accounts(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    INDEX idx_rd_account_number (rd_account_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_rd_status (rd_status)
) ENGINE=InnoDB;

-- RD Installment Schedule
CREATE TABLE rd_installment_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rd_id BIGINT UNSIGNED NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    installment_amount DECIMAL(12, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'missed') DEFAULT 'pending',
    paid_date DATE,
    penalty_amount DECIMAL(6, 2) DEFAULT 0,
    transaction_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rd_id) REFERENCES recurring_deposits(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_rd_id (rd_id),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 9: INVESTMENTS & WEALTH MANAGEMENT
-- ============================================================================

-- Investment Products
CREATE TABLE investment_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_type ENUM('mutual_fund', 'bond', 'ipo', 'gold', 'nps', 'sgb', 'stock') NOT NULL,
    category VARCHAR(100) COMMENT 'Equity, Debt, Hybrid, etc.',
    fund_house VARCHAR(255),
    amc_code VARCHAR(50),
    isin VARCHAR(50),
    risk_level ENUM('low', 'moderate', 'high', 'very_high') NOT NULL,
    min_investment DECIMAL(12, 2),
    min_sip_amount DECIMAL(10, 2),
    exit_load_percentage DECIMAL(5, 2),
    expense_ratio DECIMAL(5, 2),
    lock_in_period_days INT DEFAULT 0,
    current_nav DECIMAL(10, 4),
    nav_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_type (product_type),
    INDEX idx_isin (isin)
) ENGINE=InnoDB;

-- Customer Investments
CREATE TABLE customer_investments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    investment_product_id BIGINT UNSIGNED NOT NULL,
    investment_type ENUM('lumpsum', 'sip', 'swp', 'stp') NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    investment_date DATE NOT NULL,
    units DECIMAL(15, 4) NOT NULL DEFAULT 0,
    average_nav DECIMAL(10, 4),
    invested_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    current_value DECIMAL(15, 2),
    unrealized_gain_loss DECIMAL(15, 2),
    realized_gain_loss DECIMAL(15, 2) DEFAULT 0,
    status ENUM('active', 'redeemed', 'partially_redeemed') DEFAULT 'active',
    nominee_customer_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (investment_product_id) REFERENCES investment_products(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (nominee_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_folio_number (folio_number)
) ENGINE=InnoDB;

-- Investment Transactions
CREATE TABLE investment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(100) UNIQUE NOT NULL,
    investment_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('purchase', 'redemption', 'switch', 'dividend', 'bonus') NOT NULL,
    transaction_date DATE NOT NULL,
    units DECIMAL(15, 4) NOT NULL,
    nav DECIMAL(10, 4) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    charges DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    net_amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    payment_transaction_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_id) REFERENCES customer_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_investment_id (investment_id),
    INDEX idx_transaction_date (transaction_date)
) ENGINE=InnoDB;

-- SIP Mandates
CREATE TABLE sip_mandates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mandate_number VARCHAR(50) UNIQUE NOT NULL,
    investment_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    sip_amount DECIMAL(10, 2) NOT NULL,
    frequency ENUM('weekly', 'monthly', 'quarterly') DEFAULT 'monthly',
    sip_day INT NOT NULL COMMENT 'Day of month/week',
    start_date DATE NOT NULL,
    end_date DATE,
    next_sip_date DATE,
    installments_completed INT DEFAULT 0,
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_id) REFERENCES customer_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    INDEX idx_investment_id (investment_id),
    INDEX idx_next_sip_date (next_sip_date)
) ENGINE=InnoDB;


-- ============================================================================
-- SECTION 10: INSURANCE MANAGEMENT
-- ============================================================================

-- Insurance Products
CREATE TABLE insurance_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    insurance_type ENUM('term_life', 'health', 'vehicle', 'home', 'travel', 'credit_life') NOT NULL,
    insurer_name VARCHAR(255) NOT NULL,
    insurer_code VARCHAR(50),
    description TEXT,
    min_sum_assured DECIMAL(15, 2),
    max_sum_assured DECIMAL(15, 2),
    min_age INT,
    max_age INT,
    policy_term_min_years INT,
    policy_term_max_years INT,
    premium_payment_frequency ENUM('monthly', 'quarterly', 'half_yearly', 'annually') DEFAULT 'annually',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_insurance_type (insurance_type)
) ENGINE=InnoDB;

-- Customer Insurance Policies
CREATE TABLE customer_insurance_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    insurance_product_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    sum_assured DECIMAL(15, 2) NOT NULL,
    premium_amount DECIMAL(10, 2) NOT NULL,
    premium_frequency ENUM('monthly', 'quarterly', 'half_yearly', 'annually') NOT NULL,
    policy_start_date DATE NOT NULL,
    policy_end_date DATE NOT NULL,
    next_premium_due_date DATE,
    policy_status ENUM('active', 'lapsed', 'matured', 'surrendered', 'claimed') DEFAULT 'active',
    nominee_customer_id BIGINT UNSIGNED,
    linked_loan_id BIGINT UNSIGNED COMMENT 'For credit life insurance',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (insurance_product_id) REFERENCES insurance_products(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (nominee_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (linked_loan_id) REFERENCES loans(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_policy_number (policy_number)
) ENGINE=InnoDB;

-- Insurance Claims
CREATE TABLE insurance_claims (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_number VARCHAR(50) UNIQUE NOT NULL,
    policy_id BIGINT UNSIGNED NOT NULL,
    claim_type ENUM('death', 'hospitalization', 'accident', 'theft', 'damage', 'maturity', 'other') NOT NULL,
    claim_amount DECIMAL(15, 2) NOT NULL,
    claim_date DATE NOT NULL,
    incident_date DATE NOT NULL,
    claim_status ENUM('submitted', 'under_review', 'approved', 'rejected', 'settled') DEFAULT 'submitted',
    approved_amount DECIMAL(15, 2),
    rejection_reason TEXT,
    settlement_date DATE,
    documents_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (policy_id) REFERENCES customer_insurance_policies(id) ON DELETE CASCADE,
    INDEX idx_policy_id (policy_id),
    INDEX idx_claim_status (claim_status)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 11: NOTIFICATIONS & ALERTS
-- ============================================================================

-- Notification Templates
CREATE TABLE notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    notification_type ENUM('transaction', 'login', 'security', 'loan', 'fd_maturity', 'payment_due', 'promotional', 'system') NOT NULL,
    channel ENUM('sms', 'email', 'push', 'in_app', 'whatsapp') NOT NULL,
    subject VARCHAR(255),
    template_body TEXT NOT NULL,
    variables JSON COMMENT 'Template variables',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_code (template_code)
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type ENUM('transaction', 'login', 'security', 'loan', 'fd_maturity', 'payment_due', 'promotional', 'system') NOT NULL,
    channel ENUM('sms', 'email', 'push', 'in_app', 'whatsapp') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    subject VARCHAR(255),
    message TEXT NOT NULL,
    metadata JSON,
    status ENUM('pending', 'sent', 'failed', 'read') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    failure_reason TEXT,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Notification Preferences
CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    transaction_alerts_sms BOOLEAN DEFAULT TRUE,
    transaction_alerts_email BOOLEAN DEFAULT TRUE,
    transaction_alerts_push BOOLEAN DEFAULT TRUE,
    login_alerts BOOLEAN DEFAULT TRUE,
    low_balance_alerts BOOLEAN DEFAULT TRUE,
    low_balance_threshold DECIMAL(12, 2) DEFAULT 1000,
    large_transaction_alerts BOOLEAN DEFAULT TRUE,
    large_transaction_threshold DECIMAL(15, 2) DEFAULT 10000,
    payment_due_reminders BOOLEAN DEFAULT TRUE,
    fd_maturity_alerts BOOLEAN DEFAULT TRUE,
    promotional_notifications BOOLEAN DEFAULT FALSE,
    whatsapp_notifications BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 12: TREASURY & FOREX MANAGEMENT
-- ============================================================================

-- Currency Rates
CREATE TABLE currency_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    exchange_rate DECIMAL(12, 6) NOT NULL,
    buy_rate DECIMAL(12, 6) NOT NULL,
    sell_rate DECIMAL(12, 6) NOT NULL,
    rate_date DATE NOT NULL,
    rate_time TIME NOT NULL,
    source VARCHAR(100) COMMENT 'Rate provider',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_to_currency (from_currency, to_currency),
    INDEX idx_rate_date (rate_date)
) ENGINE=InnoDB;

-- Forex Transactions
CREATE TABLE forex_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(100) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    from_amount DECIMAL(18, 2) NOT NULL,
    to_amount DECIMAL(18, 2) NOT NULL,
    exchange_rate DECIMAL(12, 6) NOT NULL,
    service_charge DECIMAL(10, 2) DEFAULT 0,
    transaction_type ENUM('buy', 'sell', 'remittance') NOT NULL,
    purpose VARCHAR(255),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    transaction_date DATE NOT NULL,
    value_date DATE NOT NULL,
    branch_id BIGINT UNSIGNED,
    processed_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_transaction_date (transaction_date)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 13: AUDIT & COMPLIANCE
-- ============================================================================

-- Audit Logs
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL COMMENT 'Table/Model name',
    entity_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_method VARCHAR(10),
    request_url VARCHAR(500),
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB;

-- AML Alerts
CREATE TABLE aml_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_number VARCHAR(50) UNIQUE NOT NULL,
    alert_type ENUM('velocity_check', 'pattern_detection', 'sanctions_screening', 'pep_screening', 'threshold_breach', 'country_risk', 'other') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    customer_id BIGINT UNSIGNED,
    transaction_id BIGINT UNSIGNED,
    account_id BIGINT UNSIGNED,
    description TEXT NOT NULL,
    alert_data JSON,
    alert_status ENUM('open', 'under_investigation', 'false_positive', 'escalated', 'closed') DEFAULT 'open',
    assigned_to BIGINT UNSIGNED,
    resolution TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_alert_number (alert_number),
    INDEX idx_alert_status (alert_status),
    INDEX idx_severity (severity)
) ENGINE=InnoDB;

-- Suspicious Transaction Reports (STR)
CREATE TABLE suspicious_transaction_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    str_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED,
    report_type ENUM('str', 'ctr') NOT NULL COMMENT 'STR or Cash Transaction Report',
    reporting_entity VARCHAR(255),
    suspicious_activity_description TEXT NOT NULL,
    amount DECIMAL(18, 2),
    transaction_date DATE,
    report_status ENUM('draft', 'under_review', 'approved', 'submitted', 'rejected') DEFAULT 'draft',
    prepared_by BIGINT UNSIGNED NOT NULL,
    reviewed_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    submitted_to VARCHAR(255) COMMENT 'FIU/Regulatory body',
    submission_date DATE,
    submission_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_str_number (str_number),
    INDEX idx_report_status (report_status)
) ENGINE=InnoDB;

-- Sanctions Screening List
CREATE TABLE sanctions_screening_list (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    list_name VARCHAR(100) NOT NULL COMMENT 'OFAC, UN, EU, etc.',
    entity_type ENUM('individual', 'organization') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    aliases JSON COMMENT 'Array of alternative names',
    date_of_birth DATE,
    nationality VARCHAR(100),
    passport_number VARCHAR(100),
    identification_number VARCHAR(100),
    address TEXT,
    list_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_full_name (full_name),
    INDEX idx_list_name (list_name)
) ENGINE=InnoDB;

-- Compliance Documents
CREATE TABLE compliance_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_type ENUM('policy', 'procedure', 'regulatory_filing', 'audit_report', 'certificate', 'other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_number VARCHAR(100) UNIQUE,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    version VARCHAR(20),
    effective_date DATE,
    expiry_date DATE,
    uploaded_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 14: REPORTS & ANALYTICS
-- ============================================================================

-- Saved Reports
CREATE TABLE saved_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    report_category ENUM('customer', 'transaction', 'loan', 'deposit', 'regulatory', 'management', 'audit') NOT NULL,
    description TEXT,
    parameters JSON COMMENT 'Filters and parameters',
    schedule ENUM('daily', 'weekly', 'monthly', 'quarterly', 'on_demand') DEFAULT 'on_demand',
    schedule_time TIME,
    schedule_day INT,
    recipients JSON COMMENT 'Email recipients',
    output_format ENUM('pdf', 'excel', 'csv', 'html') DEFAULT 'pdf',
    is_active BOOLEAN DEFAULT TRUE,
    last_generated_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_report_category (report_category)
) ENGINE=InnoDB;

-- Report Executions
CREATE TABLE report_executions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saved_report_id BIGINT UNSIGNED,
    report_name VARCHAR(255) NOT NULL,
    executed_by BIGINT UNSIGNED NOT NULL,
    execution_start TIMESTAMP NOT NULL,
    execution_end TIMESTAMP,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    output_file_path VARCHAR(500),
    error_message TEXT,
    record_count INT,
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (saved_report_id) REFERENCES saved_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_saved_report_id (saved_report_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================================
-- SECTION 15: SYSTEM CONFIGURATION
-- ============================================================================

-- System Settings
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(100),
    description TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- API Keys (Third-party integrations)
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL,
    api_secret_hash VARCHAR(255),
    environment ENUM('sandbox', 'production') DEFAULT 'sandbox',
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_name (service_name)
) ENGINE=InnoDB;

-- Email Queue
CREATE TABLE email_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    attachments JSON,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- SMS Queue
CREATE TABLE sms_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    provider VARCHAR(50),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Jobs Queue (Background tasks)
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(100) NOT NULL DEFAULT 'default',
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_queue (queue)
) ENGINE=InnoDB;

-- Failed Jobs
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================================
-- SECTION 16: VIEWS FOR REPORTING & ANALYTICS
-- ============================================================================

-- Customer Summary View
CREATE OR REPLACE VIEW v_customer_summary AS
SELECT 
    c.id,
    c.customer_id,
    c.first_name,
    c.last_name,
    CONCAT(c.first_name, ' ', c.last_name) AS full_name,
    c.customer_type,
    c.segment,
    c.kyc_status,
    c.risk_rating,
    u.email,
    u.phone,
    u.status AS user_status,
    COUNT(DISTINCT a.id) AS total_accounts,
    SUM(a.available_balance) AS total_balance,
    COUNT(DISTINCT l.id) AS total_loans,
    SUM(l.total_outstanding) AS total_loan_outstanding,
    COUNT(DISTINCT card.id) AS total_cards,
    c.customer_since,
    c.customer_lifetime_value
FROM customers c
INNER JOIN users u ON c.user_id = u.id
LEFT JOIN accounts a ON c.id = a.customer_id AND a.deleted_at IS NULL
LEFT JOIN loans l ON c.id = l.customer_id AND l.loan_status IN ('active', 'disbursed')
LEFT JOIN cards card ON c.id = card.customer_id AND card.card_status = 'active'
WHERE c.deleted_at IS NULL
GROUP BY
    c.id,
    c.customer_id,
    c.first_name,
    c.last_name,
    c.customer_type,
    c.segment,
    c.kyc_status,
    c.risk_rating,
    u.email,
    u.phone,
    u.status,
    c.customer_since,
    c.customer_lifetime_value;

-- Account Balance Summary View
CREATE OR REPLACE VIEW v_account_balance_summary AS
SELECT 
    a.id,
    a.account_number,
    a.account_status,
    at.name AS account_type_name,
    at.category AS account_category,
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    b.branch_name,
    a.available_balance,
    a.ledger_balance,
    a.hold_amount,
    a.currency_code,
    a.opening_date,
    a.last_transaction_date,
    DATEDIFF(CURDATE(), a.last_transaction_date) AS days_since_last_transaction
FROM accounts a
INNER JOIN account_types at ON a.account_type_id = at.id
INNER JOIN customers c ON a.customer_id = c.id
INNER JOIN branches b ON a.branch_id = b.id
WHERE a.deleted_at IS NULL;

-- Transaction Summary View
CREATE OR REPLACE VIEW v_transaction_summary AS
SELECT 
    t.id,
    t.transaction_ref,
    t.transaction_type,
    t.transaction_mode,
    t.from_account_number,
    t.to_account_number,
    t.amount,
    t.currency_code,
    t.transaction_status,
    t.value_date,
    t.created_at AS transaction_datetime,
    fa.customer_id AS from_customer_id,
    CONCAT(fc.first_name, ' ', fc.last_name) AS from_customer_name,
    ta.customer_id AS to_customer_id,
    CONCAT(tc.first_name, ' ', tc.last_name) AS to_customer_name,
    b.branch_name,
    CONCAT(u.email) AS initiated_by_email
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.id
LEFT JOIN customers fc ON fa.customer_id = fc.id
LEFT JOIN accounts ta ON t.to_account_id = ta.id
LEFT JOIN customers tc ON ta.customer_id = tc.id
LEFT JOIN branches b ON t.branch_id = b.id
LEFT JOIN users u ON t.initiated_by = u.id;

-- Loan Portfolio View
CREATE OR REPLACE VIEW v_loan_portfolio AS
SELECT 
    l.id,
    l.loan_account_number,
    lp.product_name AS loan_product_name,
    lp.product_type,
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    l.principal_amount,
    l.interest_rate,
    l.tenure_months,
    l.emi_amount,
    l.disbursement_date,
    l.maturity_date,
    l.outstanding_principal,
    l.outstanding_interest,
    l.total_outstanding,
    l.loan_status,
    l.npa_classification,
    l.overdue_days,
    l.penalty_amount,
    b.branch_name,
    CASE 
        WHEN l.overdue_days = 0 THEN 'Current'
        WHEN l.overdue_days BETWEEN 1 AND 30 THEN '1-30 Days'
        WHEN l.overdue_days BETWEEN 31 AND 60 THEN '31-60 Days'
        WHEN l.overdue_days BETWEEN 61 AND 90 THEN '61-90 Days'
        ELSE '90+ Days'
    END AS aging_bucket
FROM loans l
INNER JOIN loan_products lp ON l.loan_product_id = lp.id
INNER JOIN customers c ON l.customer_id = c.id
INNER JOIN branches b ON l.branch_id = b.id;

-- Card Portfolio View
CREATE OR REPLACE VIEW v_card_portfolio AS
SELECT 
    card.id,
    card.card_number,
    cp.product_name AS card_product_name,
    card.card_type,
    card.card_status,
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    card.credit_limit,
    card.available_limit,
    card.outstanding_amount,
    card.expiry_date,
    card.reward_points,
    a.account_number,
    a.available_balance AS linked_account_balance
FROM cards card
INNER JOIN card_products cp ON card.card_product_id = cp.id
INNER JOIN customers c ON card.customer_id = c.id
INNER JOIN accounts a ON card.account_id = a.id;

-- Branch Performance View
CREATE OR REPLACE VIEW v_branch_performance AS
SELECT 
    b.id,
    b.branch_code,
    b.branch_name,
    b.branch_type,
    COUNT(DISTINCT a.id) AS total_accounts,
    SUM(a.available_balance) AS total_deposits,
    COUNT(DISTINCT l.id) AS total_loans,
    SUM(l.total_outstanding) AS total_loan_outstanding,
    COUNT(DISTINCT CASE WHEN t.value_date = CURDATE() THEN t.id END) AS today_transactions,
    SUM(CASE WHEN t.value_date = CURDATE() THEN t.amount ELSE 0 END) AS today_transaction_volume,
    COUNT(DISTINCT c.id) AS total_customers
FROM branches b
LEFT JOIN accounts a ON b.id = a.branch_id AND a.account_status = 'active'
LEFT JOIN loans l ON b.id = l.branch_id AND l.loan_status IN ('active', 'disbursed')
LEFT JOIN transactions t ON b.id = t.branch_id AND t.transaction_status = 'completed'
LEFT JOIN customers c ON c.id IN (
    SELECT DISTINCT customer_id FROM accounts WHERE branch_id = b.id
)
WHERE b.status = 'active'
GROUP BY b.id, b.branch_code, b.branch_name, b.branch_type;

-- Daily Transaction Summary View
CREATE OR REPLACE VIEW v_daily_transaction_summary AS
SELECT 
    DATE(created_at) AS transaction_date,
    transaction_type,
    transaction_mode,
    transaction_status,
    currency_code,
    COUNT(*) AS transaction_count,
    SUM(amount) AS total_amount,
    AVG(amount) AS average_amount,
    MIN(amount) AS min_amount,
    MAX(amount) AS max_amount
FROM transactions
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY DATE(created_at), transaction_type, transaction_mode, transaction_status, currency_code;

-- Overdue Loans View
CREATE OR REPLACE VIEW v_overdue_loans AS
SELECT 
    l.loan_account_number,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.customer_id,
    u.email,
    u.phone,
    lp.product_name,
    l.emi_amount,
    lrs.due_date,
    DATEDIFF(CURDATE(), lrs.due_date) AS days_overdue,
    lrs.principal_amount + lrs.interest_amount AS overdue_amount,
    l.penalty_amount,
    l.npa_classification,
    b.branch_name
FROM loan_repayment_schedule lrs
INNER JOIN loans l ON lrs.loan_id = l.id
INNER JOIN loan_products lp ON l.loan_product_id = lp.id
INNER JOIN customers c ON l.customer_id = c.id
INNER JOIN users u ON c.user_id = u.id
INNER JOIN branches b ON l.branch_id = b.id
WHERE lrs.payment_status = 'overdue'
    AND l.loan_status = 'active'
ORDER BY days_overdue DESC;

-- Upcoming FD Maturities View
CREATE OR REPLACE VIEW v_upcoming_fd_maturities AS
SELECT 
    fd.fd_account_number,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.customer_id,
    u.email,
    u.phone,
    dp.product_name,
    fd.principal_amount,
    fd.maturity_amount,
    fd.interest_amount,
    fd.maturity_date,
    DATEDIFF(fd.maturity_date, CURDATE()) AS days_to_maturity,
    fd.is_auto_renewal,
    b.branch_name
FROM fixed_deposits fd
INNER JOIN deposit_products dp ON fd.deposit_product_id = dp.id
INNER JOIN customers c ON fd.customer_id = c.id
INNER JOIN users u ON c.user_id = u.id
INNER JOIN branches b ON fd.branch_id = b.id
WHERE fd.fd_status = 'active'
    AND fd.maturity_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY fd.maturity_date;

-- Customer Risk Profile View
CREATE OR REPLACE VIEW v_customer_risk_profile AS
SELECT 
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.risk_rating,
    c.kyc_status,
    c.is_pep,
    c.aml_status,
    COUNT(DISTINCT aml.id) AS aml_alerts_count,
    MAX(aml.severity) AS highest_alert_severity,
    SUM(CASE WHEN t.amount > 10000 AND t.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS large_transactions_last_30_days,
    c.annual_income,
    SUM(l.total_outstanding) AS total_loan_exposure
FROM customers c
LEFT JOIN aml_alerts aml ON c.id = aml.customer_id AND aml.alert_status NOT IN ('false_positive', 'closed')
LEFT JOIN accounts a ON c.id = a.customer_id
LEFT JOIN transactions t ON a.id = t.from_account_id
LEFT JOIN loans l ON c.id = l.customer_id AND l.loan_status IN ('active', 'disbursed')
WHERE c.deleted_at IS NULL
GROUP BY
    c.id,
    c.customer_id,
    c.first_name,
    c.last_name,
    c.risk_rating,
    c.kyc_status,
    c.is_pep,
    c.aml_status,
    c.annual_income;

-- ============================================================================
-- SECTION 17: STORED PROCEDURES
-- ============================================================================

-- Procedure: Calculate Interest for Savings Accounts
DELIMITER //
CREATE PROCEDURE sp_calculate_account_interest(
    IN p_account_id BIGINT UNSIGNED,
    IN p_calculation_date DATE
)
BEGIN
    DECLARE v_balance DECIMAL(18,2);
    DECLARE v_interest_rate DECIMAL(5,2);
    DECLARE v_days INT;
    DECLARE v_interest DECIMAL(12,2);
    
    SELECT available_balance, interest_rate INTO v_balance, v_interest_rate
    FROM accounts WHERE id = p_account_id;
    
    SET v_days = DATEDIFF(p_calculation_date, IFNULL((SELECT MAX(posting_date) FROM transactions WHERE from_account_id = p_account_id), p_calculation_date));
    
    -- Daily product method
    SET v_interest = (v_balance * v_interest_rate * v_days) / (365 * 100);
    
    UPDATE accounts 
    SET accrued_interest = accrued_interest + v_interest
    WHERE id = p_account_id;
    
    SELECT v_interest AS calculated_interest;
END //
DELIMITER ;

-- Procedure: Process EMI Payment
DELIMITER //
CREATE PROCEDURE sp_process_emi_payment(
    IN p_loan_id BIGINT UNSIGNED,
    IN p_payment_date DATE,
    IN p_payment_amount DECIMAL(12,2),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(500)
)
BEGIN
    DECLARE v_installment_id BIGINT UNSIGNED;
    DECLARE v_emi_amount DECIMAL(12,2);
    DECLARE v_repayment_account_id BIGINT UNSIGNED;
    DECLARE v_principal DECIMAL(12,2);
    DECLARE v_interest DECIMAL(12,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error processing EMI payment';
    END;
    
    START TRANSACTION;
    
    -- Get the next pending installment
    SELECT id, principal_amount, interest_amount, emi_amount 
    INTO v_installment_id, v_principal, v_interest, v_emi_amount
    FROM loan_repayment_schedule 
    WHERE loan_id = p_loan_id AND payment_status = 'pending'
    ORDER BY installment_number LIMIT 1;
    
    IF v_installment_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'No pending installments found';
        ROLLBACK;
    ELSE
        -- Get repayment account
        SELECT repayment_account_id INTO v_repayment_account_id FROM loans WHERE id = p_loan_id;
        
        -- Update installment
        UPDATE loan_repayment_schedule 
        SET payment_status = 'paid',
            paid_amount = p_payment_amount,
            paid_date = p_payment_date
        WHERE id = v_installment_id;
        
        -- Update loan outstanding
        UPDATE loans 
        SET outstanding_principal = outstanding_principal - v_principal,
            outstanding_interest = outstanding_interest - v_interest,
            total_outstanding = total_outstanding - p_payment_amount,
            total_paid = total_paid + p_payment_amount
        WHERE id = p_loan_id;
        
        -- Deduct from repayment account
        UPDATE accounts 
        SET available_balance = available_balance - p_payment_amount,
            ledger_balance = ledger_balance - p_payment_amount
        WHERE id = v_repayment_account_id;
        
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'EMI payment processed successfully';
    END IF;
END //
DELIMITER ;

-- Procedure: Transfer Funds
DELIMITER //
CREATE PROCEDURE sp_transfer_funds(
    IN p_from_account_id BIGINT UNSIGNED,
    IN p_to_account_id BIGINT UNSIGNED,
    IN p_amount DECIMAL(18,2),
    IN p_description TEXT,
    IN p_initiated_by BIGINT UNSIGNED,
    OUT p_transaction_ref VARCHAR(100),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(500)
)
BEGIN
    DECLARE v_from_balance DECIMAL(18,2);
    DECLARE v_from_status VARCHAR(20);
    DECLARE v_to_status VARCHAR(20);
    DECLARE v_transaction_id BIGINT UNSIGNED;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error processing fund transfer';
    END;
    
    START TRANSACTION;
    
    -- Check from account
    SELECT available_balance, account_status INTO v_from_balance, v_from_status
    FROM accounts WHERE id = p_from_account_id FOR UPDATE;
    
    -- Check to account
    SELECT account_status INTO v_to_status FROM accounts WHERE id = p_to_account_id FOR UPDATE;
    
    -- Validations
    IF v_from_status != 'active' THEN
        SET p_success = FALSE;
        SET p_message = 'Source account is not active';
        ROLLBACK;
    ELSEIF v_to_status != 'active' THEN
        SET p_success = FALSE;
        SET p_message = 'Destination account is not active';
        ROLLBACK;
    ELSEIF v_from_balance < p_amount THEN
        SET p_success = FALSE;
        SET p_message = 'Insufficient balance';
        ROLLBACK;
    ELSE
        -- Generate transaction reference
        SET p_transaction_ref = CONCAT('TXN', UNIX_TIMESTAMP(), LPAD(p_from_account_id, 6, '0'));
        
        -- Insert transaction
        INSERT INTO transactions (
            transaction_ref, transaction_type, transaction_mode,
            from_account_id, to_account_id, amount, currency_code,
            total_amount, transaction_status, initiated_by,
            description, value_date, posting_date
        ) VALUES (
            p_transaction_ref, 'internal_transfer', 'internet_banking',
            p_from_account_id, p_to_account_id, p_amount, 'USD',
            p_amount, 'completed', p_initiated_by,
            p_description, CURDATE(), NOW()
        );
        
        SET v_transaction_id = LAST_INSERT_ID();
        
        -- Update from account
        UPDATE accounts 
        SET available_balance = available_balance - p_amount,
            ledger_balance = ledger_balance - p_amount,
            last_transaction_date = CURDATE()
        WHERE id = p_from_account_id;
        
        -- Update to account
        UPDATE accounts 
        SET available_balance = available_balance + p_amount,
            ledger_balance = ledger_balance + p_amount,
            last_transaction_date = CURDATE()
        WHERE id = p_to_account_id;
        
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'Transfer completed successfully';
    END IF;
END //
DELIMITER ;

-- ============================================================================
-- SECTION 18: TRIGGERS
-- ============================================================================

-- Trigger: Update account balance after transaction
DELIMITER //
CREATE TRIGGER trg_after_transaction_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_status = 'completed' THEN
        -- Update last transaction date
        IF NEW.from_account_id IS NOT NULL THEN
            UPDATE accounts SET last_transaction_date = NEW.value_date 
            WHERE id = NEW.from_account_id;
        END IF;
        
        IF NEW.to_account_id IS NOT NULL THEN
            UPDATE accounts SET last_transaction_date = NEW.value_date 
            WHERE id = NEW.to_account_id;
        END IF;
    END IF;
END //
DELIMITER ;

-- Trigger: Log audit trail on user updates
DELIMITER //
CREATE TRIGGER trg_users_audit
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        user_id, action, entity_type, entity_id,
        old_values, new_values, performed_at
    ) VALUES (
        NEW.id, 'UPDATE', 'users', NEW.id,
        JSON_OBJECT('status', OLD.status, 'email', OLD.email),
        JSON_OBJECT('status', NEW.status, 'email', NEW.email),
        NOW()
    );
END //
DELIMITER ;

-- Trigger: Calculate loan overdue days
DELIMITER //
CREATE TRIGGER trg_update_loan_overdue
BEFORE UPDATE ON loan_repayment_schedule
FOR EACH ROW
BEGIN
    IF NEW.payment_status = 'overdue' AND OLD.payment_status != 'overdue' THEN
        UPDATE loans 
        SET overdue_days = DATEDIFF(CURDATE(), NEW.due_date)
        WHERE id = NEW.loan_id;
    END IF;
END //
DELIMITER ;

-- ============================================================================
-- SECTION 19: INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for frequently joined queries
CREATE INDEX idx_accounts_customer_status ON accounts(customer_id, account_status);
CREATE INDEX idx_transactions_account_date ON transactions(from_account_id, value_date);
CREATE INDEX idx_transactions_type_status ON transactions(transaction_type, transaction_status);
CREATE INDEX idx_loans_customer_status ON loans(customer_id, loan_status);
CREATE INDEX idx_cards_customer_status ON cards(customer_id, card_status);
CREATE INDEX idx_notifications_user_status ON notifications(user_id, status);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id, performed_at);

-- ============================================================================
-- SECTION 20: INITIAL DATA SEEDING
-- ============================================================================

-- Insert default roles
INSERT INTO roles (name, display_name, description, level) VALUES
('super_admin', 'Super Administrator', 'Full system access with override capabilities', 1),
('bank_admin', 'Bank Administrator', 'Bank-wide administrative access and management', 2),
('branch_manager', 'Branch Manager', 'Branch-level management and high-value transaction approval', 3),
('credit_manager', 'Credit Manager', 'Loan approval and credit limit management', 4),
('loan_officer', 'Loan Officer', 'Loan processing, assessment, and monitoring', 5),
('accountant', 'Accountant', 'General ledger, financial reporting, and reconciliation', 5),
('customer_service_officer', 'Customer Service Officer', 'Account opening, KYC verification, and customer support', 6),
('teller', 'Teller/Cashier', 'Counter operations and cash handling', 7),
('compliance_officer', 'Compliance Officer / Security Officer', 'AML monitoring and compliance reporting', 4),
('auditor', 'Auditor', 'Read-only audit access and compliance checks', 8),
('it_administrator', 'IT Administrator', 'System maintenance, backup, and technical support', 3),
('operations_manager', 'Operations Manager', 'Operational oversight and process management', 4),
('risk_analyst', 'Risk & Fraud Analyst', 'Risk assessment and fraud detection', 5),
('customer', 'Customer', 'Online banking customer access', 10),
('business_customer', 'Business Customer', 'Corporate/business banking access', 10),
('agent', 'Banking Agent', 'Banking correspondent agent access', 9);

-- Insert comprehensive permissions
INSERT INTO permissions (name, display_name, module, description) VALUES
-- User Management
('users.view', 'View Users', 'user_management', 'View user accounts'),
('users.create', 'Create Users', 'user_management', 'Create new user accounts'),
('users.update', 'Update Users', 'user_management', 'Modify user accounts'),
('users.delete', 'Delete Users', 'user_management', 'Delete user accounts'),
('users.assign_roles', 'Assign Roles', 'user_management', 'Assign roles to users'),

-- Branch Management
('branches.view', 'View Branches', 'branch_management', 'View branch information'),
('branches.create', 'Create Branches', 'branch_management', 'Create new branches'),
('branches.update', 'Update Branches', 'branch_management', 'Modify branch details'),
('branches.delete', 'Delete Branches', 'branch_management', 'Delete branches'),
('branches.manage_all', 'Manage All Branches', 'branch_management', 'Manage all branches'),

-- Customer Management
('customers.view', 'View Customers', 'customer_management', 'View customer profiles'),
('customers.create', 'Create Customers', 'customer_management', 'Create new customer accounts'),
('customers.update', 'Update Customers', 'customer_management', 'Modify customer information'),
('customers.delete', 'Delete Customers', 'customer_management', 'Delete customer accounts'),
('customers.kyc_verify', 'Verify KYC', 'customer_management', 'Verify KYC documents'),

-- Account Management
('accounts.view', 'View Accounts', 'account_management', 'View account details'),
('accounts.create', 'Create Accounts', 'account_management', 'Open new accounts'),
('accounts.update', 'Update Accounts', 'account_management', 'Modify account details'),
('accounts.close', 'Close Accounts', 'account_management', 'Close accounts'),
('accounts.freeze', 'Freeze Accounts', 'account_management', 'Freeze/unfreeze accounts'),
('accounts.view_balance', 'View Balance', 'account_management', 'View account balances'),

-- Transaction Management
('transactions.view', 'View Transactions', 'transaction_management', 'View transaction history'),
('transactions.create', 'Create Transactions', 'transaction_management', 'Process transactions'),
('transactions.approve', 'Approve Transactions', 'transaction_management', 'Approve high-value transactions'),
('transactions.reverse', 'Reverse Transactions', 'transaction_management', 'Reverse transactions'),
('transactions.cash_deposit', 'Cash Deposit', 'transaction_management', 'Process cash deposits'),
('transactions.cash_withdrawal', 'Cash Withdrawal', 'transaction_management', 'Process cash withdrawals'),
('transactions.fund_transfer', 'Fund Transfer', 'transaction_management', 'Transfer funds'),

-- Loan Management
('loans.view', 'View Loans', 'loan_management', 'View loan details'),
('loans.create', 'Create Loan Applications', 'loan_management', 'Create loan applications'),
('loans.assess', 'Assess Loans', 'loan_management', 'Assess loan eligibility'),
('loans.approve', 'Approve Loans', 'loan_management', 'Approve/reject loans'),
('loans.disburse', 'Disburse Loans', 'loan_management', 'Disburse approved loans'),
('loans.monitor', 'Monitor Loans', 'loan_management', 'Monitor loan repayments'),
('loans.set_credit_limit', 'Set Credit Limits', 'loan_management', 'Set and modify credit limits'),
('loans.restructure', 'Restructure Loans', 'loan_management', 'Restructure loan terms'),

-- Card Management
('cards.view', 'View Cards', 'card_management', 'View card details'),
('cards.issue', 'Issue Cards', 'card_management', 'Issue ATM/debit/credit cards'),
('cards.block', 'Block Cards', 'card_management', 'Block/unblock cards'),
('cards.set_limits', 'Set Card Limits', 'card_management', 'Set transaction limits'),

-- Cheque Management
('cheques.view', 'View Cheques', 'cheque_management', 'View cheque details'),
('cheques.issue', 'Issue Cheque Books', 'cheque_management', 'Issue cheque books'),
('cheques.stop_payment', 'Stop Payment', 'cheque_management', 'Stop cheque payments'),
('cheques.clear', 'Clear Cheques', 'cheque_management', 'Process cheque clearing'),

-- Deposit Management (FD/RD)
('deposits.view', 'View Deposits', 'deposit_management', 'View FD/RD details'),
('deposits.create', 'Create Deposits', 'deposit_management', 'Create FD/RD accounts'),
('deposits.premature_close', 'Premature Closure', 'deposit_management', 'Process premature closures'),
('deposits.renew', 'Renew Deposits', 'deposit_management', 'Renew matured deposits'),

-- Accounting & Ledger
('accounting.view_ledger', 'View Ledger', 'accounting', 'View general ledger'),
('accounting.create_entry', 'Create Entries', 'accounting', 'Create accounting entries'),
('accounting.reconciliation', 'Reconciliation', 'accounting', 'Perform bank reconciliation'),
('accounting.tax_calculation', 'Tax Calculation', 'accounting', 'Calculate taxes'),

-- Reports & Analytics
('reports.customer', 'Customer Reports', 'reports', 'Generate customer reports'),
('reports.transaction', 'Transaction Reports', 'reports', 'Generate transaction reports'),
('reports.loan', 'Loan Reports', 'reports', 'Generate loan reports'),
('reports.financial', 'Financial Reports', 'reports', 'Generate financial reports'),
('reports.regulatory', 'Regulatory Reports', 'reports', 'Generate regulatory reports'),
('reports.branch', 'Branch Reports', 'reports', 'Generate branch reports'),
('reports.audit', 'Audit Reports', 'reports', 'Generate audit reports'),
('reports.export', 'Export Reports', 'reports', 'Export reports to PDF/Excel'),

-- Audit & Compliance
('audit.view_logs', 'View Audit Logs', 'audit_compliance', 'View audit logs'),
('audit.compliance_check', 'Compliance Checks', 'audit_compliance', 'Perform compliance checks'),
('audit.aml_monitoring', 'AML Monitoring', 'audit_compliance', 'Monitor AML alerts'),
('audit.str_filing', 'STR Filing', 'audit_compliance', 'File suspicious transaction reports'),
('audit.fraud_detection', 'Fraud Detection', 'audit_compliance', 'Access fraud detection tools'),

-- System Configuration
('system.view_settings', 'View Settings', 'system', 'View system settings'),
('system.update_settings', 'Update Settings', 'system', 'Modify system settings'),
('system.backup', 'Backup & Recovery', 'system', 'Perform backup and recovery'),
('system.maintenance', 'System Maintenance', 'system', 'Perform system maintenance'),
('system.manage_api_keys', 'Manage API Keys', 'system', 'Manage third-party API keys'),

-- Notification Management
('notifications.send', 'Send Notifications', 'notifications', 'Send notifications'),
('notifications.view_queue', 'View Queue', 'notifications', 'View notification queue'),
('notifications.configure', 'Configure Templates', 'notifications', 'Configure notification templates'),

-- Cash Management
('cash.teller_position', 'Teller Cash Position', 'cash_management', 'Manage teller cash position'),
('cash.branch_vault', 'Branch Vault', 'cash_management', 'Manage branch vault'),
('cash.atm_replenishment', 'ATM Replenishment', 'cash_management', 'Manage ATM cash');

-- Assign permissions to Super Admin (all permissions)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Assign permissions to Bank Admin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module IN (
    'user_management', 'branch_management', 'customer_management', 'account_management',
    'transaction_management', 'loan_management', 'card_management', 'cheque_management',
    'deposit_management', 'accounting', 'reports', 'audit_compliance', 'notifications', 'cash_management'
);

-- Assign permissions to Branch Manager
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name IN (
    -- User management (branch only)
    'users.view', 'users.create', 'users.update', 'users.assign_roles',
    -- Branch management
    'branches.view', 'branches.update',
    -- Customer management
    'customers.view', 'customers.create', 'customers.update', 'customers.kyc_verify',
    -- Account management
    'accounts.view', 'accounts.create', 'accounts.update', 'accounts.close', 'accounts.freeze', 'accounts.view_balance',
    -- Transactions
    'transactions.view', 'transactions.create', 'transactions.approve', 'transactions.cash_deposit', 
    'transactions.cash_withdrawal', 'transactions.fund_transfer',
    -- Loans
    'loans.view', 'loans.create', 'loans.assess', 'loans.approve', 'loans.monitor',
    -- Cards & Cheques
    'cards.view', 'cards.issue', 'cards.block', 'cheques.view', 'cheques.issue', 'cheques.stop_payment',
    -- Deposits
    'deposits.view', 'deposits.create', 'deposits.premature_close',
    -- Reports
    'reports.customer', 'reports.transaction', 'reports.loan', 'reports.branch', 'reports.export',
    -- Cash management
    'cash.branch_vault', 'cash.atm_replenishment'
);

-- Assign permissions to Credit Manager
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE name IN (
    'customers.view', 'loans.view', 'loans.assess', 'loans.approve', 'loans.disburse',
    'loans.monitor', 'loans.set_credit_limit', 'loans.restructure',
    'reports.loan', 'reports.financial', 'reports.export'
);

-- Assign permissions to Loan Officer
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE name IN (
    'customers.view', 'loans.view', 'loans.create', 'loans.assess', 'loans.monitor',
    'reports.loan', 'reports.export'
);

-- Assign permissions to Accountant
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions WHERE name IN (
    'accounting.view_ledger', 'accounting.create_entry', 'accounting.reconciliation', 
    'accounting.tax_calculation', 'transactions.view', 'reports.financial', 
    'reports.transaction', 'reports.export'
);

-- Assign permissions to Customer Service Officer
INSERT INTO role_permissions (role_id, permission_id)
SELECT 7, id FROM permissions WHERE name IN (
    'customers.view', 'customers.create', 'customers.update', 'customers.kyc_verify',
    'accounts.view', 'accounts.create', 'accounts.update', 'accounts.view_balance',
    'cards.view', 'cards.issue', 'cheques.view', 'cheques.issue',
    'transactions.view', 'transactions.cash_deposit', 'transactions.cash_withdrawal', 'transactions.fund_transfer',
    'deposits.view', 'deposits.create', 'loans.view', 'loans.create'
);

-- Assign permissions to Teller
INSERT INTO role_permissions (role_id, permission_id)
SELECT 8, id FROM permissions WHERE name IN (
    'customers.view', 'accounts.view', 'accounts.view_balance',
    'transactions.view', 'transactions.create', 'transactions.cash_deposit', 
    'transactions.cash_withdrawal', 'transactions.fund_transfer',
    'cheques.view', 'cheques.clear', 'cash.teller_position'
);

-- Assign permissions to Compliance Officer
INSERT INTO role_permissions (role_id, permission_id)
SELECT 9, id FROM permissions WHERE name IN (
    'customers.view', 'accounts.view', 'transactions.view',
    'audit.view_logs', 'audit.compliance_check', 'audit.aml_monitoring', 
    'audit.str_filing', 'audit.fraud_detection',
    'reports.audit', 'reports.regulatory', 'reports.export'
);

-- Assign permissions to Auditor (all view permissions)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 10, id FROM permissions WHERE name LIKE '%.view%' OR name IN (
    'audit.view_logs', 'audit.compliance_check', 'reports.audit', 
    'reports.regulatory', 'reports.export'
);

-- Assign permissions to IT Administrator
INSERT INTO role_permissions (role_id, permission_id)
SELECT 11, id FROM permissions WHERE name IN (
    'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign_roles',
    'system.view_settings', 'system.update_settings', 'system.backup', 
    'system.maintenance', 'system.manage_api_keys',
    'audit.view_logs', 'notifications.view_queue', 'notifications.configure'
);

-- Assign permissions to Operations Manager
INSERT INTO role_permissions (role_id, permission_id)
SELECT 12, id FROM permissions WHERE module IN (
    'branch_management', 'customer_management', 'transaction_management',
    'cash_management', 'reports'
);

-- Assign permissions to Risk Analyst
INSERT INTO role_permissions (role_id, permission_id)
SELECT 13, id FROM permissions WHERE name IN (
    'customers.view', 'accounts.view', 'transactions.view', 'loans.view',
    'audit.view_logs', 'audit.fraud_detection', 'audit.aml_monitoring',
    'reports.audit', 'reports.regulatory', 'reports.loan', 'reports.export'
);

-- Assign permissions to Customer (own account access only)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 14, id FROM permissions WHERE name IN (
    'accounts.view', 'accounts.view_balance', 'transactions.view', 
    'transactions.fund_transfer', 'cards.view', 'loans.view', 'loans.create',
    'deposits.view', 'deposits.create', 'reports.customer', 'reports.export'
);

-- ============================================================================
-- ROLE-PERMISSION MAPPING REFERENCE
-- ============================================================================
/*
COMPREHENSIVE ROLE RESPONSIBILITIES:

1. SUPER ADMIN (ID: 1, Level: 1)
   - Manage all branches across organization
   - Create and manage all user accounts
   - Assign roles and permissions
   - System-wide configuration
   - View all reports and analytics
   - Emergency access and override
   - Session Timeout: 60 minutes
   - Security: Hardware token mandatory

2. BANK ADMIN (ID: 2, Level: 2)
   - Manage bank-level operations
   - User management (except Super Admin)
   - Configure branch settings
   - Monitor bank performance
   - Bank-wide reporting
   - Session Timeout: 60 minutes
   - Security: 2FA mandatory

3. BRANCH MANAGER (ID: 3, Level: 3)
   - Manage specific branch operations
   - Approve high-value transactions
   - Monitor staff activities
   - Branch-scoped user management
   - Branch reports and analytics
   - Session Timeout: 45 minutes
   - Security: 2FA mandatory

4. CREDIT MANAGER (ID: 4, Level: 4)
   - Approve/reject loan applications
   - Set and modify credit limits
   - Review loan risks
   - Manage NPAs and overdue loans
   - Portfolio analytics
   - Session Timeout: 45 minutes
   - Security: 2FA mandatory

5. LOAN OFFICER (ID: 5, Level: 5)
   - Receive and process loan applications
   - Verify loan documents
   - Assess loan eligibility
   - Recommend approval/rejection
   - Monitor loan repayments
   - Session Timeout: 30 minutes
   - Security: 2FA recommended

6. ACCOUNTANT (ID: 6, Level: 5)
   - General ledger management
   - Financial reporting
   - Bank reconciliation
   - Tax calculations
   - Support audits
   - Session Timeout: 45 minutes
   - Security: 2FA recommended

7. CUSTOMER SERVICE OFFICER (ID: 7, Level: 6)
   - Open new customer accounts
   - Update customer information
   - Issue ATM cards and checkbooks
   - Handle customer requests
   - KYC verification
   - Session Timeout: 30 minutes
   - Security: 2FA recommended

8. TELLER / CASHIER (ID: 8, Level: 7)
   - Process cash deposits/withdrawals
   - Transfer funds
   - Handle daily cash transactions
   - Print receipts
   - Balance cash drawer
   - Session Timeout: 10 minutes
   - Security: PIN-based quick login

9. COMPLIANCE OFFICER (ID: 9, Level: 4)
   - Monitor suspicious transactions
   - Perform AML checks
   - Generate compliance reports
   - User activity monitoring
   - Sanctions/PEP screening
   - Session Timeout: 45 minutes
   - Security: 2FA mandatory

10. AUDITOR (ID: 10, Level: 8)
    - Review transaction history
    - Access audit logs
    - Compliance checks
    - Fraud detection reports
    - Read-only system access
    - Session Timeout: 60 minutes
    - Security: Time-limited access

11. IT ADMINISTRATOR (ID: 11, Level: 3)
    - Manage servers and databases
    - Backup and recovery
    - System maintenance
    - Security updates
    - User access control (technical)
    - Session Timeout: 60 minutes
    - Security: IP whitelist + 2FA mandatory

12. OPERATIONS MANAGER (ID: 12, Level: 4)
    - Operational oversight
    - Process management
    - Branch coordination
    - Performance monitoring
    - Session Timeout: 45 minutes

13. RISK ANALYST (ID: 13, Level: 5)
    - Risk assessment
    - Fraud detection
    - Pattern analysis
    - Risk reporting
    - Session Timeout: 45 minutes

14. CUSTOMER (ID: 14, Level: 10)
    - View account balances
    - Transfer funds
    - Pay bills
    - Download statements
    - Manage profile
    - Apply for products
    - Session Timeout: 15-30 minutes
    - Security: 2FA for transactions

MODULE ACCESS SUMMARY:
=====================
✅ = Full Access | 👁 = View Only | ⚠️ = Limited Access | ❌ = No Access

Module                    | SuperAdmin | BankAdmin | BranchMgr | Teller | CSO | LoanOfficer | CreditMgr | Accountant | Auditor | Compliance | ITAdmin | Customer
--------------------------|------------|-----------|-----------|--------|-----|-------------|-----------|------------|---------|------------|---------|----------
User Management           | ✅         | ✅        | ⚠️        | ❌     | ❌  | ❌          | ❌        | ❌         | 👁      | ❌         | ✅      | ❌
Branch Management         | ✅         | ✅        | ⚠️        | ❌     | ❌  | ❌          | ❌        | ❌         | 👁      | ❌         | ❌      | ❌
Customer Management       | ✅         | ✅        | ✅        | 👁     | ✅  | 👁          | 👁        | ❌         | 👁      | 👁         | ❌      | ⚠️
Account Management        | ✅         | ✅        | ✅        | ⚠️     | ✅  | ❌          | ❌        | ❌         | 👁      | 👁         | ❌      | ⚠️
Transaction Management    | ✅         | ✅        | ✅        | ✅     | ✅  | ❌          | ❌        | 👁         | 👁      | 👁         | ❌      | ⚠️
Loan Management           | ✅         | ✅        | ✅        | ❌     | ⚠️  | ✅          | ✅        | ❌         | 👁      | 👁         | ❌      | ⚠️
Card Management           | ✅         | ✅        | ✅        | ❌     | ✅  | ❌          | ❌        | ❌         | 👁      | 👁         | ❌      | 👁
Deposit Management        | ✅         | ✅        | ✅        | ⚠️     | ✅  | ❌          | ❌        | ❌         | 👁      | 👁         | ❌      | ⚠️
Accounting & Ledger       | ✅         | ✅        | ❌        | ❌     | ❌  | ❌          | ❌        | ✅         | 👁      | 👁         | ❌      | ❌
Reports & Analytics       | ✅         | ✅        | ⚠️        | ❌     | ❌  | ⚠️          | ⚠️        | ✅         | ✅      | ✅         | ❌      | ⚠️
Audit & Compliance        | ✅         | ✅        | 👁        | ❌     | ❌  | ❌          | ❌        | ❌         | ✅      | ✅         | ✅      | ❌
System Configuration      | ✅         | ⚠️        | ❌        | ❌     | ❌  | ❌          | ❌        | ❌         | ❌      | ❌         | ✅      | ❌
Cash Management           | ✅         | ✅        | ✅        | ✅     | ⚠️  | ❌          | ❌        | ❌         | 👁      | 👁         | ❌      | ❌

*/



-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES
('bank_name', 'Modern Bank', 'string', 'general', 'Bank Name'),
('bank_code', 'MBNK', 'string', 'general', 'Bank Code'),
('currency_default', 'USD', 'string', 'general', 'Default Currency'),
('interest_posting_day', '1', 'number', 'accounts', 'Day of month for interest posting'),
('min_balance_penalty', '25.00', 'number', 'accounts', 'Minimum balance penalty amount'),
('transaction_limit_daily', '50000.00', 'number', 'transactions', 'Default daily transaction limit'),
('loan_approval_threshold', '100000.00', 'number', 'loans', 'Amount requiring approval'),
('npa_days_threshold', '90', 'number', 'loans', 'Days overdue to classify as NPA'),
('session_timeout_minutes', '30', 'number', 'security', 'Session timeout in minutes'),
('max_login_attempts', '5', 'number', 'security', 'Maximum failed login attempts'),
('otp_expiry_minutes', '5', 'number', 'security', 'OTP expiry time in minutes'),
('aml_transaction_threshold', '10000.00', 'number', 'compliance', 'Amount triggering AML alert'),
('email_from_address', 'noreply@modernbank.com', 'string', 'notifications', 'From email address'),
('sms_provider', 'twilio', 'string', 'notifications', 'SMS gateway provider'),
('teller_session_timeout', '10', 'number', 'security', 'Teller session timeout in minutes'),
('customer_session_timeout', '30', 'number', 'security', 'Customer session timeout in minutes'),
('admin_session_timeout', '60', 'number', 'security', 'Admin session timeout in minutes'),
('require_2fa_admin', 'true', 'boolean', 'security', 'Require 2FA for admin roles'),
('require_2fa_transaction', 'true', 'boolean', 'security', 'Require 2FA for customer transactions'),
('high_value_transaction_threshold', '10000.00', 'number', 'transactions', 'Threshold for manager approval'),
('maker_checker_enabled', 'true', 'boolean', 'transactions', 'Enable maker-checker for high value transactions'),
('branch_manager_approval_limit', '50000.00', 'number', 'transactions', 'Branch manager approval limit'),
('super_admin_hardware_token', 'true', 'boolean', 'security', 'Require hardware token for Super Admin');

-- ============================================================================
-- COMPLETION
-- ============================================================================

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- End of Modern Bank Management System Database Schema
-- Total Tables: 80+
-- Total Views: 10+
-- Total Stored Procedures: 3
-- Total Triggers: 3
