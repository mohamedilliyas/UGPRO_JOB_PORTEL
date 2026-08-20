-- =======================================================
-- UgPro University Job & Career Portal - Complete Database Schema
-- Database Name: vavuniyauniversity
-- =======================================================

CREATE DATABASE IF NOT EXISTS `vavuniyauniversity` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vavuniyauniversity`;

-- Drop existing tables in reverse dependency order if recreating
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `saved_jobs`;
DROP TABLE IF EXISTS `job_applications`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_categories`;
DROP TABLE IF EXISTS `undergraduate`;
DROP TABLE IF EXISTS `employer`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `contact_messages`;
SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================
-- 1. Table: undergraduate (Students / Job Seekers)
-- =======================================================
CREATE TABLE `undergraduate` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `reg_no` VARCHAR(100) DEFAULT NULL,
    `faculty` VARCHAR(255) DEFAULT 'Faculty of Applied Science',
    `course` VARCHAR(255) DEFAULT NULL,
    `graduation_year` INT(4) DEFAULT 2025,
    `phone` VARCHAR(50) DEFAULT NULL,
    `skills` TEXT DEFAULT NULL,
    `projects` TEXT DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `github` VARCHAR(255) DEFAULT NULL,
    `linkedin` VARCHAR(255) DEFAULT NULL,
    `portfolio_url` VARCHAR(255) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `resume_file` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 2. Table: employer (Companies / Recruiters)
-- =======================================================
CREATE TABLE `employer` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `company_logo` VARCHAR(255) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT 'Colombo, Sri Lanka',
    `industry` VARCHAR(255) DEFAULT 'Information Technology',
    `about` TEXT DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('active', 'pending', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 3. Table: admins (University Coordinators / System Admins)
-- =======================================================
CREATE TABLE `admins` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'admin',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 4. Table: job_categories (Job Industry / Categories)
-- =======================================================
CREATE TABLE `job_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(100) DEFAULT 'bi-briefcase',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 5. Table: jobs (Job Listings posted by Employers)
-- =======================================================
CREATE TABLE `jobs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employer_id` INT(11) NOT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `job_type` ENUM('Full Time', 'Part Time', 'Internship', 'Freelancer', 'Contract') NOT NULL DEFAULT 'Full Time',
    `workplace_type` ENUM('On-site', 'Remote', 'Hybrid') NOT NULL DEFAULT 'On-site',
    `location` VARCHAR(255) NOT NULL DEFAULT 'Colombo, Sri Lanka',
    `salary_range` VARCHAR(100) DEFAULT 'Negotiable',
    `vacancy_count` INT(11) DEFAULT 1,
    `working_hours` VARCHAR(100) DEFAULT '40h / week',
    `experience_level` VARCHAR(100) DEFAULT 'Entry Level / Undergraduate',
    `education_req` VARCHAR(255) DEFAULT 'Bachelor\'s Degree in Computer Science / IT or related field',
    `description` TEXT NOT NULL,
    `responsibilities` TEXT DEFAULT NULL,
    `requirements` TEXT DEFAULT NULL,
    `benefits` TEXT DEFAULT NULL,
    `deadline` DATE DEFAULT NULL,
    `status` ENUM('active', 'closed', 'draft') DEFAULT 'active',
    `views_count` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_jobs_employer` FOREIGN KEY (`employer_id`) REFERENCES `employer` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jobs_category` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 6. Table: job_applications (Applications from Students)
-- =======================================================
CREATE TABLE `job_applications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `job_id` INT(11) NOT NULL,
    `undergraduate_id` INT(11) NOT NULL,
    `cover_letter` TEXT DEFAULT NULL,
    `resume_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending', 'reviewing', 'shortlisted', 'interviewed', 'accepted', 'rejected') DEFAULT 'pending',
    `employer_notes` TEXT DEFAULT NULL,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_job_undergrad` (`job_id`, `undergraduate_id`),
    CONSTRAINT `fk_app_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_app_undergrad` FOREIGN KEY (`undergraduate_id`) REFERENCES `undergraduate` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 7. Table: saved_jobs (Bookmarks)
-- =======================================================
CREATE TABLE `saved_jobs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `undergraduate_id` INT(11) NOT NULL,
    `job_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_saved_job` (`undergraduate_id`, `job_id`),
    CONSTRAINT `fk_saved_undergrad` FOREIGN KEY (`undergraduate_id`) REFERENCES `undergraduate` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saved_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 8. Table: contact_messages (Contact Form Submissions)
-- =======================================================
CREATE TABLE `contact_messages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) DEFAULT 'General Inquiry',
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- SEED DATA (Categories, Sample Accounts & Jobs)
-- =======================================================

