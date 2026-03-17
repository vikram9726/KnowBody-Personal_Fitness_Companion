-- Dummy Data for KnowBody Database testing
-- This script will CLEAR existing user data and insert test values.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0; -- Disable checks to allow clearing tables
START TRANSACTION;

-- Clear existing data using DELETE (Table-dependant TRUNCATE fails in some versions)
DELETE FROM `user_progress`;
DELETE FROM `workout_exercises`;
DELETE FROM `user_plans`;
DELETE FROM `user_calorie`;
DELETE FROM `user_bmi`;
DELETE FROM `feedback`;
DELETE FROM `users`;
DELETE FROM `exercises`;
DELETE FROM `workout_plans`;

-- --------------------------------------------------------
-- Users (ID 1: testuser, ID 2: john_doe)
-- Password for all: password123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------
-- Exercises
-- --------------------------------------------------------
INSERT INTO `exercises` (`id`, `name`, `muscle_group`, `description`) VALUES
(2, 'Pushups', 'Chest', 'Standard pushups for chest and triceps strength.'),
(3, 'Squats', 'Legs', 'Bodyweight squats for leg development.'),
(4, 'Plank', 'Core', 'Isometric core exercise to build stability.'),
(5, 'Running', 'Cardio', 'Outdoor or treadmill running for stamina.');

-- --------------------------------------------------------
-- Workout Plans
-- --------------------------------------------------------
INSERT INTO `workout_plans` (`id`, `name`, `description`, `duration_weeks`, `level`) VALUES
(2, 'Beginner Blast', 'A 4-week program for those starting their fitness journey.', 4, 'beginner'),
(3, 'Advanced Strength', 'High intensity 8-week program for experienced athletes.', 8, 'advanced');

-- --------------------------------------------------------
-- Workout Exercises
-- --------------------------------------------------------
INSERT INTO `workout_exercises` (`id`, `workout_plan_id`, `exercise_id`, `sets_default`, `reps_default`) VALUES
(1, 2, 3, 4, 15),
(2, 2, 4, 3, 60),
(3, 3, 2, 5, 20);

-- --------------------------------------------------------
-- User Assigned Plans
-- --------------------------------------------------------
INSERT INTO `user_plans` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `completion_rate`, `status`) VALUES
(1, 1, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 20.00, 'Active'),
(2, 2, 3, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 22 DAY), 100.00, 'Completed');

-- --------------------------------------------------------
-- User BMI History (Last 3 records)
-- --------------------------------------------------------
INSERT INTO `user_bmi` (`id`, `weight`, `height`, `bmi_value`, `created_at`) VALUES
(1, 80, 1.8, 24.69, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 78, 1.8, 24.07, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 77, 1.8, 23.77, NOW());

-- --------------------------------------------------------
-- User Calorie History
-- --------------------------------------------------------
INSERT INTO `user_calorie` (`id`, `age`, `weight`, `height`, `gender`, `calorie`, `recorded_at`) VALUES
(1, 25, 80, 180, 'male', 2800, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 25, 77, 180, 'male', 2750, NOW());

-- --------------------------------------------------------
-- Feedback
-- --------------------------------------------------------
INSERT INTO `feedback` (`user_id`, `message`, `status`, `created_at`) VALUES
(1, 'I love the new workout plans!', 'Pending', NOW()),
(2, 'Could you add more cardio?', 'Reviewed', DATE_SUB(NOW(), INTERVAL 1 DAY));

SET FOREIGN_KEY_CHECKS = 1; -- Re-enable checks
COMMIT;
