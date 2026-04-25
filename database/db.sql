CREATE DATABASE jobapplication;
USE jobapplication;

-- Applicant
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phoneNo VARCHAR(20),
    password VARCHAR(255),
    resume VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Company Admin / HR
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    fullname VARCHAR(100)
);

-- Job Vacancy
CREATE TABLE jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    jobtitle VARCHAR(100),
    position VARCHAR(100),
    salary DECIMAL(10,2),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

-- Job Application
CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    job_id INT,
    admin_id INT,
    status ENUM('Pending','Accepted','Rejected') DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
);

-- Notification
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- carrer history
CREATE TABLE career_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_name VARCHAR(255),
    position VARCHAR(255),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- education
CREATE TABLE education (
    education_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    institution_name VARCHAR(255),
    degree VARCHAR(255),
    field_of_study VARCHAR(255),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Interview
-- CREATE TABLE interviews (
--     interview_id INT AUTO_INCREMENT PRIMARY KEY,
--     application_id INT,
--     interview_date DATETIME,
--     location VARCHAR(255),
--     notes TEXT,
--     FOREIGN KEY (application_id) REFERENCES applications(application_id)
-- );