-- 1. Insert Default Job Categories
INSERT INTO `job_categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Software Engineering', 'software-engineering', 'bi-code-slash'),
(2, 'Web Development', 'web-development', 'bi-globe'),
(3, 'UI/UX & Graphic Design', 'design', 'bi-palette'),
(4, 'Data Science & AI', 'data-science', 'bi-cpu'),
(5, 'Cybersecurity', 'cybersecurity', 'bi-shield-check'),
(6, 'Business & Marketing', 'business-marketing', 'bi-graph-up-arrow'),
(7, 'Mobile App Development', 'mobile-app', 'bi-phone'),
(8, 'Quality Assurance (QA)', 'qa-testing', 'bi-check2-circle');

-- 2. Insert Default Administrator (Password: admin123)
INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@ugpro.lk', '$2y$10$fuzTKOKUXYh4A0cCGm/tYefBIt5nF7xEn/62OL2PXSYYL0GhUsnK6', 'superadmin')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`), `password`=VALUES(`password`);

-- 3. Insert Demo Employers (Password: employer123 for all demo employers)
INSERT INTO `employer` (`id`, `company_name`, `email`, `password`, `company_logo`, `website`, `location`, `industry`, `about`, `phone`) VALUES
(1, 'Virtusa Sri Lanka', 'careers@virtusa.com', '$2y$10$s3bsdmDM7srwijQw7AOD6u.lcm9OxfzvB.hxQNUzPXF23VhbIQVHi', 'images/google.png', 'https://www.virtusa.com', 'Colombo, Sri Lanka', 'Information Technology', 'Virtusa Corporation is an American information technology services company providing digital engineering and business consulting.', '+94 11 249 4000'),
(2, 'WSO2', 'recruitment@wso2.com', '$2y$10$s3bsdmDM7srwijQw7AOD6u.lcm9OxfzvB.hxQNUzPXF23VhbIQVHi', 'images/uber.png', 'https://wso2.com', 'Colombo 03, Sri Lanka', 'Open Source Software', 'WSO2 is an open-source technology provider offering API management, integration, and identity & access management.', '+94 11 743 5800'),
(3, 'IFS R&D Sri Lanka', 'careers@ifs.com', '$2y$10$s3bsdmDM7srwijQw7AOD6u.lcm9OxfzvB.hxQNUzPXF23VhbIQVHi', 'images/facebook.png', 'https://www.ifs.com', 'Colombo 04, Sri Lanka', 'Enterprise Software', 'IFS develops and delivers enterprise software for customers around the world who manufacture and distribute goods.', '+94 11 236 4400'),
(4, 'Creative Software', 'hr@creativesoftware.com', '$2y$10$s3bsdmDM7srwijQw7AOD6u.lcm9OxfzvB.hxQNUzPXF23VhbIQVHi', 'images/linkedin.png', 'https://www.creativesoftware.com', 'Colombo 07, Sri Lanka', 'Software Outsourcing', 'Pioneering international software development company collaborating with world-leading European and US tech innovators.', '+94 11 250 8733')
ON DUPLICATE KEY UPDATE `company_name`=VALUES(`company_name`), `password`=VALUES(`password`);

