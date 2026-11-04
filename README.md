# SafeVoice — School Complaint & Student Protection System
Procedural PHP 8+ · **Procedural MySQLi** (no PDO, no classes) · MySQL · Bootstrap 5 · Vanilla JS · Chart.js

## Installation (XAMPP)
1. Start **Apache** and **MySQL** in XAMPP.
2. Copy the `SafeVoice` folder to `C:\xampp\htdocs\`.
3. phpMyAdmin → **Import** `database/schema.sql`, then `database/seed.sql`.
4. Check DB credentials at the top of `config/app.php` (default: root / blank).
5. Make sure PHP extensions `fileinfo` and `mysqli` are enabled (default in XAMPP).
6. Open http://localhost/SafeVoice/

## Development accounts
| Role | Email | Password |
|---|---|---|
| Super Admin (default) | admin@gmail.com | 12345678 |
| Investigator | officer1@safevoice.local | Officer@12345 |
| Student | sign up at /auth/register.php | any password with 8+ characters |

Everyone goes straight to their dashboard after login (no forced "set new password" step).
Passwords can be changed any time from Profile Settings → Change Password.

## Upgrading an existing database
Do NOT import `schema.sql` on a database you want to keep (it recreates every table).
Import `database/migration.sql` instead. It drops `audit_logs` and `login_attempts`, removes the
`users.must_change_password` column (only if it still exists), and adds the default admin only if missing.
All users, complaints, messages, evidence and history are kept. Fresh installs: `schema.sql`, then `seed.sql`.

## Project layout
Root contains only `index.php`, `.htaccess` and `README.md`. Folders: `config/` `includes/` `auth/` `public/` `student/` `officer/` `admin/`
`actions/` `middleware/` `assets/` `uploads/` `database/`. The protected file endpoints live in `actions/`:
`actions/profile-image.php?id=` (profile pictures) and `actions/evidence-file.php?id=` (evidence).

## Authentication
- Unlimited login attempts; a wrong password just shows "Invalid email or password." (no lockout, no attempt table).
- Passwords are stored with `password_hash()` and checked with `password_verify()`; all SQL uses prepared statements; CSRF on every POST.
- Login/Signup are standalone pages with the logo inside the card (clickable: visitors → homepage, logged-in users → their dashboard).
- Signup: phone required, password 8+ characters only, one validation message at a time, success message does not show the Student ID.
- Flash messages fade out after 3 seconds.

## Quick demo flow
1. Login as **admin@gmail.com / 12345678** → set a new password → *Investigation Officers* → note officer1 → *Category Assignments* → assign categories (e.g. Bullying) to officer1.
2. Register a **student** → *Submit Complaint* (category Bullying) → it routes automatically to officer1.
3. Login as **officer1** (private window) → *Assigned Complaints* → open the case → Investigation → send a message / request more info / change status.
4. Back as **student**: notifications bell, messages show "SafeVoice Investigation Team", progress timeline updates. The officer's identity is never shown.
5. As **admin**: Complaints → open a complaint → see real sender names, reassign with a reason, view reports.

## Project notes
- One config file only: `config/app.php` (settings + `mysqli_connect`).
- All SQL uses prepared statements through helpers in `includes/functions.php` (`db_one/db_all/db_value/db_exec/db_insert`).
- Role always comes from the database. Public registration only creates Students.
- Student-facing queries (`student_get_complaint`, student messages, progress) never select officer columns.
- Profile pictures and evidence are served only through the protected endpoints in `actions/`. `uploads/` is blocked by `.htaccess`.
- No audit-log or login-attempt features exist. Only Students have notifications. Admin/Investigator layouts contain none.
- `actions/update-user-status.php` handles activate/deactivate/suspend for Students and Investigators (replaces `update-officer-status.php`).
- Video evidence is intentionally not enabled (safety/storage requirements); allowed: JPG, PNG, WEBP, PDF, DOC, DOCX, TXT.
- An officer keeps access to a complaint while it is assigned to them, even if the category assignment later changes. Admin moves complaints through controlled reassignment (reason required, history kept).

## Test checklist (see the master prompt section 30)
Auth: register (phone required, 8-char password, one error at a time)/login/logout, duplicate email, repeated wrong logins never lock the account · Roles: student opening /admin/ is redirected ·
Accounts: create/edit/deactivate/delete officer (delete refuses when open complaints/categories/history exist) ·
Categories: one officer per category (UNIQUE), multi-category officers, unassigned complaints stay Unassigned ·
Complaints: multiple complaints, school/roll/class/gender saved per complaint, evidence upload ·
Messaging: student sees "SafeVoice Investigation Team"; admin sees real sender ·
Pictures: upload/change/remove for all three roles; admin manages student & officer pictures; student cannot fetch officer picture (default avatar returned) ·
Files: wrong extension / MIME / oversize rejected; CSRF on every POST.

## UI refresh (design layer)
- `assets/css/safevoice-ui.css` is the shared design system (tokens, buttons, forms, tables, cards, headers, sidebar, footers, messaging). It loads after `style.css` on the public, student, officer and admin layouts. **Login and signup do not load it and are unchanged.**
- Messages (student and officer) now use a two-pane layout: searchable conversation list with last-message preview, time and unread count; chat pane with date separators, left/right bubbles and a composer (`assets/js/messages.js`: Enter to send, Shift+Enter for a new line). Messaging is still server-rendered through `actions/send-message.php`; students only ever see "SafeVoice Investigation Team".
- `includes/public-content.php` holds the public-site content (steps, categories, features, FAQs, safety tips) used by both the public homepage and the student dashboard ("About SafeVoice" section, categories read from the database).
- No database changes are required for this update.


## Student navigation & UI update (Home as landing page)

- **Home is the Student landing page.** After login (and after any "go to my area" redirect) Students are sent to `index.php`, which shows the same public Home page used by visitors. Only the header differs: a logged-in Student sees *Home, My Complaints, Submit Complaint, Messages* plus the notification bell and an account menu (Profile, Profile Settings, Change Password, Help & Safety, Logout). Visitors keep the public header with Get Started / Login. Administrators and Investigators are still forwarded to their own dashboards.
- `dashboard_path_for_role()` (includes/functions.php) now returns `index.php` for Students. `student/dashboard.php` is still in the project but is no longer linked or used as a landing page.
- The Student header lives in **one** file, `includes/student-nav.php`, used by both `includes/student-header.php` (all Student pages) and `includes/navbar.php` (Home page).
- **Submit Complaint** (`student/submit-complaint.php`, `assets/js/submit-complaint.js`) was redesigned with sectioned cards, an evidence drag-and-drop area with file list, a character counter, a guidance sidebar and a clear error summary. All field names/ids, the form action, CSRF and the backend are unchanged.
- **Messages** (`student/messages.php`, `assets/js/messages.js`) gained a neutral team avatar, readable timestamps, visible sending / sent / error states and a fix for the mobile "back to conversations" button. Students only ever see "SafeVoice Investigation Team"; investigator data is never selected in Student-facing queries.
- No database change and no migration are needed for this update.
