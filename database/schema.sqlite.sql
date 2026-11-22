-- SQLite version of the same schema (used for tests and quick demos without MySQL)
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin','lecturer','student')),
    created_at TEXT NOT NULL
);
CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    index_number TEXT NOT NULL UNIQUE,
    programme TEXT NOT NULL,
    level INTEGER NOT NULL
);
CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    credit_hours INTEGER NOT NULL,
    semester INTEGER NOT NULL,
    academic_year TEXT NOT NULL,
    lecturer_id INTEGER REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE (student_id, course_id)
);
CREATE TABLE results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    enrollment_id INTEGER NOT NULL UNIQUE REFERENCES enrollments(id) ON DELETE CASCADE,
    ca_score REAL NOT NULL CHECK (ca_score BETWEEN 0 AND 40),
    exam_score REAL NOT NULL CHECK (exam_score BETWEEN 0 AND 60),
    total REAL NOT NULL,
    grade TEXT NOT NULL,
    grade_point REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','submitted','published')),
    updated_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    details TEXT NOT NULL,
    created_at TEXT NOT NULL
);
CREATE INDEX idx_results_status ON results(status);
CREATE INDEX idx_courses_lecturer ON courses(lecturer_id);
