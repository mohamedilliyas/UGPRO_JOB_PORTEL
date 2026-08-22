<?php
/**
 * Fallback Data Provider - UgPro
 * Provides realistic mock data and demo auth when live database is cold, sleeping, or temporarily offline.
 */

if (!function_exists('get_fallback_stats')) {
    function get_fallback_stats() {
        return [
            'total_jobs' => 12,
            'total_employers' => 8,
            'total_students' => 150,
            'total_applications' => 45
        ];
    }
}

if (!function_exists('get_fallback_categories')) {
    function get_fallback_categories() {
        return [
            ['id' => 1, 'name' => 'Software Engineering', 'slug' => 'software-engineering', 'icon' => 'bi-code-slash'],
            ['id' => 2, 'name' => 'Data Science & AI', 'slug' => 'data-science-ai', 'icon' => 'bi-cpu'],
            ['id' => 3, 'name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'icon' => 'bi-shield-check'],
            ['id' => 4, 'name' => 'UI/UX & Product Design', 'slug' => 'ui-ux-design', 'icon' => 'bi-palette'],
            ['id' => 5, 'name' => 'Business & Management', 'slug' => 'business-management', 'icon' => 'bi-graph-up-arrow'],
            ['id' => 6, 'name' => 'Cloud & DevOps', 'slug' => 'cloud-devops', 'icon' => 'bi-cloud-check'],
            ['id' => 7, 'name' => 'Quality Assurance (QA)', 'slug' => 'quality-assurance', 'icon' => 'bi-bug'],
            ['id' => 8, 'name' => 'Mobile App Development', 'slug' => 'mobile-development', 'icon' => 'bi-phone']
        ];
    }
}

if (!function_exists('get_fallback_jobs')) {
    function get_fallback_jobs() {
        return [
            [
                'id' => 1,
                'employer_id' => 1,
                'category_id' => 1,
                'title' => 'Associate Software Engineer (Full Stack)',
                'job_type' => 'Full Time',
                'workplace_type' => 'Hybrid',
                'location' => 'Colombo, Sri Lanka',
                'salary_range' => 'LKR 90,000 - 140,000 / month',
                'vacancy_count' => 3,
                'working_hours' => '40h / week',
                'experience_level' => 'Entry Level / Undergraduate',
                'education_req' => "Bachelor's in IT / Computer Science or Software Engineering",
                'description' => "We are looking for passionate undergraduate interns / fresh graduates from University of Vavuniya to join our high-impact enterprise engineering team building modern distributed applications.",
                'responsibilities' => "Design, build, and maintain scalable microservices using PHP, Node.js, and React. Collaborate with senior architects, participate in code reviews, and implement CI/CD pipelines.",
                'requirements' => "Strong problem-solving skills, solid understanding of OOP, PHP, JavaScript, SQL, Git, and RESTful APIs. Willingness to learn cloud-native tech (AWS/Docker).",
                'benefits' => "Mentorship from Principal Engineers, flexible hybrid schedule, health insurance, paid study leaves, and full-time employment conversion opportunity.",
                'deadline' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'active',
                'views_count' => 312,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'company_name' => 'Virtusa (Pvt) Ltd',
                'company_logo' => 'images/google.png',
                'category_name' => 'Software Engineering',
                'category_slug' => 'software-engineering',
                'industry' => 'Information Technology',
                'company_website' => 'https://www.virtusa.com',
                'company_location' => 'Colombo 07, Sri Lanka',
                'company_about' => 'Virtusa Corporation is a global provider of digital business strategy, digital engineering, and IT services.'
            ],
            [
                'id' => 2,
                'employer_id' => 2,
                'category_id' => 1,
                'title' => 'Software Engineering Intern - Cloud Platforms',
                'job_type' => 'Internship',
                'workplace_type' => 'Remote',
                'location' => 'Colombo / Remote',
                'salary_range' => 'LKR 45,000 - 65,000 / month',
                'vacancy_count' => 5,
                'working_hours' => '40h / week',
                'experience_level' => 'Undergraduate (3rd / 4th Year)',
                'education_req' => "Undergraduate in Computer Science / ICT / Applied Science",
                'description' => "Join WSO2's cloud middleware team. Work directly on open-source projects, API gateways, and cloud-native Kubernetes integrations.",
                'responsibilities' => "Develop unit and integration test suites, contribute to open-source components, and build connectors for enterprise integrations.",
                'requirements' => "Familiarity with Java, Go, or PHP/JavaScript. Basic knowledge of HTTP protocols, REST, JSON, and Git version control.",
                'benefits' => "Full remote equipment setup, mentorship by open-source maintainers, university internship credits certification.",
                'deadline' => date('Y-m-d', strtotime('+45 days')),
                'status' => 'active',
                'views_count' => 458,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'company_name' => 'WSO2 Lanka (Pvt) Ltd',
                'company_logo' => 'images/google.png',
                'category_name' => 'Software Engineering',
                'category_slug' => 'software-engineering',
                'industry' => 'Open Source & Cloud Middleware',
                'company_website' => 'https://wso2.com',
                'company_location' => 'Colombo 03, Sri Lanka',
                'company_about' => 'WSO2 is a global open-source technology leader delivering digital transformation architectures.'
            ],
            [
                'id' => 3,
                'employer_id' => 3,
                'category_id' => 2,
                'title' => 'Junior Data Analyst / AI Associate',
                'job_type' => 'Full Time',
                'workplace_type' => 'On-site',
                'location' => 'Colombo 04, Sri Lanka',
                'salary_range' => 'LKR 80,000 - 120,000 / month',
                'vacancy_count' => 2,
                'working_hours' => '40h / week',
                'experience_level' => 'Entry Level / Fresh Graduate',
                'education_req' => "BSc in Physical Science / Mathematics / Computer Science / ICT",
                'description' => "IFS is seeking analytical minds to work on predictive analytics, enterprise forecasting models, and business intelligence dashboards for international manufacturing clients.",
                'responsibilities' => "Analyze complex multi-dimensional datasets, build automated PowerBI / Tableau dashboards, and write optimized SQL analytical queries.",
                'requirements' => "Proficiency in SQL, Python (Pandas, NumPy), Excel modeling, and statistical analysis.",
                'benefits' => "Global project exposure, modern office environment, subsidized meals, wellness allowance.",
                'deadline' => date('Y-m-d', strtotime('+20 days')),
                'status' => 'active',
                'views_count' => 195,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'company_name' => 'IFS R&D International',
                'company_logo' => 'images/google.png',
                'category_name' => 'Data Science & AI',
                'category_slug' => 'data-science-ai',
                'industry' => 'Enterprise Software Solutions',
                'company_website' => 'https://www.ifs.com',
                'company_location' => 'Colombo 04, Sri Lanka',
                'company_about' => 'IFS develops and delivers enterprise software for companies around the world.'
            ],
            [
                'id' => 4,
                'employer_id' => 4,
                'category_id' => 4,
                'title' => 'UI/UX Designer Intern',
                'job_type' => 'Internship',
                'workplace_type' => 'Hybrid',
                'location' => 'Colombo 03, Sri Lanka',
                'salary_range' => 'LKR 40,000 - 55,000 / month',
                'vacancy_count' => 2,
                'working_hours' => '35h / week',
                'experience_level' => 'Undergraduate (2nd / 3rd / 4th Year)',
                'education_req' => "Undergraduate in IT, Multimedia, Design or equivalent",
                'description' => "Collaborate with Scandinavian software engineering squads to craft intuitive user journeys, wireframes, and design systems for enterprise SaaS applications.",
                'responsibilities' => "Create wireframes, prototypes, user personas in Figma. Conduct usability tests and translate user feedback into clean interface designs.",
                'requirements' => "Portfolio demonstrating Figma UI/UX projects, typography principles, and interactive prototyping skills.",
                'benefits' => "Direct collaboration with European design directors, Apple Mac workstation, hybrid work flexibility.",
                'deadline' => date('Y-m-d', strtotime('+15 days')),
                'status' => 'active',
                'views_count' => 280,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'company_name' => 'Creative Software',
                'company_logo' => 'images/google.png',
                'category_name' => 'UI/UX & Product Design',
                'category_slug' => 'ui-ux-design',
                'industry' => 'Software R&D Services',
                'company_website' => 'https://www.creativesoftware.com',
                'company_location' => 'Colombo 03, Sri Lanka',
                'company_about' => 'Creative Software is a pioneer in building international software solutions with Scandinavian partners.'
            ]
        ];
    }
}

if (!function_exists('get_fallback_candidates')) {
    function get_fallback_candidates() {
        return [
            [
                'id' => 1,
                'full_name' => 'Mohamed Illiyas',
                'reg_no' => '2019/ICT/01',
                'email' => 'illiyas@vau.ac.lk',
                'phone' => '+94 77 123 4567',
                'faculty' => 'Faculty of Technological Studies',
                'course' => 'BSc in Information & Communication Technology',
                'year_of_study' => '4th Year',
                'gpa' => '3.82',
                'skills' => 'PHP, MySQL, JavaScript, React, Python, Docker, Cloud Deployment',
                'bio' => 'Driven final-year ICT undergraduate with strong full-stack web development and software architecture expertise.',
                'linkedin_url' => 'https://linkedin.com',
                'github_url' => 'https://github.com/mohamedilliyas',
                'profile_image' => 'images/fl-3.png',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'full_name' => 'Naveen Fernando',
                'reg_no' => '2020/AS/42',
                'email' => 'naveen.f@vau.ac.lk',
                'phone' => '+94 71 456 7890',
                'faculty' => 'Faculty of Applied Science',
                'course' => 'BSc (Hons) in Computer Science',
                'year_of_study' => '3rd Year',
                'gpa' => '3.75',
                'skills' => 'Java, Spring Boot, PostgreSQL, Machine Learning, AWS',
                'bio' => 'Computer Science undergraduate passionate about backend systems, algorithms, and applied machine learning.',
                'linkedin_url' => 'https://linkedin.com',
                'github_url' => 'https://github.com',
                'profile_image' => 'images/fl-3.png',
                'status' => 'active'
            ],
            [
                'id' => 3,
                'full_name' => 'Praveena Rajendran',
                'reg_no' => '2020/BS/18',
                'email' => 'praveena.r@vau.ac.lk',
                'phone' => '+94 76 987 6543',
                'faculty' => 'Faculty of Business Studies',
                'course' => 'BBM (Hons) in Human Resource & Marketing',
                'year_of_study' => '4th Year',
                'gpa' => '3.89',
                'skills' => 'Talent Acquisition, Business Analytics, PowerBI, Digital Marketing, SEO',
                'bio' => 'High-achieving Business undergraduate specializing in HR analytics, corporate recruitment, and talent branding.',
                'linkedin_url' => 'https://linkedin.com',
                'github_url' => '',
                'profile_image' => 'images/fl-3.png',
                'status' => 'active'
            ]
        ];
    }
}

if (!function_exists('verify_fallback_demo_auth')) {
    function verify_fallback_demo_auth($role, $identifier, $password) {
        $cleanId = trim(strtolower($identifier));
        
        if ($role === 'admin') {
            if (($cleanId === 'admin' || $cleanId === 'admin@ugpro.lk') && $password === 'admin123') {
                return [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@ugpro.lk',
                    'role' => 'admin'
                ];
            }
        }
        
        if ($role === 'student') {
            if ($cleanId === 'illiyas@vau.ac.lk' && $password === 'student123') {
                return [
                    'id' => 1,
                    'full_name' => 'Mohamed Illiyas',
                    'email' => 'illiyas@vau.ac.lk',
                    'course' => 'BSc in Information & Communication Technology',
                    'profile_image' => 'images/fl-3.png',
                    'role' => 'student'
                ];
            }
        }
        
        if ($role === 'employer') {
            $employers = [
                'careers@virtusa.com' => ['id' => 1, 'name' => 'Virtusa (Pvt) Ltd', 'logo' => 'images/google.png'],
                'recruitment@wso2.com' => ['id' => 2, 'name' => 'WSO2 Lanka (Pvt) Ltd', 'logo' => 'images/google.png'],
                'careers@ifs.com' => ['id' => 3, 'name' => 'IFS R&D International', 'logo' => 'images/google.png'],
                'hr@creativesoftware.com' => ['id' => 4, 'name' => 'Creative Software', 'logo' => 'images/google.png']
            ];
            
            if (isset($employers[$cleanId]) && $password === 'employer123') {
                return [
                    'id' => $employers[$cleanId]['id'],
                    'company_name' => $employers[$cleanId]['name'],
                    'email' => $cleanId,
                    'company_logo' => $employers[$cleanId]['logo'],
                    'role' => 'employer'
                ];
            }
        }
        
        return false;
    }
}
