# Bomaveda Testing Protocol

**Version:** 1.0  
**Date:** 2026-11-17  
**Author:** Monicah Pirisi  

## 1. Purpose

This protocol defines how Bomaveda is tested before each release to ensure the platform is safe, functional, and usable for community members reporting concerns about youth radicalisation in Kilifi County.

## 2. Test Environments

| Environment | URL | Purpose |
|---|---|---|
| Local | `http://localhost:8080` | Developer testing (XAMPP or Docker) |
| Staging | `https://<DOMAIN>` (Docker on VPS) | Pre-release validation |
| Production | Same VPS, after staging sign-off | Live community use |

### Setup

```bash
# Local (Docker)
cp .env.example .env   # fill in secrets
docker compose up -d --build web db

# Staging/Production (VPS)
git pull && docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

## 3. Test Categories

### 3.1 Smoke Tests (run on every deploy)

| # | Test | Steps | Expected Result |
|---|---|---|---|
| S1 | Home page loads | Navigate to `/` | Hero section, nav bar, footer visible |
| S2 | Health check | `GET /api/health` | `{"ok": true, "status": "healthy"}` |
| S3 | Language toggle | Click "Kiswahili" in nav | All text switches to Kiswahili |
| S4 | Quick Exit | Click "Quick Exit" button | Redirects to google.com, session cleared |
| S5 | USSD simulator | Navigate to `/simulator`, press Call | USSD menu appears |

### 3.2 Intake Flow — Web Form (concern)

| # | Test | Steps | Expected Result |
|---|---|---|---|
| W1 | Full submission | Complete all 5 steps: ward, category, description (10+ chars), urgency, contact | 12-digit case code displayed |
| W2 | Validation — short description | Enter < 10 chars in description, submit | Error message shown, form not submitted |
| W3 | Validation — empty required fields | Skip category or ward, attempt submit | Browser validation prevents submission |
| W4 | Honeypot | Fill the hidden `website` field | Silently redirects to `/` (spam blocked) |
| W5 | Rate limit | Submit 11 times in 60 minutes | 11th submission shows "too many" error |
| W6 | Case lookup | Enter the code from W1 at `/case` | Shows status "submitted" |

### 3.3 Intake Flow — API

| # | Test | Method | Expected |
|---|---|---|---|
| A1 | Submit concern | `POST /api/concerns` with valid JSON | 201, case_code returned |
| A2 | Missing category | `POST /api/concerns` without category | 422, error message |
| A3 | Invalid ward | `POST /api/concerns` with wrong ward for constituency | 422, error message |
| A4 | Lookup case | `POST /api/cases/lookup` with code from A1 | 200, status = "submitted" |
| A5 | Lookup invalid | `POST /api/cases/lookup` with `"code": "000000000000"` | 404, "Case not found" |
| A6 | Submit support | `POST /api/support-requests` with valid JSON | 201, case_code returned |
| A7 | List resources | `GET /api/resources?lang=en` | 200, guides and emergency numbers |
| A8 | List wards | `GET /api/wards` | 200, all 7 constituencies with wards |
| A9 | List categories | `GET /api/categories?lang=en` | 200, 8 categories with labels |

### 3.4 Intake Flow — USSD Simulator

| # | Test | Steps | Expected |
|---|---|---|---|
| U1 | Full USSD flow | Dial → 1 (language) → 1 (report) → 1 (sub-county) → 1 (ward) → 1 (category) → "test description" | Case code displayed |
| U2 | Evidence upload | After U1, click Photo, select a JPEG | "Attached: filename.jpg" message |
| U3 | Skip evidence | After U1, click "Skip — finish without evidence" | Evidence panel hides |
| U4 | Check status via USSD | Dial → 1 → 2 → enter code from U1 | Status displayed |

### 3.5 Evidence Upload

| # | Test | Steps | Expected |
|---|---|---|---|
| E1 | Photo upload | Upload a .jpg < 10MB | Success message |
| E2 | Video upload | Upload a .mp4 < 50MB | Success message |
| E3 | Document upload | Upload a .pdf < 10MB | Success message |
| E4 | Wrong file type | Upload a .exe as "photo" | Error: "File type not allowed" |
| E5 | Oversized file | Upload a 60MB video | Error: "File too large" |
| E6 | API evidence | `POST /api/evidence` with multipart form | 201, success |

### 3.6 Coordinator Workspace

| # | Test | Steps | Expected |
|---|---|---|---|
| C1 | Login | Navigate to `/auth`, enter credentials | Redirected to `/admin` |
| C2 | View case | Click a case code in the list | Case details shown (description decrypted) |
| C3 | Update status | Change status dropdown, click Update | Status saved, audit log entry created |
| C4 | Send message | Type a message, click Send | Message appears in case view |
| C5 | Reveal contact | Click "Show contact" | Contact displayed, audit log entry created |
| C6 | Request referral | Select agency, enter reason, submit | Referral appears in Referrals tab |
| C7 | Approve referral | Different user approves the referral | Status changes to "approved" |
| C8 | Analytics dashboard | Navigate to Overview tab | KPI cards, charts, breakdown table displayed |
| C9 | Manage opportunities | Add an opportunity, toggle active/hidden | Opportunity appears/hides on Resources page |
| C10 | Audit log | View Audit log tab | Shows all recent actions with timestamps |

### 3.7 Security Tests

| # | Test | Steps | Expected |
|---|---|---|---|
| X1 | XSS in description | Submit `<script>alert(1)</script>` as description | Stored encrypted, displayed escaped, no alert |
| X2 | CSRF protection | POST to `/admin` without valid csrf token | 403 Forbidden |
| X3 | Direct admin access | Navigate to `/admin` without login | Redirected to `/auth` |
| X4 | CSP header | Check response headers | `default-src 'self'; frame-ancestors 'none'` |
| X5 | SQL injection | Enter `' OR 1=1 --` in case lookup | "Case not found" (parameterised query) |
| X6 | Path traversal | Try to access `/storage/evidence/../../etc/passwd` | 404 (storage not web-accessible) |

