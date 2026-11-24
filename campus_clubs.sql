-- ============================================================================
-- Campus Clubs Management System — Database Export (campus_clubs.sql)
-- Undergraduate PHP & MySQL Course Project
-- Import directly in phpMyAdmin (XAMPP / MySQL 8+ / MariaDB 10.4+)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `campus_clubs_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `campus_clubs_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `club_members`;
DROP TABLE IF EXISTS `membership_applications`;
DROP TABLE IF EXISTS `clubs`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. users
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `account_status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Approved',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. clubs
-- ----------------------------------------------------------------------------
CREATE TABLE `clubs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(140) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(80) NOT NULL,
    `activities` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT 'images/club-tech.jpg',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_clubs_name` (`name`),
    KEY `idx_clubs_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. membership_applications
-- ----------------------------------------------------------------------------
CREATE TABLE `membership_applications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `club_id` INT UNSIGNED NOT NULL,
    `motivation` TEXT DEFAULT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `application_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_apps_student_club` (`student_id`, `club_id`, `status`),
    KEY `idx_apps_status` (`status`),
    CONSTRAINT `fk_apps_student`
        FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_apps_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. club_members
-- ----------------------------------------------------------------------------
CREATE TABLE `club_members` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `club_id` INT UNSIGNED NOT NULL,
    `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_member_student_club` (`student_id`, `club_id`),
    KEY `idx_members_club` (`club_id`),
    CONSTRAINT `fk_members_student`
        FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_members_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. announcements
-- ----------------------------------------------------------------------------
CREATE TABLE `announcements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `club_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(180) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_announcements_club` (`club_id`),
    KEY `idx_announcements_created` (`created_at`),
    CONSTRAINT `fk_announcements_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Sample Test Data (Fictional Course Accounts — bcrypt hashed passwords)
--   Admin:     admin@campus.edu    / Admin@123
--   Student 1: student1@campus.edu / Student@123
--   Student 2: student2@campus.edu / Student@123
-- ============================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Campus Admin (Test)', 'admin@campus.edu', '$2y$10$80319WCB3vSNXnKFkfz1L.5omjMap9oZ6RQq6BYAC2zQu.LTFGHBu', 'admin'),
(2, 'Student One (Test)', 'student1@campus.edu', '$2y$10$lHBRjPBPrfipV1YCAsSqn.wlmUg9ARHvlfoBwpY6yxgRCxf5xVmRy', 'student'),
(3, 'Student Two (Test)', 'student2@campus.edu', '$2y$10$lHBRjPBPrfipV1YCAsSqn.wlmUg9ARHvlfoBwpY6yxgRCxf5xVmRy', 'student');

INSERT INTO `clubs` (`id`, `name`, `description`, `category`, `activities`, `image`) VALUES
(1, 'Creative Coding & Web Studio',
 'A collaborative campus studio where students build thoughtful web projects, practice clean PHP and JavaScript craft, and share open critiques every week.',
 'Technology',
 'Weekly code studio sessions; Campus web showcase; Peer code review circles; Intro to UI craft workshops',
 'images/club-tech.jpg'),
(2, 'Visual Arts & Editorial Design',
 'Dedicated to typography, poster design, zine publishing, and campus photography. Members curate seasonal exhibitions and design visual identities for student events.',
 'Arts & Design',
 'Editorial layout critiques; Campus zine print night; Typography & color theory talks; Student exhibition curation',
 'images/club-design.jpg'),
(3, 'Literature & Reading Circle',
 'A calm community for readers and writers exploring contemporary Arabic and world literature, essay workshops, and lively weekly book salons.',
 'Culture',
 'Bi-weekly book salons; Short story writing evenings; Poetry reading gatherings; Library coffee talks',
 'images/club-culture.jpg'),
(4, 'Community & Campus Volunteers',
 'Brings students together around meaningful campus and neighborhood initiatives, from welcoming new freshmen to organizing charity and sustainability drives.',
 'Community',
 'Freshman welcome week guides; Seasonal donation drives; Campus greening days; Peer tutoring sessions',
 'images/club-community.jpg'),
(5, 'Entrepreneurship & Ideas Lab',
 'A practical space for turning classroom concepts into real student initiatives through teamwork, prototyping, and friendly pitch afternoons.',
 'Leadership',
 'Idea validation workshops; Student founder fireside chats; Project pitch afternoons; Teamwork & planning clinics',
 'images/club-leadership.jpg');

INSERT INTO `membership_applications` (`id`, `student_id`, `club_id`, `motivation`, `status`, `application_date`) VALUES
(1, 2, 1, 'I love building web interfaces and would enjoy collaborating on student coding projects this semester.', 'Approved', '2026-11-16 10:00:00'),
(2, 2, 2, 'I want to learn editorial typography and poster layout design for campus cultural events.', 'Pending', '2026-11-21 14:20:00'),
(3, 3, 3, 'Looking forward to joining weekly book discussions and practicing short essay writing.', 'Pending', '2026-11-22 11:45:00'),
(4, 3, 4, 'Excited to volunteer in student orientation and community tutoring programs.', 'Approved', '2026-11-18 09:15:00');

INSERT INTO `club_members` (`id`, `student_id`, `club_id`, `joined_at`) VALUES
(1, 2, 1, '2026-11-17 09:00:00'),
(2, 3, 4, '2026-11-19 11:30:00');

INSERT INTO `announcements` (`id`, `club_id`, `title`, `content`, `created_at`) VALUES
(1, NULL, 'Autumn Student Clubs Fair Opens Next Tuesday',
 'All five campus clubs will gather in the Main Student Courtyard from 11:00 AM to 2:00 PM. Stop by each booth to meet club coordinators, browse past student work, and discover where you belong this semester.',
 '2026-11-23 09:00:00'),
(2, 1, 'Open Studio: Building Clean PHP & MySQL Apps',
 'Bring your laptop this Sunday at 1:00 PM in Lab 204 for a hands-on peer session on PDO queries, layout polish, and responsive CSS techniques. Open to all members and curious newcomers.',
 '2026-11-21 12:30:00'),
(3, 2, 'Call for Submissions: Winter Campus Zine Issue 04',
 'We are collecting original posters, photography, and short essays for our upcoming printed campus zine. Submit your drafts by Thursday evening at the Design Studio.',
 '2026-11-19 15:10:00'),
(4, 4, 'Weekend Campus Greening & Welcome Drive',
 'Join fellow volunteers this Saturday morning at 9:00 AM near the central library gardens to help refresh campus seating areas and prepare welcome kits for new students.',
 '2026-11-18 08:45:00');