-- 4. Insert Demo Undergraduates (Password: student123 for all demo students)
INSERT INTO `undergraduate` (`id`, `full_name`, `email`, `password`, `reg_no`, `faculty`, `course`, `graduation_year`, `phone`, `skills`, `projects`, `bio`, `github`, `linkedin`, `portfolio_url`, `profile_image`, `status`) VALUES
(1, 'Mohamed Illiyas', 'illiyas@vau.ac.lk', '$2y$10$/ugmhBfdOwEvCe7Nl2ykw.1yvY2QqhxMg/s661DPVcVzCw8kzv3pC', '2020/ICT/42', 'Faculty of Applied Science', 'Information and Communication Technology (BICT)', 2025, '+94 77 123 4567', 'PHP, MySQL, JavaScript, React, Laravel, Tailwind CSS, Bootstrap', 'UgPro University Job Portal, E-Commerce Bookstore, Smart Attendance System', 'Passionate final-year undergraduate focused on full-stack web engineering and scalable distributed systems.', 'https://github.com/mohamedilliyas', 'https://linkedin.com/in/mohamedilliyas', 'https://illiyas.dev', 'images/fl-3.png', 'active'),
(2, 'Ayesha Perera', 'ayesha.p@vau.ac.lk', '$2y$10$/ugmhBfdOwEvCe7Nl2ykw.1yvY2QqhxMg/s661DPVcVzCw8kzv3pC', '2021/CS/18', 'Faculty of Applied Science', 'Computer Science (BSc Hons)', 2025, '+94 71 987 6543', 'Python, Machine Learning, TensorFlow, Django, SQL, Data Visualization', 'Plant Disease Detection AI, University Sentiment Analyzer, Financial Forecast Model', 'Enthusiastic computer science student with a strong passion for data science and AI applications.', 'https://github.com/ayeshap', 'https://linkedin.com/in/ayeshaperera', 'https://ayesha.me', 'images/fl-2.png', 'active'),
(3, 'Kavindu Fernando', 'kavindu.f@vau.ac.lk', '$2y$10$/ugmhBfdOwEvCe7Nl2ykw.1yvY2QqhxMg/s661DPVcVzCw8kzv3pC', '2020/ICT/88', 'Faculty of Business Studies', 'Business Information Systems', 2024, '+94 76 555 1234', 'UI/UX Design, Figma, Adobe XD, HTML/CSS, Agile Project Management, SEO', 'HealthCare Mobile App Design, Banking Dashboard UX Case Study', 'Aspiring product designer and frontend enthusiast who crafts user-centered, accessible interfaces.', 'https://github.com/kavindu', 'https://linkedin.com/in/kavindufernando', 'https://kavindu.design', 'images/fl-1.png', 'active')
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`), `password`=VALUES(`password`);

-- 5. Insert Realistic Sample Jobs
INSERT INTO `jobs` (`id`, `employer_id`, `category_id`, `title`, `job_type`, `workplace_type`, `location`, `salary_range`, `vacancy_count`, `working_hours`, `experience_level`, `education_req`, `description`, `responsibilities`, `requirements`, `benefits`, `deadline`, `status`) VALUES
(1, 1, 2, 'Associate Software Engineer - Web', 'Full Time', 'Hybrid', 'Colombo, Sri Lanka', 'LKR 120,000 - 180,000 / month', 3, '40h / week', 'Entry Level / Fresh Graduate', 'BSc in Computer Science, IT, Software Engineering or equivalent', 'We are looking for enthusiastic and driven Associate Web Engineers to join our global engineering team in Colombo. You will work on cutting-edge enterprise cloud platforms and contribute to modern microservice architectures.', '• Develop responsive, high-performance web applications using modern web stacks.\n• Collaborate with senior architects and cross-functional teams in agile sprints.\n• Write clean, maintainable, and well-tested code.\n• Participate in code reviews and active technical discussions.', '• Solid foundation in OOP, algorithms, and data structures.\n• Proficiency in JavaScript, PHP, Python, or Java.\n• Knowledge of relational databases (MySQL/PostgreSQL).\n• Strong communication and analytical problem-solving skills.', '• Comprehensive health and life insurance.\n• Flexible hybrid working model.\n• Continuous learning allowances & certification support.\n• Annual performance bonuses.', '2026-10-31', 'active'),

(2, 2, 1, 'Software Engineering Intern - Cloud & APIs', 'Internship', 'Remote', 'Colombo / Remote, Sri Lanka', 'LKR 50,000 - 75,000 / month (Stipend)', 5, '40h / week', 'Undergraduate (Final / 3rd Year)', 'Currently pursuing a degree in CS, SE, or IT', 'Join WSO2 as a Software Engineering Intern and gain hands-on experience developing industry-standard open-source identity, integration, and cloud-native solutions utilized by thousands of enterprises worldwide.', '• Implement features and bug fixes across open-source middleware components.\n• Build sample applications and write developer-focused technical guides.\n• Work alongside mentor engineers on distributed systems and API architectures.', '• Strong proficiency in Java, Go, or Node.js.\n• Familiarity with Git, Linux environments, and RESTful APIs.\n• Proactive mindset and eagerness to learn open-source tools.', '• Dedicated 1-on-1 industry mentorship.\n• Opportunity for full-time absorption upon graduation.\n• High-spec developer laptop and home workstation allowance.', '2026-09-30', 'active'),

(3, 3, 3, 'Junior UI/UX Designer', 'Full Time', 'On-site', 'Colombo 04, Sri Lanka', 'LKR 90,000 - 140,000 / month', 2, '40h / week', 'Entry Level / 1 Year Experience', 'Degree/Diploma in Design, Human-Computer Interaction, or related field', 'IFS is hiring a Junior UI/UX Designer to craft world-class enterprise user experiences. You will transform complex business workflows into intuitive, visually stunning interfaces.', '• Create wireframes, interactive prototypes, and high-fidelity mockups in Figma.\n• Conduct user research, usability testing, and heuristic evaluations.\n• Maintain and evolve our enterprise design system components.', '• Strong portfolio demonstrating web/mobile UX case studies.\n• Proficiency in Figma, Illustrator, or Adobe XD.\n• Understanding of responsive design principles and design systems.', '• Global exposure with Swedish multinational culture.\n• On-site gym, cafeteria, and recreational zones.\n• Flexible working hours.', '2026-11-15', 'active'),

(4, 4, 4, 'Trainee Data Analyst / Python Developer', 'Full Time', 'Hybrid', 'Colombo 07, Sri Lanka', 'LKR 80,000 - 120,000 / month', 2, '40h / week', 'Fresh Graduate / Undergraduate', 'BSc in Computer Science, Data Science, Statistics, or Mathematics', 'Creative Software is expanding its Data Solutions team. We seek ambitious graduates with a strong passion for data pipelines, predictive analytics, and automated reporting.', '• Extract, clean, and analyze large datasets from varied SQL and NoSQL sources.\n• Build interactive dashboards in Power BI and automated Python scripts.\n• Assist in building predictive models and statistical analyses.', '• Strong knowledge of Python (Pandas, NumPy, Scikit-learn).\n• Proficiency in SQL querying and data warehousing concepts.\n• Excellent data storytelling and presentation skills.', '• Modern collaborative workspace in Colombo 07.\n• Regular international hackathons and tech talks.\n• Medical insurance package.', '2026-10-15', 'active'),

(5, 1, 1, 'Freelance Full Stack Developer', 'Freelancer', 'Remote', 'Remote (Sri Lanka)', '$15 - $25 / hour', 1, '20h / week', '1+ Year Experience', 'Undergraduate or Graduate in IT/CS', 'Looking for an experienced student or freelancer to collaborate on building a university research repository and student talent platform.', '• Build secure REST APIs and frontend components.\n• Optimize database queries and setup automated deployment.', '• Experience with PHP/Laravel or Node.js + React.\n• Good communication and delivery discipline.', '• Hourly milestone payouts.\n• 100% remote flexibility.', '2026-12-31', 'active')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 6. Insert Sample Application
INSERT INTO `job_applications` (`id`, `job_id`, `undergraduate_id`, `cover_letter`, `resume_path`, `status`, `employer_notes`) VALUES
(1, 1, 1, 'I am excited to apply for the Associate Software Engineer position. With a solid foundation in web development, database architecture, and agile methodologies from the University of Vavuniya, I am eager to contribute to Virtusa.', 'uploads/resumes/demo_resume.pdf', 'shortlisted', 'Strong portfolio and relevant web project experience. Candidate shortlisted for initial technical interview.')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);

-- 7. Insert Sample Saved Job
INSERT INTO `saved_jobs` (`id`, `undergraduate_id`, `job_id`) VALUES
(1, 1, 2)
ON DUPLICATE KEY UPDATE `job_id`=VALUES(`job_id`);

-- 8. Insert Sample Contact Messages
INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `status`) VALUES
(1, 'University Career Guidance Unit', 'cgu@vau.ac.lk', 'Partnership with UgPro', 'We would like to coordinate university placement drives through the UgPro Job Portal for the upcoming graduation batch.', 'read')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);
