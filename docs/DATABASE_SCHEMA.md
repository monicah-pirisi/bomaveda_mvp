# Bomaveda Database Schema Documentation

**Database:** MariaDB 11.8 (LTS)  
**Charset:** utf8mb4  
**Version:** 2026-11-17  

## Entity Relationship Diagram

```
┌─────────────┐    1:0..1    ┌──────────────┐
│   cases      │─────────────│   contacts    │
│              │             │              │
│ id (PK)      │             │ case_id (PK,FK)│
│ code (UQ)    │             │ method        │
│ kind         │             │ value_enc     │
│ category     │             │ safe_to_call  │
│ constituency │             └──────────────┘
│ ward         │
│ description  │    1:0..N    ┌──────────────┐
│ urgency      │─────────────│   messages    │
│ status       │             │              │
│ created_at   │             │ id (PK)       │
│ updated_at   │             │ case_id (FK)  │
└──────┬───────┘             │ author        │
       │                     │ body_enc      │
       │  1:0..N             │ created_at    │
       │                     └──────────────┘
       │
       ├─────────────────────┐
       │  1:0..N             │  1:0..N
       ▼                     ▼
┌──────────────┐      ┌──────────────┐
│   evidence    │      │  escalations  │
│              │      │              │
│ id (PK)      │      │ id (PK)       │
│ case_id (FK) │      │ case_id (FK)  │
│ type         │      │ agency        │
│ original_name│      │ reason_enc    │
│ stored_name  │      │ status        │
│ mime_type    │      │ requested_by  │
│ size         │      │ decided_by    │
│ uploaded_at  │      │ reference     │
└──────────────┘      │ created_at    │
                      │ updated_at    │
                      └──────────────┘

┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│    staff      │      │  audit_log    │      │   mentors     │
│              │      │              │      │              │
│ id (PK)      │      │ id (PK)       │      │ id (PK)       │
│ username (UQ)│      │ staff_id      │      │ name          │
│ pass_hash    │      │ action        │      │ focus         │
│ role         │      │ case_id       │      │ constituency  │
│ constituency │      │ note          │      │ contact_enc   │
│ totp_enc     │      │ at            │      │ active        │
│ last_totp    │      └──────────────┘      └──────────────┘
│ active       │
│ created_at   │      ┌──────────────┐      ┌──────────────┐
└──────────────┘      │opportunities  │      │   throttle    │
                      │              │      │  (MEMORY)     │
                      │ id (PK)       │      │              │
                      │ type          │      │ h (IP hash)   │
                      │ title         │      │ b (bucket)    │
                      │ title_sw      │      │ at            │
                      │ description   │      └──────────────┘
                      │ description_sw│
                      │ provider      │
                      │ location      │
                      │ deadline      │
                      │ link          │
                      │ active        │
                      │ created_by    │
                      │ created_at    │
                      │ updated_at    │
                      └──────────────┘
```

## Tables

### cases
Core intake table. Every concern and support request lands here.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Internal ID |
| code | CHAR(12) | UNIQUE, NOT NULL | 12-digit case code (e.g., 1234-5678-9012) |
| kind | ENUM('concern','support') | NOT NULL, DEFAULT 'concern' | Type of intake |
| category | VARCHAR(40) | NOT NULL | Concern category or support type |
| constituency | VARCHAR(40) | NOT NULL | Sub-county |
| ward | VARCHAR(40) | NOT NULL | Ward within constituency |
| description | TEXT | NOT NULL | **Encrypted** (libsodium) narrative |
| urgency | ENUM('low','moderate','urgent') | NOT NULL, DEFAULT 'moderate' | Urgency level |
| status | ENUM(7 values) | NOT NULL, DEFAULT 'submitted' | Case progression |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Submission time |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Status progression:** submitted → under_review → verified → intervention_assigned → active_support → monitoring → resolved

### contacts
Optional reporter contact info, encrypted at rest.

| Column | Type | Constraints | Description |
|---|---|---|---|
| case_id | BIGINT UNSIGNED | PK, FK → cases.id ON DELETE CASCADE | One contact per case |
| method | ENUM('phone','email') | NOT NULL | Contact method |
| value_enc | TEXT | NOT NULL | **Encrypted** phone or email |
| safe_to_call | TINYINT(1) | NOT NULL, DEFAULT 0 | Reporter consents to being called |

### messages
Staff messages readable by the reporter using their case code.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Message ID |
| case_id | BIGINT UNSIGNED | FK → cases.id ON DELETE CASCADE | Parent case |
| author | ENUM('staff','reporter') | NOT NULL | Who wrote it |
| body_enc | TEXT | NOT NULL | **Encrypted** message body |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Sent time |