### 3.8 Responsiveness

| # | Test | Device/Width | Expected |
|---|---|---|---|
| R1 | Mobile nav | 375px width | Nav scrolls horizontally, no overlap |
| R2 | Tablet layout | 768px width | Cards in 2 columns, nav fits in one line |
| R3 | Desktop layout | 1440px width | Full-width layout, all nav items visible |
| R4 | USSD simulator mobile | 375px width | Phone and info cards stack vertically |

## 4. Data Separation

- **Narrative** (description): Stored encrypted in `cases.description` via libsodium.
- **Contact info**: Stored encrypted in `contacts.value_enc`, linked to case by `case_id`.
- **Location**: Constituency and ward stored in `cases.constituency` and `cases.ward` (plain text for routing). Village/landmark prepended to encrypted description.
- All three are linked only by the 12-digit case code, which the reporter controls.

## 5. Test Execution

### Before Each Release
1. Run all smoke tests (S1–S5)
2. Run one full intake flow (W1 or A1)
3. Run one evidence upload test (E1)
4. Run one coordinator test (C1–C2)
5. Check responsiveness on mobile (R1)

### Before Major Releases
1. Run all tests in sections 3.1–3.8
2. Record results in the Beta Feedback Template
3. Get sign-off from at least one team member

## 6. Bug Reporting

Report bugs with:
- **Test ID** (e.g., W2)
- **Steps to reproduce**
- **Expected vs. actual result**
- **Screenshot or error log** (from Docker: `docker compose logs web`)
- **Browser and device**

## 7. Approval

| Role | Name | Date | Signature |
|---|---|---|---|
| Developer | Monicah Pirisi | 2026-11-17 | _____________ |
| Reviewer | _____________ | _____________ | _____________ |
