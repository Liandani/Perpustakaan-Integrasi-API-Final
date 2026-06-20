-- Disable foreign key checks to allow truncating
SET FOREIGN_KEY_CHECKS = 0;

-- Wipe existing data
TRUNCATE TABLE user_db.users;
TRUNCATE TABLE book_db.books;
TRUNCATE TABLE loan_db.loans;
TRUNCATE TABLE loan_db.loan_histories;
TRUNCATE TABLE fine_db.fines;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Seed user_db
-- ============================================
-- Note: 'phone' column doesn't exist in user_db.users, so we skip it.
-- We provide a default password hash for 'password' column.
INSERT INTO user_db.users (`id`, `name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Citra', 'citra@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- ============================================
-- Seed book_db
-- ============================================
INSERT INTO book_db.books (`id`, `title`, `available`, `created_at`, `updated_at`) VALUES
(1, 'Laravel for Beginners', 0, NOW(), NOW()),
(2, 'Clean Code', 1, NOW(), NOW()),
(3, 'Design Patterns', 1, NOW(), NOW()),
(4, 'The Pragmatic Programmer', 1, NOW(), NOW()),
(5, 'Refactoring', 1, NOW(), NOW());

-- ============================================
-- Seed loan_db
-- ============================================
-- Active Loan
INSERT INTO loan_db.loans (`id`, `user_id`, `book_id`, `loan_date`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'borrowed', NOW(), NOW());

-- Loan Histories (Adapted to our microservice schema which uses 'action')
-- User 1 borrowed Book 1
INSERT INTO loan_db.loan_histories (`loan_id`, `user_id`, `book_id`, `action`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'borrowed', NOW(), NOW());

-- Completed Loan History for Book 2
-- We need a fake loan_id for history, let's say loan 2
INSERT INTO loan_db.loan_histories (`loan_id`, `user_id`, `book_id`, `action`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 'borrowed', '2026-04-05 10:00:00', '2026-04-05 10:00:00'),
(2, 1, 2, 'returned', '2026-04-12 10:00:00', '2026-04-12 10:00:00');

-- Active Loan History for Book 3 (From the dummy data, it says book 3 was borrowed on 2026-04-20)
-- Wait, the loans table in dummy data only had book 1! But let's insert it as a loan too!
INSERT INTO loan_db.loans (`id`, `user_id`, `book_id`, `loan_date`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(3, 1, 3, '2026-04-20', '2026-04-27', 'borrowed', '2026-04-20 10:00:00', '2026-04-20 10:00:00');

INSERT INTO loan_db.loan_histories (`loan_id`, `user_id`, `book_id`, `action`, `created_at`, `updated_at`) VALUES
(3, 1, 3, 'borrowed', '2026-04-20 10:00:00', '2026-04-20 10:00:00');
