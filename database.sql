-- Online Leerplatform Database Schema
-- Created for Course & Subscription Management System

-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'docent', 'student') DEFAULT 'student' NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    instructor VARCHAR(100) NOT NULL,
    teacher_id INT NULL,
    duration_hours INT DEFAULT 0,
    price DECIMAL(10, 2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscriptions table
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enrollments table (users enrolled in courses)
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default users (admin, docent, student)
-- Default password for all demo users: password (change after first login!)
INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES
('admin', 'admin@leerplatform.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin'),
('docent1', 'docent1@leerplatform.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jan', 'Jansen', 'docent'),
('student1', 'student1@leerplatform.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'Een', 'student');

-- Insert sample courses (teacher_id = 2 is docent1 - Jan Jansen)
INSERT INTO courses (title, description, instructor, teacher_id, duration_hours, price, is_active) VALUES
('PHP Fundamentals', 'Leer de basis van PHP programmeren', 'Jan Jansen', 2, 40, 299.00, TRUE),
('MySQL Database Design', 'Database ontwerp en optimalisatie', 'Maria de Vries', NULL, 30, 249.00, TRUE),
('Web Development Advanced', 'Geavanceerde web development technieken', 'Peter Bakker', NULL, 50, 399.00, TRUE),
('JavaScript Basics', 'Introductie tot JavaScript', 'Lisa van der Berg', NULL, 25, 199.00, TRUE);

-- Insert sample subscription (user_id 3 = student1)
INSERT INTO subscriptions (user_id, subscription_type, start_date, end_date, is_active) VALUES
(3, 'Premium', '2024-01-01', '2024-12-31', TRUE);

