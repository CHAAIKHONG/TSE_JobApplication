//先给admin添加账户

INSERT INTO admin (username, password, fullname)
VALUES
('admin', 'admin123', 'System Administrator');

//加job
INSERT INTO jobs (admin_id, jobtitle, position, salary, details, created_at, badge)
VALUES

(1, 'Web Developer Internship', 'Intern', 800.00,
'Assist in developing and maintaining company websites using HTML, CSS, PHP and MySQL.',
'2026-06-08 18:36:45', 'Remote'),

(1, 'IT Support Technician', 'Full Time', 2500.00,
'Provide technical support, troubleshoot hardware and software issues, and assist customers.',
'2026-06-08 18:36:45', 'Urgent'),

(1, 'Network Engineer', 'Full Time', 3500.00,
'Manage network infrastructure, configure routers and switches, and monitor network performance.',
'2026-06-08 18:36:45', 'Onsite'),

(1, 'CCTV Installation Technician', 'Contract', 2200.00,
'Install and maintain CCTV systems, perform wiring and customer training.',
'2026-06-08 18:36:45', 'Remote,Urgent'),

(1, 'Software Developer', 'Full Time', 4000.00,
'Design, develop and maintain web applications and databases.',
'2026-06-08 18:36:45', 'Onsite,Urgent'),

(1, 'UI/UX Designer', 'Full Time', 3200.00,
'Design user-friendly interfaces, create wireframes and improve user experience.',
'2026-06-08 18:56:16', 'Remote'),

(1, 'Graphic Designer', 'Full Time', 2800.00,
'Create marketing materials, social media content and branding assets.',
'2026-06-08 18:56:16', 'Remote'),

(1, 'Data Analyst', 'Full Time', 4500.00,
'Analyze business data, create reports and provide insights for decision making.',
'2026-06-08 18:56:16', 'Urgent'),

(1, 'Cyber Security Analyst', 'Full Time', 5000.00,
'Monitor security threats, perform vulnerability assessments and improve security controls.',
'2026-06-08 18:56:16', 'Onsite,Urgent'),

(1, 'Database Administrator', 'Full Time', 4800.00,
'Manage MySQL databases, perform backups and optimize database performance.',
'2026-06-08 18:56:16', 'Onsite'),

(1, 'Mobile App Developer', 'Full Time', 4200.00,
'Develop Android and iOS applications using modern frameworks.',
'2026-06-08 18:56:16', 'Remote'),

(1, 'Technical Support Executive', 'Full Time', 2600.00,
'Provide customer support and troubleshoot software and hardware issues.',
'2026-06-08 18:56:16', 'Urgent'),

(1, 'Cloud Engineer', 'Full Time', 5500.00,
'Deploy and maintain cloud infrastructure on AWS and Azure.',
'2026-06-08 18:56:16', 'Remote,Urgent'),

(1, 'Digital Marketing Executive', 'Full Time', 3000.00,
'Manage online advertising campaigns and social media marketing.',
'2026-06-08 18:56:16', 'Remote'),

(1, 'System Administrator', 'Full Time', 4000.00,
'Maintain servers, user accounts, network services and system security.',
'2026-06-08 18:56:16', 'Onsite');