### evidence
Files attached to cases (photos, videos, voice notes, documents).

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Evidence ID |
| case_id | BIGINT UNSIGNED | FK → cases.id ON DELETE CASCADE | Parent case |
| type | ENUM('photo','video','voice','document') | NOT NULL | File category |
| original_name | VARCHAR(255) | NOT NULL | Uploader's filename |
| stored_name | VARCHAR(80) | NOT NULL | Server filename (random hex) |
| mime_type | VARCHAR(100) | NOT NULL | Detected MIME type |
| size | INT UNSIGNED | NOT NULL, DEFAULT 0 | File size in bytes |
| uploaded_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Upload time |

### escalations
Referrals to external agencies with dual-approval workflow.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Referral ID |
| case_id | BIGINT UNSIGNED | FK → cases.id ON DELETE CASCADE | Parent case |
| agency | VARCHAR(60) | NOT NULL | Target agency name |
| reason_enc | TEXT | NOT NULL | **Encrypted** reason for referral |
| status | ENUM('requested','approved','rejected','referred') | DEFAULT 'requested' | Approval state |
| requested_by | INT UNSIGNED | NOT NULL | Staff who requested |
| decided_by | INT UNSIGNED | NULL | Staff who approved/rejected |
| reference | VARCHAR(80) | NULL | External reference number |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Request time |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

### staff
Coordinator accounts. Created via CLI only (`bin/create-staff.php`), no public sign-up.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Staff ID |
| username | VARCHAR(40) | UNIQUE, NOT NULL | Login username |
| pass_hash | VARCHAR(255) | NOT NULL | Argon2id password hash |
| role | ENUM('admin','coordinator','liaison') | NOT NULL | Access level |
| constituency | VARCHAR(40) | NULL | Liaison's assigned sub-county |
| totp_enc | TEXT | NOT NULL | **Encrypted** TOTP secret |
| last_totp | BIGINT | NOT NULL, DEFAULT 0 | Replay protection |
| active | TINYINT(1) | NOT NULL, DEFAULT 1 | Account enabled |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Account creation |

### audit_log
Every staff action recorded for accountability. No IP addresses stored.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Log entry ID |
| staff_id | INT UNSIGNED | NULL | Acting staff member |
| action | VARCHAR(40) | NOT NULL | Action type (login, status, reveal_contact, etc.) |
| case_id | BIGINT UNSIGNED | NULL | Affected case, if any |
| note | VARCHAR(200) | NULL | Additional context |
| at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Action time |

### mentors
Pool of community mentors available for assignment.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Mentor ID |
| name | VARCHAR(80) | NOT NULL | Full name |
| focus | VARCHAR(80) | NOT NULL | Specialisation (counselling, etc.) |
| constituency | VARCHAR(40) | NOT NULL | Coverage area |
| contact_enc | TEXT | NULL | **Encrypted** contact info |
| active | TINYINT(1) | NOT NULL, DEFAULT 1 | Currently available |

### opportunities
Youth opportunities managed by coordinators, displayed on the public Resources page.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Opportunity ID |
| type | ENUM('job','skill','scholarship','bursary') | NOT NULL | Category |
| title | VARCHAR(120) | NOT NULL | English title |
| title_sw | VARCHAR(120) | DEFAULT '' | Kiswahili title |
| description | TEXT | NOT NULL | English description |
| description_sw | TEXT | DEFAULT '' | Kiswahili description |
| provider | VARCHAR(120) | DEFAULT '' | Organisation offering it |
| location | VARCHAR(80) | DEFAULT '' | Location |
| deadline | DATE | NULL | Application deadline |
| link | VARCHAR(500) | DEFAULT '' | External link |
| active | TINYINT(1) | NOT NULL, DEFAULT 1 | Visible on site |
| created_by | INT UNSIGNED | NULL | Staff who added it |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Added time |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

### throttle
In-memory rate limiting. Wiped on restart (ENGINE=MEMORY).

| Column | Type | Description |
|---|---|---|
| h | CHAR(64) | HMAC-SHA256 of IP + daily salt |
| b | VARCHAR(12) | Rate-limit bucket (submit, lookup) |
| at | DATETIME | Request timestamp |

## Encryption

All sensitive fields are encrypted using **libsodium XSalsa20-Poly1305** (authenticated encryption):

- `cases.description` — concern/support narrative
- `contacts.value_enc` — phone number or email
- `messages.body_enc` — staff-to-reporter messages
- `escalations.reason_enc` — referral justification
- `mentors.contact_enc` — mentor phone/email
- `staff.totp_enc` — authenticator secret

The encryption key (`APP_KEY`) is a 256-bit key stored as a base64-encoded environment variable, never committed to version control.

## Indexes

| Table | Index | Columns | Purpose |
|---|---|---|---|
| cases | PRIMARY | id | Row lookup |
| cases | code | code (UNIQUE) | Case lookup by 12-digit code |
| cases | idx_status | status | Filter cases by status |
| evidence | idx_case | case_id | List evidence for a case |
| opportunities | idx_active_type | active, type | Filter active opportunities by type |
| throttle | (h, b) | h, b | Rate limit lookups |
