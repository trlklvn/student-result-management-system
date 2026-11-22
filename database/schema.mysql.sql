-- Student Result Management System: MySQL 8 schema
CREATE DATABASE IF NOT EXISTS srms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE srms;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          ENUM('admin','lecturer','student') NOT NULL,
    created_at    DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE students (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL UNIQUE,
    index_number VARCHAR(20)  NOT NULL UNIQUE,
    programme    VARCHAR(100) NOT NULL,
    level        SMALLINT     NOT NULL,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE courses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(15)  NOT NULL UNIQUE,
    title         VARCHAR(150) NOT NULL,
    credit_hours  TINYINT      NOT NULL,
    semester      TINYINT      NOT NULL,
    academic_year VARCHAR(9)   NOT NULL,
    lecturer_id   INT UNSIGNED NULL,
    CONSTRAINT fk_courses_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE enrollments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_enrollment (student_id, course_id),
    CONSTRAINT fk_enr_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_enr_course  FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE results (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL UNIQUE,
    ca_score      DECIMAL(5,2) NOT NULL,
    exam_score    DECIMAL(5,2) NOT NULL,
    total         DECIMAL(5,2) NOT NULL,
    grade         VARCHAR(2)   NOT NULL,
    grade_point   DECIMAL(3,1) NOT NULL,
    status        ENUM('draft','submitted','published') NOT NULL DEFAULT 'draft',
    updated_by    INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL,
    CONSTRAINT fk_res_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_ca   CHECK (ca_score BETWEEN 0 AND 40),
    CONSTRAINT chk_exam CHECK (exam_score BETWEEN 0 AND 60)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(50)  NOT NULL,
    details    VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_results_status ON results(status);
CREATE INDEX idx_courses_lecturer ON courses(lecturer_id);
