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

-- Demo data
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('1', 'admin', '$2y$10$YIvDg43u.21FjlHyhlgHLeqPuFAGftZhwXBhBAdA070akpumBvDra', 'Registry Administrator', 'admin', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('2', 'k.mensah', '$2y$10$cQgD/uMLz72F8LIEP0rAP.GpDXbF0oQQdm.ffDid1bbC.bRB0IEaq', 'Dr. Kwame Mensah', 'lecturer', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('3', 'a.owusu', '$2y$10$mvy49OUW0zdNml596INt9O7Eb/cFvCoC3qy.s9x5XF4ybP2FDjLka', 'Mrs. Abena Owusu', 'lecturer', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('4', 'UEB3201021', '$2y$10$JJKBrtEKqnCdBsQo6ylrG.EZ6FkY1wOPzLK27WJbp7xVJTsA5FMh.', 'Ama Serwaa Boateng', 'student', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('5', 'UEB3201022', '$2y$10$7ccGSNxDlkpCZ9Jlv.sC1ePlqj5h3Rji52fvyvvPgETg/hJCMNTIS', 'Kofi Asante', 'student', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('6', 'UEB3201023', '$2y$10$gbNJ3xXL7CvpX7o5//ooh.IuJzJ7QzDd8A7ROEYB3puDWXJw6YsaW', 'Esi Nyarko', 'student', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('7', 'UEB3201024', '$2y$10$LvNahllxULKrAnR0xAibhesQN650y0WTU3sByLdFBovx56jQf1OjS', 'Yaw Darko', 'student', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('8', 'UEB3201025', '$2y$10$ahEIPbBkKRk.wtDA70DEquHnMHAJGpKSTBBnnsV9yzs1WL/xK6HoK', 'Akosua Frimpong', 'student', '2026-11-22 21:25:41');
INSERT INTO users (id, username, password_hash, full_name, role, created_at) VALUES ('9', 'UEB3201026', '$2y$10$HXHiFGYlH4JIDJirXb4qxu745WuWGoMeLCc1y.xtKcn4xUfnr6i1a', 'Kwabena Ofori', 'student', '2026-11-22 21:25:41');
INSERT INTO students (id, user_id, index_number, programme, level) VALUES ('1', '4', 'UEB3201021', 'BSc Computer Science', '200');
INSERT INTO students (id, user_id, index_number, programme, level) VALUES ('2', '5', 'UEB3201022', 'BSc Computer Science', '200');
INSERT INTO students (id, user_id, index_number, programme, level) VALUES ('3', '6', 'UEB3201023', 'BSc Computer Science', '200');
INSERT INTO students (id, user_id, index_number, programme, level) VALUES ('4', '7', 'UEB3201024', 'BSc Computer Science', '200');
INSERT INTO students (id, user_id, index_number, programme, level) VALUES ('5', '8', 'UEB3201025', 'BSc Computer Science', '200');
INSERT INTO students (id, user_id, index_number, programme, level) VALUES ('6', '9', 'UEB3201026', 'BSc Computer Science', '200');
INSERT INTO courses (id, code, title, credit_hours, semester, academic_year, lecturer_id) VALUES ('1', 'CSC 201', 'Data Structures and Algorithms', '3', '1', '2025/2026', '2');
INSERT INTO courses (id, code, title, credit_hours, semester, academic_year, lecturer_id) VALUES ('2', 'CSC 203', 'Database Systems', '3', '1', '2025/2026', '3');
INSERT INTO courses (id, code, title, credit_hours, semester, academic_year, lecturer_id) VALUES ('3', 'MTH 201', 'Discrete Mathematics', '2', '1', '2025/2026', '3');
INSERT INTO courses (id, code, title, credit_hours, semester, academic_year, lecturer_id) VALUES ('4', 'CSC 202', 'Software Engineering', '3', '2', '2025/2026', '2');
INSERT INTO courses (id, code, title, credit_hours, semester, academic_year, lecturer_id) VALUES ('5', 'CSC 204', 'Web Development', '3', '2', '2025/2026', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('1', '1', '1');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('2', '2', '1');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('3', '3', '1');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('4', '4', '1');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('5', '5', '1');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('6', '6', '1');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('7', '1', '2');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('8', '2', '2');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('9', '3', '2');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('10', '4', '2');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('11', '5', '2');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('12', '6', '2');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('13', '1', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('14', '2', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('15', '3', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('16', '4', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('17', '5', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('18', '6', '3');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('19', '1', '4');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('20', '2', '4');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('21', '3', '4');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('22', '4', '4');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('23', '5', '4');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('24', '6', '4');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('25', '1', '5');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('26', '2', '5');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('27', '3', '5');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('28', '4', '5');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('29', '5', '5');
INSERT INTO enrollments (id, student_id, course_id) VALUES ('30', '6', '5');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('1', '1', '36', '52', '88', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('2', '2', '28', '41', '69', 'C+', '2.5', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('3', '3', '33', '47', '80', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('4', '4', '22', '30', '52', 'D', '1', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('5', '5', '38', '55', '93', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('6', '6', '25', '36', '61', 'C', '2', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('7', '7', '34', '48', '82', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('8', '8', '30', '39', '69', 'C+', '2.5', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('9', '9', '35', '50', '85', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('10', '10', '20', '27', '47', 'F', '0', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('11', '11', '37', '51', '88', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('12', '12', '27', '33', '60', 'C', '2', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('13', '13', '32', '45', '77', 'B+', '3.5', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('14', '14', '26', '35', '61', 'C', '2', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('15', '15', '30', '44', '74', 'B', '3', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('16', '16', '18', '25', '43', 'F', '0', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('17', '17', '36', '50', '86', 'A', '4', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('18', '18', '24', '31', '55', 'D+', '1.5', 'published', '1', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('19', '19', '35', '50', '85', 'A', '4', 'submitted', '2', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('20', '20', '29', '40', '69', 'C+', '2.5', 'submitted', '2', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('21', '21', '34', '46', '80', 'A', '4', 'submitted', '2', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('22', '22', '21', '29', '50', 'D', '1', 'submitted', '2', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('23', '23', '39', '56', '95', 'A', '4', 'submitted', '2', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('24', '24', '26', '35', '61', 'C', '2', 'submitted', '2', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('25', '25', '33', '49', '82', 'A', '4', 'draft', '3', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('26', '26', '27', '38', '65', 'C+', '2.5', 'draft', '3', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('27', '27', '31', '42', '73', 'B', '3', 'draft', '3', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('28', '28', '19', '28', '47', 'F', '0', 'draft', '3', '2026-11-22 21:25:42');
INSERT INTO results (id, enrollment_id, ca_score, exam_score, total, grade, grade_point, status, updated_by, updated_at) VALUES ('29', '29', '35', '53', '88', 'A', '4', 'draft', '3', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('1', '2', 'scores_saved', '6 result(s) saved for CSC 201', '2026-11-22 21:25:41');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('2', '3', 'scores_saved', '6 result(s) saved for CSC 203', '2026-11-22 21:25:41');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('3', '3', 'scores_saved', '6 result(s) saved for MTH 201', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('4', '2', 'scores_saved', '6 result(s) saved for CSC 202', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('5', '3', 'scores_saved', '5 result(s) saved for CSC 204', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('6', '2', 'results_submitted', '6 result(s) submitted for CSC 201', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('7', '1', 'results_published', '6 result(s) published for CSC 201', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('8', '3', 'results_submitted', '6 result(s) submitted for CSC 203', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('9', '1', 'results_published', '6 result(s) published for CSC 203', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('10', '3', 'results_submitted', '6 result(s) submitted for MTH 201', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('11', '1', 'results_published', '6 result(s) published for MTH 201', '2026-11-22 21:25:42');
INSERT INTO audit_log (id, user_id, action, details, created_at) VALUES ('12', '2', 'results_submitted', '6 result(s) submitted for CSC 202', '2026-11-22 21:25:42');
