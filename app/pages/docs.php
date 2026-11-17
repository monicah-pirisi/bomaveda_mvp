<?php
page('Bomaveda API Documentation', function () {
  echo '<h1>Bomaveda API Documentation</h1>';
  echo '<p class="lead">RESTful JSON API for community safety reporting in Kilifi County, Kenya. All endpoints accept and return JSON unless noted otherwise.</p>';

  echo '<section class="band white"><div class="wrap">';
  echo '<h2>Base URL</h2>';
  echo '<pre><code>https://&lt;your-domain&gt;/api</code></pre>';
  echo '<p>All requests must use HTTPS in production. Responses follow the format:</p>';
  echo '<pre><code>{
  "ok": true,
  ... response data ...
}

// or on error:
{
  "ok": false,
  "error": "Human-readable error message"
}</code></pre>';

  echo '<h2>Authentication</h2>';
  echo '<p>Public endpoints (concern submission, support requests, case lookup) require no authentication. They are rate-limited by IP address.</p>';

  echo '<hr>';
  echo '<h2>Endpoints</h2>';

  // --- Health ---
  echo '<div class="card"><h3>GET /api/health</h3>';
  echo '<p>Health check. Returns database connection status.</p>';
  echo '<p><strong>Response 200:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "status": "healthy",
  "database": "connected",
  "timestamp": "2026-11-17T10:30:00+03:00"
}</code></pre></div>';

  // --- Submit Concern ---
  echo '<div class="card"><h3>POST /api/concerns</h3>';
  echo '<p>Submit a community safety concern. This is the core intake endpoint.</p>';
  echo '<p><strong>Request body (JSON):</strong></p>';
  echo '<pre><code>{
  "category": "sudden_isolation",
  "constituency": "Kilifi North",
  "ward": "Tezo",
  "description": "Description of the concern (10-2000 chars)",
  "urgency": "moderate",
  "village": "Optional village name",
  "landmark": "Optional landmark",
  "contact_method": "phone",
  "contact_value": "0712345678",
  "safe_to_call": true
}</code></pre>';
  echo '<p><strong>Required fields:</strong> category, constituency, ward, description</p>';
  echo '<p><strong>Optional fields:</strong> urgency (default: moderate), village, landmark, contact_method, contact_value, safe_to_call</p>';
  echo '<table class="tbl"><thead><tr><th>Field</th><th>Type</th><th>Values</th></tr></thead><tbody>';
  echo '<tr><td>category</td><td>string</td><td>sudden_isolation, harmful_online_influence, school_dropout_risk, emotional_distress, violent_rhetoric, recruitment_concerns, substance_abuse, family_community_conflict</td></tr>';
  echo '<tr><td>urgency</td><td>string</td><td>low, moderate, urgent</td></tr>';
  echo '<tr><td>constituency</td><td>string</td><td>Kilifi North, Kilifi South, Kaloleni, Rabai, Ganze, Malindi, Magarini</td></tr>';
  echo '<tr><td>contact_method</td><td>string</td><td>phone, email</td></tr>';
  echo '</tbody></table>';
  echo '<p><strong>Response 201:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "case_code": "1234-5678-9012",
  "message": "Concern submitted successfully. Save your case code."
}</code></pre>';
  echo '<p><strong>Error codes:</strong> 400 (bad JSON), 422 (validation), 429 (rate limit), 500 (server error)</p>';
  echo '</div>';

  // --- Support Request ---
  echo '<div class="card"><h3>POST /api/support-requests</h3>';
  echo '<p>Request mentorship or counselling support.</p>';
  echo '<p><strong>Request body (JSON):</strong></p>';
  echo '<pre><code>{
  "type": "mentorship",
  "constituency": "Kilifi North",
  "ward": "Tezo",
  "message": "Optional details (max 1000 chars)",
  "contact_method": "phone",
  "contact_value": "0712345678",
  "safe_to_call": false
}</code></pre>';
  echo '<p><strong>Required fields:</strong> type, constituency, ward</p>';
  echo '<table class="tbl"><thead><tr><th>Field</th><th>Type</th><th>Values</th></tr></thead><tbody>';
  echo '<tr><td>type</td><td>string</td><td>mentorship, counselling</td></tr>';
  echo '</tbody></table>';
  echo '<p><strong>Response 201:</strong> Same format as POST /api/concerns</p>';
  echo '</div>';

  // --- Case Lookup ---
  echo '<div class="card"><h3>POST /api/cases/lookup</h3>';
  echo '<p>Check the status of a submitted case using its 12-digit code. POST is used instead of GET to keep the code out of server logs and browser history.</p>';
  echo '<p><strong>Request body (JSON):</strong></p>';
  echo '<pre><code>{
  "code": "1234-5678-9012"
}</code></pre>';
  echo '<p><strong>Response 200:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "status": "under_review",
  "status_label": "Under review",
  "progress": "2/7",
  "updated_at": "2026-11-17 10:30:00",
  "messages": [
    {
      "message": "We have received your report and assigned a liaison.",
      "date": "2026-11-17 11:00:00"
    }
  ]
}</code></pre>';
  echo '<p><strong>Status progression:</strong> submitted → under_review → verified → intervention_assigned → active_support → monitoring → resolved</p>';
  echo '</div>';

  // --- Evidence Upload ---
  echo '<div class="card"><h3>POST /api/evidence</h3>';
  echo '<p>Attach evidence to an existing case. Uses <code>multipart/form-data</code> (not JSON).</p>';
  echo '<p><strong>Form fields:</strong></p>';
  echo '<table class="tbl"><thead><tr><th>Field</th><th>Type</th><th>Description</th></tr></thead><tbody>';
  echo '<tr><td>code</td><td>string</td><td>12-digit case code</td></tr>';
  echo '<tr><td>type</td><td>string</td><td>photo, video, voice, or document</td></tr>';
  echo '<tr><td>file</td><td>file</td><td>The evidence file</td></tr>';
  echo '</tbody></table>';
  echo '<p><strong>File limits:</strong></p>';
  echo '<table class="tbl"><thead><tr><th>Type</th><th>Accepted formats</th><th>Max size</th></tr></thead><tbody>';
  echo '<tr><td>photo</td><td>JPEG, PNG, WebP</td><td>10 MB</td></tr>';
  echo '<tr><td>video</td><td>MP4, WebM, MOV</td><td>50 MB</td></tr>';
  echo '<tr><td>voice</td><td>MP3, OGG, WAV, WebM, M4A</td><td>20 MB</td></tr>';
  echo '<tr><td>document</td><td>PDF, Word (.doc/.docx), plain text</td><td>10 MB</td></tr>';
  echo '</tbody></table>';
  echo '<p><strong>Response 201:</strong></p>';
  echo '<pre><code>{"ok": true, "message": "Evidence attached successfully"}</code></pre>';
  echo '</div>';

  // --- Resources ---
  echo '<div class="card"><h3>GET /api/resources?lang=en</h3>';
  echo '<p>Retrieve safety guides and emergency contacts.</p>';
  echo '<p><strong>Query parameters:</strong> lang (en or sw, default: en)</p>';
  echo '<p><strong>Response 200:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "language": "en",
  "guides": [
    {"title": "Early warning signs", "body": "Pulling away from family..."}
  ],
  "emergency": [
    {"number": "999 / 112", "label": "Emergency"}
  ]
}</code></pre></div>';

  // --- Opportunities ---
  echo '<div class="card"><h3>GET /api/opportunities?lang=en&amp;type=job</h3>';
  echo '<p>List active opportunities (jobs, skills training, scholarships, bursaries).</p>';
  echo '<p><strong>Query parameters:</strong> lang (en/sw), type (job/skill/scholarship/bursary, optional filter)</p>';
  echo '<p><strong>Response 200:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "opportunities": [
    {
      "id": 1,
      "type": "job",
      "title": "Community youth worker",
      "description": "...",
      "provider": "County Government",
      "location": "Kilifi Town",
      "deadline": "2026-12-31",
      "link": "https://..."
    }
  ]
}</code></pre></div>';

  // --- Wards ---
  echo '<div class="card"><h3>GET /api/wards</h3>';
  echo '<p>List all constituencies and their wards. Useful for populating intake form dropdowns.</p>';
  echo '<p><strong>Response 200:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "constituencies": {
    "Kilifi North": ["Tezo", "Sokoni", "Kibarani", ...],
    "Kilifi South": ["Junju", "Mwarakaya", ...],
    ...
  }
}</code></pre></div>';

  // --- Categories ---
  echo '<div class="card"><h3>GET /api/categories?lang=en</h3>';
  echo '<p>List concern categories with localised labels.</p>';
  echo '<p><strong>Response 200:</strong></p>';
  echo '<pre><code>{
  "ok": true,
  "categories": [
    {"key": "sudden_isolation", "label": "Sudden isolation"},
    {"key": "violent_rhetoric", "label": "Violent rhetoric"},
    ...
  ]
}</code></pre></div>';

  echo '<hr>';
  echo '<h2>Rate Limits</h2>';
  echo '<table class="tbl"><thead><tr><th>Action</th><th>Limit</th><th>Window</th></tr></thead><tbody>';
  echo '<tr><td>Submit concern / support request</td><td>10 requests</td><td>60 minutes per IP</td></tr>';
  echo '<tr><td>Case lookup</td><td>5 requests</td><td>15 minutes per IP</td></tr>';
  echo '</tbody></table>';
  echo '<p>When rate-limited, the API returns HTTP 429.</p>';

  echo '<hr>';
  echo '<h2>Data Model</h2>';
  echo '<p>The core data entities and their relationships:</p>';
  echo '<div class="card"><h3>Entity Relationship</h3>';
  echo '<pre><code>cases (1) ──── (0..1) contacts       Case may have optional reporter contact
