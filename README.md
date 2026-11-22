# Student Result Management System (SRMS)

A web application for recording, approving and publishing student examination results.
Built with **PHP 8 + MySQL** using a layered **MVC** architecture, with no framework dependencies.

- **Administrators** register students and lecturers, create courses, enrol students and publish results.
- **Lecturers** enter continuous assessment (CA /40) and exam (/60) scores for their own courses, then submit them for approval.
- **Students** see only published results, with semester GPA, CGPA and class of degree.

Results follow a controlled life cycle: `draft → submitted → published`. The administrator can return submitted results to the lecturer, and every change is written to an audit log.

## Run it with XAMPP (MySQL)

1. Copy this folder into `C:\xampp\htdocs\srms`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Edit `config/config.php` if your MySQL user or password is different (XAMPP default: `root`, no password).
4. Create the database and demo data, using **either** method:
   - Command line: `C:\xampp\php\php.exe database\install.php`
   - phpMyAdmin: **Import** → `database/srms_full.sql`
5. Open <http://localhost/srms/public/>.

## Run it without MySQL (SQLite, quick demo)

```bash
DB_DRIVER=sqlite php database/install.php
DB_DRIVER=sqlite php -S localhost:8000 -t public
```

## Demo accounts

| Role     | Username     | Password     |
|----------|--------------|--------------|
| Admin    | `admin`      | `admin123`   |
| Lecturer | `k.mensah`   | `lecturer123`|
| Lecturer | `a.owusu`    | `lecturer123`|
| Student  | `UEB3201021` | `student123` |

Demo data: 6 students, 2 lecturers, 5 courses. Semester 1 results are published, CSC 202 is waiting for approval, and CSC 204 is still a draft with one score missing.

## Tests

```bash
php tests/run.php              # all 26 tests
php tests/run.php unit         # 8 unit tests
php tests/run.php integration  # 9 integration tests (real schema, in-memory SQLite)
php tests/run.php system       # 9 end-to-end HTTP tests (starts its own web server)
```

## Project structure

```
config/        configuration (database driver, score limits)
database/      MySQL + SQLite schema, installer and demo data
public/        front controller (index.php) and CSS: the only web-visible folder
src/Core/      Router, Controller base, Auth, Database (singleton)
src/Models/    Repositories: all SQL lives here
src/Services/  ResultService (business rules), GradingScheme (strategy)
src/Controllers/ Auth, Admin, Lecturer, Student controllers
views/         PHP templates
tests/         Unit / Integration / System tests and runner
docs/          Project report and presentation
```

## Grading scale

| Score  | Grade | Point |
|--------|-------|-------|
| 80–100 | A     | 4.0   |
| 75–79  | B+    | 3.5   |
| 70–74  | B     | 3.0   |
| 65–69  | C+    | 2.5   |
| 60–64  | C     | 2.0   |
| 55–59  | D+    | 1.5   |
| 50–54  | D     | 1.0   |
| 0–49   | F     | 0.0   |

GPA = Σ(grade point × credit hours) ÷ Σ credit hours.
CGPA classes: First Class ≥ 3.6, Second Upper ≥ 3.0, Second Lower ≥ 2.5, Third ≥ 2.0, Pass ≥ 1.0.

## Security

Passwords are hashed with `password_hash` (bcrypt). All queries use prepared statements. Output is escaped against XSS, every form carries a CSRF token, the session ID is regenerated at login, and access is checked by role on each route.
