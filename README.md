# Leadforgrow HRM

Laravel-based Human Resource Management system for **Leadforgrow** — employees, attendance, leaves, payroll helpers, projects, chat, tickets, assets, expenses, and compliance workflows in one app.

**App URL (local):** `http://localhost/hrmpulse-laravel/public`

---

## Stack

| Layer | Technology |
|--------|------------|
| Framework | Laravel 11 (PHP 8.2+) |
| Database | MySQL (`hrm_*` tables) |
| Auth | Session auth against `hrm_employee` |
| UI | Blade + Bootstrap 5 + Font Awesome |
| Mail | PHPMailer via in-app SMTP settings (`HrmMailer`) |
| PDF | barryvdh/laravel-dompdf |
| Chat calls | WebRTC (STUN/TURN configurable in `.env`) |

---

## Features

### People & organization
- Employee master (profile, family, education, photo)
- Departments & designations
- Reporting managers (primary / secondary)
- Roles (`user`, `admin`, `super admin`)
- Archived employees

### Daily work
- Punch in / out attendance + admin upload / reports
- Leave apply & admin approval
- Holidays calendar
- Company policies & company documents

### Project management
- Projects with PM, team assign / reassign
- **Project tasks** (assign, status flow, notes; completed locks for employees)
- **Daily work notes** (time range, status, progress %)
- Activity timeline & assignment history
- Side-by-side task / notes panels with internal scroll

### Workplace
- Expenses (employee + admin)
- Tickets (raise / manage / categories)
- Chat room (1:1 + groups) and WebRTC calls
- Asset inventory & assignments
- Resignation + notice-period steps
- POSH guidelines / committee + harassment complaints

### Communication
- Announcements (email + **in-app bell / My Notifications**)
- Employee of the Month
- Searchable employee picker (name / department / designation)
- Notification sound toggle

### Admin / developer
- Salary & advance salary helpers
- Analytics dashboard
- Branding, email, greeting, leave settings
- Cron / celebration mail tools
- API tokens (where enabled)

---

## Roles (high level)

| Role | Access |
|------|--------|
| **User** | Own dashboard, leaves, attendance, projects (assigned), expenses, tickets, chat, profile |
| **Admin / HR** | People ops, attendance admin, leaves admin, announcements, tickets, resignations, assets, salary tools |
| **Super Admin** | Admin plus developer tools, roles, deeper settings |

Authorization uses employee helpers (`isAdmin()`, `isSuperAdmin()`, policies) — not Spatie roles.

---

## Requirements

- PHP **8.2+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (recommended)
- Composer 2.x
- MySQL 5.7+ / 8.x (or MariaDB)
- Apache/Nginx **or** XAMPP (project lives under `htdocs`)

---

## Setup

### 1. Clone / place project

```text
C:\xampp\htdocs\hrmpulse-laravel
```

### 2. Install dependencies

```bash
cd C:\xampp\htdocs\hrmpulse-laravel
composer install
```

### 3. Environment

```bash
copy .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
APP_NAME="Leadforgrow HRM"
APP_URL=http://localhost/hrmpulse-laravel/public
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hrm_database
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database

Point `DB_DATABASE` at your existing HRM database (legacy `hrm_*` schema), then run any pending Laravel migrations:

```bash
php artisan migrate
```

Check status:

```text
/migration-status
```

(while logged in)

### 5. Storage link (uploads / photos)

```bash
php artisan storage:link
```

### 6. Run

**XAMPP:** start Apache + MySQL, open:

```text
http://localhost/hrmpulse-laravel/public/login
```

**Or PHP built-in server:**

```bash
php artisan serve
```

Then open `http://127.0.0.1:8000`.

---

## Important config notes

- **Login** uses `hrm_employee` (office email / credentials as implemented in `LoginController`).
- **Mail** is driven by DB email settings + `App\Services\HrmMailer` (not only Laravel `MAIL_*`).
- **In-app notifications** table: `hrm_employee_notifications` (bell + `/my-notifications`).
- **Employee search API:** `/lookups/employees`, `/lookups/employee-filters`.
- **Themes:** light / dark / dark-blue (per user).
- **WebRTC** (chat calls): see `WEBRTC_*` keys in `.env.example`.

---

## Project layout (useful paths)

```text
app/
  Http/Controllers/     # Feature controllers (Employee, Leave, Project, Chat, …)
  Models/               # Eloquent models mapped to hrm_* tables
  Policies/             # Project / task authorization
  Services/             # HrmMailer, ProjectService, EmployeeNotificationService, …
resources/views/        # Blade UI (layouts, dashboards, modules)
routes/web.php          # All web routes
public/                 # Web root (css/js assets, index.php)
database/migrations/    # Incremental schema for new modules
```

---

## Common URLs

| Page | Path |
|------|------|
| Login | `/login` |
| Employee dashboard | `/dashboard` |
| Admin dashboard | `/admin/dashboard` |
| Projects | `/projects` |
| My notifications | `/my-notifications` |
| Announcements | `/activities` |
| Chat | `/chat` |
| Attendance (self) | `/attendance` |

---

## Development tips

- Prefer `php artisan migrate` for new tables; do not overwrite production HRM data casually.
- Clear caches after config changes:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

- Rich text fields use Summernote (`js-rich-editor`).
- Employee selects use `x-employee-select` / `.js-employee-select` (search + department + designation filters).

---

## License

Internal Leadforgrow HRM application. Framework portions follow Laravel’s MIT license; application code and branding belong to the project owner.