cases (1) ──── (0..N) messages       Staff can send messages readable by case code
cases (1) ──── (0..N) evidence       Files attached to a case after submission
cases (1) ──── (0..N) escalations    Referrals to external agencies (dual-approval)
staff  ──── audit_log                All staff actions logged for accountability
mentors                              Pool of mentors by constituency and focus area
opportunities                        Jobs, skills, scholarships, bursaries</code></pre></div>';

  echo '<h3>Database Schema Summary</h3>';
  echo '<table class="tbl"><thead><tr><th>Table</th><th>Purpose</th><th>Key Fields</th></tr></thead><tbody>';
  echo '<tr><td>cases</td><td>Concerns and support requests</td><td>code (12-digit unique), kind, category, constituency, ward, description (encrypted), urgency, status</td></tr>';
  echo '<tr><td>contacts</td><td>Optional reporter contact info</td><td>case_id (FK), method, value_enc (encrypted), safe_to_call</td></tr>';
  echo '<tr><td>messages</td><td>Staff-to-reporter messages</td><td>case_id (FK), author, body_enc (encrypted)</td></tr>';
  echo '<tr><td>evidence</td><td>Uploaded files linked to cases</td><td>case_id (FK), type, original_name, stored_name, mime_type, size</td></tr>';
  echo '<tr><td>escalations</td><td>Referrals to external agencies</td><td>case_id (FK), agency, reason_enc, status (dual-approval workflow)</td></tr>';
  echo '<tr><td>staff</td><td>Coordinator accounts</td><td>username, role (admin/coordinator/liaison), constituency, TOTP 2FA</td></tr>';
  echo '<tr><td>audit_log</td><td>Accountability trail</td><td>staff_id, action, case_id, timestamp</td></tr>';
  echo '<tr><td>mentors</td><td>Community mentor pool</td><td>name, focus, constituency, contact_enc</td></tr>';
  echo '<tr><td>opportunities</td><td>Youth opportunities</td><td>type, title (EN+SW), description (EN+SW), provider, deadline</td></tr>';
  echo '<tr><td>throttle</td><td>Rate limiting (in-memory)</td><td>IP hash, bucket, timestamp</td></tr>';
  echo '</tbody></table>';

  echo '<h3>Security Design</h3>';
  echo '<ul>';
  echo '<li><strong>Encryption at rest:</strong> All personal data (descriptions, contacts, messages, referral reasons) encrypted with libsodium (XSalsa20-Poly1305), keyed by APP_KEY.</li>';
  echo '<li><strong>No PII in URLs:</strong> Case lookup uses POST to keep codes out of server logs and browser history.</li>';
  echo '<li><strong>IP hashing:</strong> Rate limits use HMAC-SHA256 of IP + daily salt. No IP addresses stored in the database.</li>';
  echo '<li><strong>Dual-approval referrals:</strong> One staff member requests, a different one must approve before data leaves the system.</li>';
  echo '<li><strong>Audit log:</strong> Every staff action recorded (view, status change, message, referral, contact reveal).</li>';
  echo '<li><strong>CSP:</strong> <code>default-src \'self\'; frame-ancestors \'none\'</code> — no external scripts, no framing.</li>';
  echo '<li><strong>2FA:</strong> TOTP authenticator required for all staff accounts (can be disabled only on localhost for development).</li>';
  echo '</ul>';
  echo '</div></section>';
}, false, false, 'home');
