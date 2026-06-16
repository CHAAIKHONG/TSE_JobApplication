ALTER TABLE education
CHANGE COLUMN degree qualification VARCHAR(255) NOT NULL;
ADD COLUMN cgpa DECIMAL(3,2) NULL AFTER field_of_study;
ADD COLUMN description TEXT NULL AFTER field_of_study;