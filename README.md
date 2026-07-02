# 🏦 Modern Bank Management System

> **Enterprise-Grade Digital Banking Platform**  
> A full-stack banking solution with Vue.js 3, Laravel 11, and MySQL 8

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.4+-green.svg)](https://vuejs.org)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)](https://www.mysql.com)
[![License](https://img.shields.io/badge/License-Proprietary-yellow.svg)]()

---

## 📋 Table of Contents

- [Overview](#-overview)
- [System Architecture](#-system-architecture)
- [Data Flow Diagrams](#-data-flow-diagrams)
- [Entity Relationship Diagram](#-entity-relationship-diagram)
- [Key Features](#-key-features)
- [Technology Stack](#-technology-stack)
- [User Roles & Permissions](#-user-roles--permissions)
- [Module Overview](#-module-overview)
- [Database Schema](#-database-schema)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Documentation](#-api-documentation)
- [Security](#-security)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🎯 Overview

The **Modern Bank Management System (MBMS)** is a comprehensive, enterprise-grade digital banking platform designed to automate and streamline all banking operations. Built with modern technologies and following industry best practices, it provides a secure, scalable, and feature-rich solution for:

- ✅ Regional Banks
- ✅ Cooperative Banks
- ✅ Microfinance Institutions
- ✅ Fintech Startups
- ✅ Credit Unions

### Vision

To deliver a banking platform that rivals Tier-1 global banks in capability, security, and user experience while being fully customizable for different banking needs.

### Key Objectives

- 🔄 Replace legacy core banking systems with cloud-native architecture
- 🌐 Deliver seamless omni-channel customer experiences
- 🔒 Achieve PCI-DSS, ISO 27001, SOC 2, GDPR compliance
- ⚡ Enable real-time transactions and fraud detection
- 💰 Support multi-currency, multi-branch operations
- 📊 Provide actionable analytics and AI-driven insights

---

## 🏗️ System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  Vue.js SPA  │  │ Admin Panel  │  │Teller Terminal│          │
│  │  (Customer)  │  │  (Management)│  │   (Branch)    │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└─────────────────────────────────────────────────────────────────┘
                             │
                        HTTPS / WSS
                             │
┌─────────────────────────────────────────────────────────────────┐
│                     API GATEWAY LAYER                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Laravel Sanctum Auth · Rate Limiting · WAF              │  │
│  │  CORS · Request Validation · API Versioning              │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                             │
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐│
│  │  REST API       │  │  WebSockets     │  │  Job Queues     ││
│  │  Controllers    │  │  (Real-time)    │  │  (Background)   ││
│  └─────────────────┘  └─────────────────┘  └─────────────────┘│
│  ┌─────────────────────────────────────────────────────────────┤│
│  │  Business Logic Layer                                       ││
│  │  • Services  • Repositories  • Policies  • Events          ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
                             │
┌─────────────────────────────────────────────────────────────────┐
│                      DATA LAYER                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │   MySQL 8.0  │  │    Redis     │  │Elasticsearch │         │
│  │   (Primary)  │  │ (Cache/Queue)│  │  (Search)    │         │
│  │ Read Replica │  │   Sessions   │  │   AML/Audit  │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
└─────────────────────────────────────────────────────────────────┘
                             │
┌─────────────────────────────────────────────────────────────────┐
│                 EXTERNAL INTEGRATIONS                            │
│  Payment Gateways │ SMS/Email │ KYC APIs │ Credit Bureaus      │
│  SWIFT │ NEFT/RTGS │ UPI │ Forex APIs │ Open Banking           │
└─────────────────────────────────────────────────────────────────┘
```

### Design Patterns

- **Backend:** Repository Pattern, Service Layer, CQRS, Event Sourcing
- **Frontend:** Composition API, Pinia Store, Atomic Design
- **Database:** OLTP for transactions, Materialized views for reporting
- **Messaging:** Redis Pub/Sub + Laravel Events for real-time updates

---

## 🔄 Data Flow Diagrams

### 1. Customer Account Opening Flow

```
┌─────────────┐
│  Customer   │
└──────┬──────┘
       │ 1. Submit Application
       ▼
┌─────────────────────┐
│   Web Portal/App    │
└──────┬──────────────┘
       │ 2. Upload KYC Documents
       ▼
┌─────────────────────┐
│   API Gateway       │
└──────┬──────────────┘
       │ 3. Validate Request
       ▼
┌─────────────────────────────────────────────────────┐
│          Application Service                        │
│  ┌─────────────────────────────────────────────┐   │
│  │ 4. Create Customer Record                   │   │
│  │ 5. Store Documents                          │   │
│  │ 6. Trigger KYC Verification                 │   │
│  └─────────────────────────────────────────────┘   │
└──────┬──────────────────────────────────┬──────────┘
       │                                   │
       │ 7. Save to DB                     │ 8. Queue Job
       ▼                                   ▼
┌─────────────┐                    ┌──────────────┐
│   MySQL     │                    │  Job Queue   │
│  Database   │                    │   (Redis)    │
└─────────────┘                    └──────┬───────┘
                                          │ 9. Process
                                          ▼
                              ┌────────────────────────┐
                              │  Background Worker     │
                              │  • OCR Verification    │
                              │  • AML Screening       │
                              │  • PEP Check           │
                              └──────┬─────────────────┘
                                     │ 10. Update Status
                                     ▼
                              ┌─────────────────┐
                              │ Notification    │
                              │ Service         │
                              └──────┬──────────┘
                                     │ 11. Send Alert
                                     ▼
                              ┌─────────────────┐
                              │ Customer        │
                              │ (SMS/Email)     │
                              └─────────────────┘
```

### 2. Fund Transfer Transaction Flow

```
┌─────────────┐
│  Customer   │
└──────┬──────┘
       │ 1. Initiate Transfer
       ▼
┌─────────────────────┐
│   Web/Mobile App    │
└──────┬──────────────┘
       │ 2. Enter Details + 2FA
       ▼
┌─────────────────────────────────────────────────────────┐
│              API Gateway                                 │
│  • Authentication Check                                  │
│  • Rate Limiting                                         │
│  • Request Validation                                    │
└──────┬──────────────────────────────────────────────────┘
       │ 3. Validated Request
       ▼
┌─────────────────────────────────────────────────────────┐
│          Transaction Service                             │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 4. Check Balance                                │   │
│  │ 5. Validate Limits                              │   │
│  │ 6. Check Fraud Rules                            │   │
│  │ 7. Lock Accounts (Pessimistic)                  │   │
│  └─────────────────────────────────────────────────┘   │
└──────┬──────────────────────────────────────────────────┘
       │
       │ 8. Begin DB Transaction
       ▼
┌─────────────────────────────────────────────────────────┐
│              Database Transaction                        │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 9. Debit From Account                           │   │
│  │ 10. Credit To Account                           │   │
│  │ 11. Insert Transaction Record                   │   │
│  │ 12. Update Balances                             │   │
│  │ 13. Log Audit Trail                             │   │
│  └─────────────────────────────────────────────────┘   │
└──────┬────────────────────┬─────────────────────────────┘
       │ Success            │ Rollback on Error
       ▼                    ▼
┌──────────────┐      ┌─────────────┐
│   Commit     │      │   Rollback  │
└──────┬───────┘      └──────┬──────┘
       │                     │
       │ 14. Trigger Events  │ 15. Log Error
       ▼                     ▼
┌────────────────────────────────────────┐
│       Event Broadcasting               │
│  • Send SMS/Email Notification         │
│  • Update Real-time Dashboard          │
│  • Trigger AML Check (if > threshold)  │
│  • Generate Receipt                    │
└────────────────────────────────────────┘
```

### 3. Loan Application Processing Flow

```
┌─────────────┐
│  Customer   │
└──────┬──────┘
       │ 1. Apply for Loan
       ▼
┌─────────────────────────────────────────────────────────┐
│          Loan Application Portal                         │
│  • Personal Information                                  │
│  • Loan Details (Amount, Tenure, Purpose)               │
│  • Document Upload                                       │
└──────┬──────────────────────────────────────────────────┘
       │ 2. Submit Application
       ▼
┌─────────────────────────────────────────────────────────┐
│          Loan Origination System (LOS)                   │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 3. Eligibility Check                            │   │
│  │    • Age, Income, Employment                    │   │
│  │    • Existing Liabilities                       │   │
│  └─────────────────────────────────────────────────┘   │
└──────┬──────────────────────────────────────────────────┘
       │ 4. Eligible
       ▼
┌─────────────────────────────────────────────────────────┐
│       Credit Bureau Integration                          │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 5. Fetch CIBIL/Experian Score                   │   │
│  │ 6. Credit History Analysis                      │   │
│  └─────────────────────────────────────────────────┘   │
└──────┬──────────────────────────────────────────────────┘
       │ 7. Credit Report
       ▼
┌─────────────────────────────────────────────────────────┐
│         Credit Scoring Engine                            │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 8. Calculate Risk Score                         │   │
│  │    • Credit Score Weight                        │   │
│  │    • Income Stability                           │   │
│  │    • Debt-to-Income Ratio                       │   │
│  │ 9. Risk Classification                          │   │
│  └─────────────────────────────────────────────────┘   │
└──────┬──────────────────────────────────────────────────┘
       │ 10. Assign to Loan Officer
       ▼
┌─────────────────────────────────────────────────────────┐
│           Loan Officer Review                            │
│  • Document Verification                                 │
│  • Income Verification                                   │
│  • Field Investigation (if needed)                      │
└──────┬──────────────────────────────────────────────────┘
       │ 11. Recommendation
       ▼
┌─────────────────────────────────────────────────────────┐
│          Credit Manager Approval                         │
│  • Review Risk Assessment                                │
│  • Set Interest Rate                                     │
│  • Approve/Reject with Comments                         │
└──────┬────────────────────┬─────────────────────────────┘
       │ Approved           │ Rejected
       ▼                    ▼
┌──────────────┐      ┌─────────────────┐
│   Sanctioned │      │  Send Rejection │
│   Letter     │      │  Letter         │
└──────┬───────┘      └─────────────────┘
       │ 12. Sign Agreement
       ▼
┌─────────────────────────────────────────────────────────┐
│          Loan Disbursement                               │
│  • Create Loan Account                                   │
│  • Generate EMI Schedule                                 │
│  • Credit Amount to Customer Account                    │
│  • Setup Auto-debit                                      │
└─────────────────────────────────────────────────────────┘
```

### 4. Daily End-of-Day (EOD) Processing Flow

```
┌─────────────────────────────────────────────────────────┐
│        Scheduled Job (Cron) - 11:59 PM                   │
└──────┬──────────────────────────────────────────────────┘
       │ 1. Trigger EOD Process
       ▼
┌─────────────────────────────────────────────────────────┐
│          EOD Controller                                  │
│  • Check if EOD already processed                        │
│  • Lock system for critical operations                  │
└──────┬──────────────────────────────────────────────────┘
       │
       ├─────────────────────────────────────────────────┐
       │                                                  │
       ▼                                                  ▼
┌──────────────────────┐                    ┌─────────────────────┐
│ Interest Calculation │                    │ Teller Balancing    │
│ • Daily Interest     │                    │ • Verify Cash       │
│ • Accrue to Accounts │                    │ • Match Records     │
└──────┬───────────────┘                    └─────────┬───────────┘
       │                                              │
       ▼                                              ▼
┌──────────────────────┐                    ┌─────────────────────┐
│ Loan EMI Processing  │                    │ Card Settlement     │
│ • Check Due EMIs     │                    │ • Settle Card Txns  │
│ • Auto-debit         │                    │ • Update Balances   │
│ • Mark Overdue       │                    └─────────┬───────────┘
└──────┬───────────────┘                              │
       │                                              │
       ▼                                              ▼
┌──────────────────────┐                    ┌─────────────────────┐
│ Penalty Application  │                    │ Transaction         │
│ • Minimum Balance    │                    │ Reconciliation      │
│ • Overdue Loans      │                    │ • Match All Txns    │
│ • Bounced Cheques    │                    │ • Generate Report   │
└──────┬───────────────┘                    └─────────┬───────────┘
       │                                              │
       └──────────────────┬───────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│          Generate Reports                                │
│  • Daily Transaction Summary                             │
│  • Branch-wise Collection                                │
│  • Account Balance Summary                               │
│  • Deposit Mobilization                                  │
│  • Loan Portfolio Status                                 │
└──────┬──────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│          Backup & Archival                               │
│  • Database Backup                                       │
│  • Archive Logs                                          │
│  • Store Reports                                         │
└──────┬──────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│          Mark EOD Complete                               │
│  • Update System Date                                    │
│  • Unlock System                                         │
│  • Send EOD Completion Notifications                    │
└─────────────────────────────────────────────────────────┘
```

### 5. AML/Compliance Monitoring Flow

```
┌─────────────────────────────────────────────────────────┐
│           Real-time Transaction Stream                   │
└──────┬──────────────────────────────────────────────────┘
       │ Every Transaction
       ▼
┌─────────────────────────────────────────────────────────┐
│          AML Monitoring Engine                           │
│  ┌─────────────────────────────────────────────────┐   │
│  │ Rule-based Checks:                              │   │
│  │ • Velocity Check (frequency)                    │   │
│  │ • Threshold Breach (amount > limit)             │   │
│  │ • Pattern Detection (unusual behavior)          │   │
│  │ • Country Risk (high-risk jurisdictions)        │   │
│  │ • Beneficiary Screening                         │   │
│  └─────────────────────────────────────────────────┘   │
└──────┬────────────────────┬─────────────────────────────┘
       │ Alert Triggered    │ No Alert
       ▼                    ▼
┌──────────────────┐   ┌────────────┐
│  Create AML      │   │  Continue  │
│  Alert           │   │  Normal    │
└──────┬───────────┘   └────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│          Alert Classification                            │
│  • Low, Medium, High, Critical                          │
│  • Assign to Compliance Officer                         │
└──────┬──────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│      Compliance Officer Review                           │
│  • Investigate Transaction                               │
│  • Review Customer Profile                               │
│  • Check Historical Patterns                            │
│  • Document Findings                                     │
└──────┬────────────────────┬─────────────────────────────┘
       │ Suspicious         │ False Positive
       ▼                    ▼
┌──────────────────┐   ┌────────────────┐
│  Escalate to     │   │  Close Alert   │
│  Manager         │   │  Mark Safe     │
└──────┬───────────┘   └────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│          STR Preparation                                 │
│  • Compile Evidence                                      │
│  • Prepare Report                                        │
│  • Get Management Approval                              │
└──────┬──────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│          File STR with FIU                               │
│  • Submit to Financial Intelligence Unit                 │
│  • Track Submission                                      │
│  • Maintain Records (5 years)                           │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Entity Relationship Diagram

### Core Banking ER Diagram

The following comprehensive ER diagram illustrates the complete database structure of the Bank Management System, showing all major entities and their relationships.

```mermaid
erDiagram
    %% ========================================
    %% USER MANAGEMENT & AUTHENTICATION
    %% ========================================
    
    users ||--o{ customers : "has"
    users ||--o{ staff : "has"
    users ||--o{ user_roles : "assigned"
    users ||--o{ sessions : "creates"
    users ||--o{ password_reset_tokens : "requests"
    users ||--o{ otp_tokens : "generates"
    users ||--o{ notifications : "receives"
    
    roles ||--o{ user_roles : "assigned_to"
    roles ||--o{ role_permissions : "has"
    permissions ||--o{ role_permissions : "belongs_to"
    
    users {
        bigint id PK
        char uuid UK
        varchar email UK
        varchar phone UK
        varchar password_hash
        enum user_type
        enum status
        timestamp email_verified_at
        boolean two_factor_enabled
        timestamp created_at
    }
    
    roles {
        bigint id PK
        varchar name UK
        varchar display_name
        int level
        timestamp created_at
    }
    
    permissions {
        bigint id PK
        varchar name UK
        varchar module
        varchar display_name
        timestamp created_at
    }
    
    %% ========================================
    %% CUSTOMER MANAGEMENT
    %% ========================================
    
    customers ||--o{ customer_addresses : "has"
    customers ||--o{ kyc_documents : "submits"
    customers ||--o{ business_customers : "extends"
    customers ||--o{ customer_relationships : "relates_to"
    customers ||--o{ customer_complaints : "files"
    customers ||--o{ accounts : "owns"
    customers ||--o{ loans : "applies_for"
    customers ||--o{ cards : "holds"
    customers ||--o{ fixed_deposits : "invests_in"
    customers ||--o{ recurring_deposits : "invests_in"
    customers ||--o{ customer_investments : "makes"
    customers ||--o{ customer_insurance_policies : "purchases"
    
    customers {
        bigint id PK
        bigint user_id FK UK
        varchar customer_id UK
        enum customer_type
        enum segment
        varchar first_name
        varchar last_name
        date date_of_birth
        enum gender
        enum kyc_status
        enum risk_rating
        boolean is_pep
        enum aml_status
        timestamp created_at
    }
    
    customer_addresses {
        bigint id PK
        bigint customer_id FK
        enum address_type
        varchar address_line1
        varchar city
        varchar state
        varchar country
        varchar postal_code
        boolean is_primary
        timestamp created_at
    }
    
    kyc_documents {
        bigint id PK
        bigint customer_id FK
        enum document_type
        varchar document_number
        varchar file_path
        enum verification_status
        timestamp verified_at
        date expiry_date
        timestamp created_at
    }
    
    business_customers {
        bigint id PK
        bigint customer_id FK UK
        varchar business_name
        varchar registration_number UK
        varchar tax_id
        date incorporation_date
        decimal annual_turnover
        timestamp created_at
    }
    
    customer_complaints {
        bigint id PK
        varchar complaint_number UK
        bigint customer_id FK
        enum category
        varchar subject
        text description
        enum priority
        enum status
        timestamp created_at
    }
    
    %% ========================================
    %% BRANCH & ORGANIZATION
    %% ========================================
    
    branches ||--o{ staff : "employs"
    branches ||--o{ accounts : "manages"
    branches ||--o{ atms : "operates"
    branches ||--o{ transactions : "processes"
    branches ||--o{ loans : "originates"
    branches ||--o{ teller_cash_positions : "tracks"
    branches ||--o{ branch_holidays : "observes"
    
    branches {
        bigint id PK
        varchar branch_code UK
        varchar branch_name
        enum branch_type
        varchar ifsc_code UK
        varchar swift_code
        varchar email
        varchar phone
        varchar city
        varchar state
        enum status
        date opened_date
        timestamp created_at
    }
    
    staff {
        bigint id PK
        bigint user_id FK UK
        varchar employee_id UK
        bigint branch_id FK
        varchar designation
        varchar department
        date date_of_joining
        enum employment_type
        decimal salary
        timestamp created_at
    }
    
    atms {
        bigint id PK
        varchar atm_id UK
        varchar atm_name
        bigint branch_id FK
        enum location_type
        varchar city
        enum status
        decimal cash_available
        timestamp last_cash_replenishment
        timestamp created_at
    }
    
    %% ========================================
    %% ACCOUNT MANAGEMENT
    %% ========================================
    
    account_types ||--o{ accounts : "defines"
    accounts ||--o{ joint_account_holders : "has"
    accounts ||--o{ transactions : "from"
    accounts ||--o{ transactions : "to"
    accounts ||--o{ standing_instructions : "automates"
    accounts ||--o{ beneficiaries : "pays_to"
    accounts ||--o{ bill_payments : "pays_from"
    accounts ||--o{ cheque_books : "issues"
    accounts ||--o{ cheques : "draws"
    
    account_types {
        bigint id PK
        varchar code UK
        varchar name
        enum category
        decimal min_opening_balance
        decimal interest_rate
        enum interest_calculation_method
        boolean allow_cheque_book
        boolean is_active
        timestamp created_at
    }
    
    accounts {
        bigint id PK
        varchar account_number UK
        bigint account_type_id FK
        bigint customer_id FK
        bigint branch_id FK
        varchar currency_code
        varchar account_title
        enum account_status
        date opening_date
        decimal available_balance
        decimal ledger_balance
        decimal hold_amount
        decimal interest_rate
        boolean is_joint_account
        timestamp created_at
    }
    
    joint_account_holders {
        bigint id PK
        bigint account_id FK
        bigint customer_id FK
        boolean is_primary
        enum authorization_level
        date added_date
        timestamp created_at
    }
    
    standing_instructions {
        bigint id PK
        varchar instruction_number UK
        bigint account_id FK
        enum instruction_type
        varchar beneficiary_account_number
        decimal amount
        enum frequency
        date start_date
        date next_execution_date
        enum status
        timestamp created_at
    }
    
    beneficiaries {
        bigint id PK
        bigint customer_id FK
        varchar beneficiary_name
        varchar account_number
        varchar ifsc_code
        enum beneficiary_type
        varchar nickname
        enum verification_status
        timestamp created_at
    }
    
    %% ========================================
    %% TRANSACTION MANAGEMENT
    %% ========================================
    
    transactions ||--o{ transaction_approvals : "requires"
    transactions ||--o{ bill_payments : "records"
    transactions ||--o{ aml_alerts : "triggers"
    
    transactions {
        bigint id PK
        varchar transaction_ref UK
        enum transaction_type
        enum transaction_mode
        bigint from_account_id FK
        bigint to_account_id FK
        decimal amount
        varchar currency_code
        decimal transaction_fee
        decimal total_amount
        enum transaction_status
        bigint initiated_by FK
        bigint branch_id FK
        date value_date
        timestamp posting_date
        boolean requires_approval
        enum approval_status
        timestamp created_at
    }
    
    transaction_approvals {
        bigint id PK
        bigint transaction_id FK
        int level
        bigint approver_id FK
        enum status
        text comments
        timestamp approved_at
        timestamp created_at
    }
    
    cheque_books {
        bigint id PK
        varchar cheque_book_number UK
        bigint account_id FK
        int number_of_leaves
        varchar start_cheque_number
        varchar end_cheque_number
        date issue_date
        enum status
        timestamp created_at
    }
    
    cheques {
        bigint id PK
        varchar cheque_number
        bigint account_id FK
        bigint cheque_book_id FK
        decimal amount
        varchar payee_name
        date cheque_date
        date clearance_date
        enum status
        bigint transaction_id FK
        timestamp created_at
    }
    
    bill_payments {
        bigint id PK
        bigint transaction_id FK UK
        bigint account_id FK
        enum biller_category
        varchar biller_name
        varchar consumer_number
        decimal bill_amount
        date due_date
        boolean is_autopay
        timestamp created_at
    }
    
    %% ========================================
    %% LOAN MANAGEMENT
    %% ========================================
    
    loan_products ||--o{ loan_applications : "applied_for"
    loan_products ||--o{ loans : "type_of"
    loan_applications ||--o{ loan_application_documents : "supports"
    loan_applications ||--o{ loans : "sanctioned_as"
    loans ||--o{ loan_repayment_schedule : "has"
    loans ||--o{ loan_collaterals : "secured_by"
    loans ||--o{ loan_guarantors : "guaranteed_by"
    
    loan_products {
        bigint id PK
        varchar product_code UK
        varchar product_name
        enum product_type
        decimal min_amount
        decimal max_amount
        int min_tenure_months
        int max_tenure_months
        decimal interest_rate_min
        decimal interest_rate_max
        enum interest_rate_type
        boolean is_active
        timestamp created_at
    }
    
    loan_applications {
        bigint id PK
        varchar application_number UK
        bigint loan_product_id FK
        bigint customer_id FK
        decimal applied_amount
        int applied_tenure_months
        text purpose
        enum application_status
        decimal sanctioned_amount
        decimal sanctioned_interest_rate
        timestamp sanctioned_at
        timestamp created_at
    }
    
    loans {
        bigint id PK
        varchar loan_account_number UK
        bigint application_id FK
        bigint loan_product_id FK
        bigint customer_id FK
        bigint branch_id FK
        decimal principal_amount
        decimal interest_rate
        int tenure_months
        decimal emi_amount
        date disbursement_date
        date maturity_date
        decimal outstanding_principal
        decimal total_outstanding
        enum loan_status
        enum npa_classification
        int overdue_days
        timestamp created_at
    }
    
    loan_repayment_schedule {
        bigint id PK
        bigint loan_id FK
        int installment_number
        date due_date
        decimal principal_amount
        decimal interest_amount
        decimal emi_amount
        enum payment_status
        date paid_date
        bigint transaction_id FK
        timestamp created_at
    }
    
    loan_collaterals {
        bigint id PK
        bigint loan_id FK
        enum collateral_type
        text description
        decimal valuation_amount
        date valuation_date
        enum status
        timestamp created_at
    }
    
    loan_guarantors {
        bigint id PK
        bigint loan_id FK
        bigint guarantor_customer_id FK
        enum guarantor_type
        decimal guarantee_amount
        date guarantee_date
        enum status
        timestamp created_at
    }
    
    %% ========================================
    %% CARD MANAGEMENT
    %% ========================================
    
    card_products ||--o{ cards : "type_of"
    cards ||--o{ card_transactions : "makes"
    cards ||--o{ card_emi_conversions : "converts_to"
    
    card_products {
        bigint id PK
        varchar product_code UK
        varchar product_name
        enum card_type
        enum card_network
        decimal annual_fee
        decimal credit_limit_min
        decimal credit_limit_max
        decimal interest_rate
        boolean is_active
        timestamp created_at
    }
    
    cards {
        bigint id PK
        varchar card_number UK
        bigint card_product_id FK
        bigint account_id FK
        bigint customer_id FK
        varchar cardholder_name
        enum card_type
        enum card_status
        date issue_date
        date expiry_date
        decimal credit_limit
        decimal outstanding_amount
        decimal reward_points
        timestamp created_at
    }
    
    card_transactions {
        bigint id PK
        varchar transaction_ref UK
        bigint card_id FK
        enum transaction_type
        varchar merchant_name
        decimal amount
        varchar currency_code
        timestamp transaction_date
        enum transaction_status
        boolean is_international
        timestamp created_at
    }
    
    card_emi_conversions {
        bigint id PK
        bigint card_id FK
        bigint card_transaction_id FK
        decimal principal_amount
        decimal interest_rate
        int tenure_months
        decimal emi_amount
        enum status
        timestamp created_at
    }
    
    %% ========================================
    %% DEPOSITS (FD & RD)
    %% ========================================
    
    deposit_products ||--o{ fixed_deposits : "type_of"
    deposit_products ||--o{ recurring_deposits : "type_of"
    recurring_deposits ||--o{ rd_installment_schedule : "has"
    
    deposit_products {
        bigint id PK
        varchar product_code UK
        varchar product_name
        enum product_type
        decimal min_amount
        decimal interest_rate
        int min_tenure_days
        int max_tenure_days
        boolean is_active
        timestamp created_at
    }
    
    fixed_deposits {
        bigint id PK
        varchar fd_account_number UK
        bigint deposit_product_id FK
        bigint customer_id FK
        bigint linked_account_id FK
        bigint branch_id FK
        decimal principal_amount
        decimal interest_rate
        int tenure_days
        decimal maturity_amount
        date deposit_date
        date maturity_date
        enum fd_status
        timestamp created_at
    }
    
    recurring_deposits {
        bigint id PK
        varchar rd_account_number UK
        bigint deposit_product_id FK
        bigint customer_id FK
        bigint linked_account_id FK
        bigint branch_id FK
        decimal monthly_installment
        decimal interest_rate
        int tenure_months
        decimal maturity_amount
        enum rd_status
        timestamp created_at
    }
    
    rd_installment_schedule {
        bigint id PK
        bigint rd_id FK
        int installment_number
        date due_date
        decimal installment_amount
        enum payment_status
        bigint transaction_id FK
        timestamp created_at
    }
    
    %% ========================================
    %% INVESTMENTS
    %% ========================================
    
    investment_products ||--o{ customer_investments : "invested_in"
    customer_investments ||--o{ investment_transactions : "has"
    customer_investments ||--o{ sip_mandates : "automates"
    
    investment_products {
        bigint id PK
        varchar product_code UK
        varchar product_name
        enum product_type
        varchar category
        enum risk_level
        decimal min_investment
        decimal current_nav
        boolean is_active
        timestamp created_at
    }
    
    customer_investments {
        bigint id PK
        varchar folio_number UK
        bigint customer_id FK
        bigint investment_product_id FK
        bigint account_id FK
        enum investment_type
        decimal units
        decimal invested_amount
        decimal current_value
        enum status
        timestamp created_at
    }
    
    investment_transactions {
        bigint id PK
        varchar transaction_ref UK
        bigint investment_id FK
        enum transaction_type
        date transaction_date
        decimal units
        decimal nav
        decimal amount
        enum status
        timestamp created_at
    }
    
    sip_mandates {
        bigint id PK
        varchar mandate_number UK
        bigint investment_id FK
        bigint account_id FK
        decimal sip_amount
        enum frequency
        date start_date
        date next_sip_date
        enum status
        timestamp created_at
    }
    
    %% ========================================
    %% INSURANCE
    %% ========================================
    
    insurance_products ||--o{ customer_insurance_policies : "type_of"
    customer_insurance_policies ||--o{ insurance_claims : "files"
    
    insurance_products {
        bigint id PK
        varchar product_code UK
        varchar product_name
        enum insurance_type
        varchar insurer_name
        decimal min_sum_assured
        decimal max_sum_assured
        boolean is_active
        timestamp created_at
    }
    
    customer_insurance_policies {
        bigint id PK
        varchar policy_number UK
        bigint customer_id FK
        bigint insurance_product_id FK
        bigint account_id FK
        decimal sum_assured
        decimal premium_amount
        date policy_start_date
        date policy_end_date
        enum policy_status
        timestamp created_at
    }
    
    insurance_claims {
        bigint id PK
        varchar claim_number UK
        bigint policy_id FK
        enum claim_type
        decimal claim_amount
        date claim_date
        enum claim_status
        timestamp created_at
    }
    
    %% ========================================
    %% COMPLIANCE & AUDIT
    %% ========================================
    
    aml_alerts ||--o{ suspicious_transaction_reports : "escalates_to"
    
    audit_logs {
        bigint id PK
        bigint user_id FK
        varchar action
        varchar entity_type
        bigint entity_id
        json old_values
        json new_values
        varchar ip_address
        timestamp performed_at
    }
    
    aml_alerts {
        bigint id PK
        varchar alert_number UK
        enum alert_type
        enum severity
        bigint customer_id FK
        bigint transaction_id FK
        enum alert_status
        text description
        timestamp created_at
    }
    
    suspicious_transaction_reports {
        bigint id PK
        varchar str_number UK
        bigint customer_id FK
        bigint transaction_id FK
        enum report_type
        text suspicious_activity_description
        enum report_status
        date submission_date
        timestamp created_at
    }
    
    sanctions_screening_list {
        bigint id PK
        varchar list_name
        enum entity_type
        varchar full_name
        json aliases
        date date_of_birth
        boolean is_active
        timestamp created_at
    }
    
    %% ========================================
    %% NOTIFICATIONS & FOREX
    %% ========================================
    
    notification_templates ||--o{ notifications : "uses"
    
    notifications {
        bigint id PK
        bigint user_id FK
        enum notification_type
        enum channel
        enum priority
        text message
        enum status
        timestamp sent_at
        timestamp created_at
    }
    
    notification_preferences {
        bigint id PK
        bigint user_id FK UK
        boolean transaction_alerts_sms
        boolean login_alerts
        boolean promotional_notifications
        timestamp created_at
    }
    
    currency_rates {
        bigint id PK
        varchar from_currency
        varchar to_currency
        decimal exchange_rate
        decimal buy_rate
        decimal sell_rate
        date rate_date
        timestamp created_at
    }
    
    forex_transactions {
        bigint id PK
        varchar transaction_ref UK
        bigint customer_id FK
        bigint account_id FK
        varchar from_currency
        varchar to_currency
        decimal from_amount
        decimal to_amount
        decimal exchange_rate
        enum status
        timestamp created_at
    }
    
    %% ========================================
    %% SYSTEM CONFIGURATION
    %% ========================================
    
    system_settings {
        bigint id PK
        varchar setting_key UK
        text setting_value
        enum setting_type
        varchar category
        boolean is_encrypted
        timestamp created_at
    }
    
    api_keys {
        bigint id PK
        varchar service_name
        varchar api_key_hash
        enum environment
        boolean is_active
        timestamp expires_at
        timestamp created_at
    }
```

### Key Relationships Summary

#### Customer Domain
- **1 User → 1 Customer** (One-to-One)
- **1 Customer → Many Accounts** (One-to-Many)
- **1 Customer → Many Loans** (One-to-Many)
- **1 Customer → Many Cards** (One-to-Many)
- **1 Customer → Many Deposits** (One-to-Many)

#### Account Domain
- **1 Account Type → Many Accounts** (One-to-Many)
- **1 Account → Many Transactions** (One-to-Many)
- **1 Account → Many Beneficiaries** (One-to-Many)
- **1 Account → Many Joint Holders** (One-to-Many)

#### Loan Domain
- **1 Loan Product → Many Loans** (One-to-Many)
- **1 Loan → Many EMI Schedules** (One-to-Many)
- **1 Loan → Many Collaterals** (One-to-Many)
- **1 Loan → Many Guarantors** (One-to-Many)

#### Transaction Domain
- **1 Transaction → Many Approvals** (One-to-Many for Maker-Checker)
- **1 Transaction → 0..1 AML Alert** (One-to-One Optional)
- **Many Transactions → 1 Account** (Many-to-One bidirectional)

#### Organizational Domain
- **1 Branch → Many Staff** (One-to-Many)
- **1 Branch → Many Accounts** (One-to-Many)
- **1 Branch → Many ATMs** (One-to-Many)

#### Security & Compliance
- **1 User → Many Roles** (Many-to-Many via user_roles)
- **1 Role → Many Permissions** (Many-to-Many via role_permissions)
- **1 Customer → Many AML Alerts** (One-to-Many)
- **1 AML Alert → 0..1 STR** (One-to-One Optional)

---

## 🎨 ER Diagram Design Principles

### Normalization
- **3rd Normal Form (3NF)** achieved across all tables
- No redundant data storage
- Proper use of foreign keys and indexes

### Audit Trail
- `created_at` and `updated_at` timestamps on all tables
- `deleted_at` for soft deletes (customers, accounts)
- Comprehensive `audit_logs` table for all critical operations

### Data Integrity
- **Foreign Key Constraints** ensure referential integrity
- **Check Constraints** via ENUM types for valid values
- **Unique Constraints** on business keys (customer_id, account_number, etc.)
- **Cascading Rules** properly configured (CASCADE, SET NULL, RESTRICT)

### Performance Optimization
- Strategic indexes on:
  - Primary keys (auto-created)
  - Foreign keys (all relationships)
  - Frequently queried columns (status, dates, types)
  - Composite indexes for complex queries
- Materialized views for reporting (`v_customer_summary`, `v_loan_portfolio`, etc.)

### Security
- Sensitive data hashing (passwords, CVV, PINs)
- Encrypted fields for PII data
- Separate tables for authentication tokens
- IP whitelisting for privileged operations

### Scalability
- Partitioning-ready design (by date ranges for transactions)
- Read replicas support (no auto-increment dependencies in business logic)
- Efficient JSON columns for flexible metadata
- Queue tables for asynchronous processing

```
