---
title: UgPro Job Portal
emoji: 🎓
colorFrom: green
colorTo: blue
sdk: docker
app_port: 7860
pinned: false
---

# UgPro - University Career & Job Portal

> **A modern, full-featured University Job & Career Portal connecting undergraduates with top industry employers, verified internships, and placement opportunities.**

---

## 🌟 Features Overview

### 🎓 1. Undergraduate (Student) Portal
- **Smart Registration & Profile Hub**: Upload profile pictures, document academic faculty, degree, index/registration number, graduation year, skills tags, personal projects, GitHub, LinkedIn, and PDF resume.
- **Dynamic Job Discovery**: Search and filter by category, employment type (Full Time, Internship, Remote, Freelance), location, and salary.
- **One-Click Application**: Submit applications with customized cover letters and automatic profile resume attachments.
- **Live Application Tracking**: Real-time dashboard showing application status (`Pending`, `Under Review`, `Shortlisted`, `Interview Scheduled`, `Accepted`, `Rejected`) and employer feedback notes.
- **Job Bookmarks**: Save jobs for later review.

### 🏢 2. Employer (Recruiter / Company) Portal
- **Company Profile Management**: Upload company logos, website links, office location, industry sector, and about summary.
- **Job Posting & Management**: Post, update, toggle active/closed, and delete job vacancies with comprehensive specifications (vacancies, hours, experience level, salary range, requirements, benefits, deadline).
- **Applicant Tracking System (ATS)**: Filter candidates by vacancy, view student profiles and cover letters, view/download PDF resumes, and update recruitment status with custom interview/feedback notes.
- **Talent Discovery Pool**: Browse registered undergraduates filtered by discipline and technical skills.

### 🛡️ 3. University Administrator Control Center
- **System Metrics Overview**: Real-time KPI metrics for students, employers, active vacancies, and total submitted applications.
- **Job Moderation**: Audit, toggle status, or remove job listings.
- **User Governance**: Verify, activate, or suspend student and employer accounts.
- **Category Manager**: Add new job categories with custom icons.
- **Contact Inquiries**: Manage and respond to university placement queries.

---

## 📂 Project Structure

```
UGPRO_JOB_PORTEL/
├── admin/                     # University Admin Control Center
│   ├── index.php              # Admin Dashboard & Moderation
│   └── login.php              # Admin Authentication
├── conf/                      # Database Configuration
│   └── dbconf.php             # Intelligent DB connection & fallback
├── includes/                  # Modular Component Partials
│   ├── alerts.php             # Flash toast & alert renderer
│   ├── auth.php               # Session state, guards & file uploader
│   ├── footer.php             # Semantic footer component
│   ├── header.php             # HTML head & CDN assets
│   └── navbar.php             # Responsive dynamic navigation
├── jobs/                      # Backward-compatible routing
│   ├── job.php                # Redirects to /jobs.php
│   └── job-details.php        # Redirects to /job_details.php
├── uploads/                   # Upload Storage
│   ├── logos/                 # Company logos
│   ├── profiles/              # Student profile pictures
│   └── resumes/               # Candidate PDF resumes
├── apply_job.php              # Application & bookmark handler
├── browse_candidates.php      # Undergraduate talent pool directory
├── config.php                 # Master configuration & environment constants
├── contact.php                # Contact page with DB persistence
├── database.sql               # Complete MySQL schema & seed data
├── DEPLOYMENT.md              # Deployment guide (Local, cPanel, VPS, Docker)
├── employer_applicants.php    # Employer Applicant Tracking System (ATS)
├── employer_edit_job.php      # Edit existing job posting
├── employer_post_job.php      # Post new job vacancy
├── index.php                  # Modern dynamic landing page
├── jobs.php                   # Dynamic job search & multi-facet filters
├── job_details.php            # Detailed job view & Apply modal
├── logout.php                 # Session destruction handler
├── profile_employer.php       # Employer dashboard & job manager
├── profile_undergraduate.php  # Student profile & applications hub
├── send_email.php             # Contact submission dispatcher
├── signin_employer.php        # Employer sign in
├── signin_undergraduate.php   # Student sign in
├── signup_employer.php        # Employer registration
├── signup_undergraduate.php   # Student registration
├── style.css                  # Custom CSS design system
└── unidetails.sql             # Schema alias
```

---

## 🚀 Quick Start (Local Setup with XAMPP)

1. Ensure **Apache** and **MySQL** are running in your XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin/` in your browser.
3. Import `database.sql` into MySQL.
4. Access the web application at:
   ```
   http://localhost/UGPRO_JOB_PORTEL/
   ```

### 🔑 Demo Accounts for Testing

| User Role | Username / Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` or `admin@ugpro.lk` | `admin123` |
| **Undergraduate Student** | `illiyas@vau.ac.lk` | `student123` |
| **Employer (Virtusa)** | `careers@virtusa.com` | `employer123` |
| **Employer (WSO2)** | `recruitment@wso2.com` | `employer123` |

---

## 📄 License & Credits

Developed for the **University of Vavuniya** Career Guidance & Placement Unit.
All Rights Reserved &copy; <?= date('Y') ?> UgPro.
