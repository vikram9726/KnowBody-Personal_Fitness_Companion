SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Drop tables if they exist to allow a clean import
DROP TABLE IF EXISTS `workout_exercises`;
DROP TABLE IF EXISTS `user_progress`;
DROP TABLE IF EXISTS `user_plans`;
DROP TABLE IF EXISTS `user_calorie`;
DROP TABLE IF EXISTS `user_bmi`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `exercises`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `workout_plans`;


-- --------------------------------------------------------

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `adminname` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`id`, `adminname`, `password`) VALUES
(1, 'admin', '$2y$10$xu37O4q15S1DI00YcveJPOH.WAWl1z/3eYlWFanj/O8otlCrLcgES');

-- --------------------------------------------------------

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `muscle_group` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `exercises` (`id`, `name`, `muscle_group`, `description`) VALUES
(1, 'weight gain', 'Chest', 'efefe');

-- --------------------------------------------------------

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_response` text DEFAULT NULL,
  `status` enum('Pending','Reviewed') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `user_bmi` (
  `bmi_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `weight` float NOT NULL,
  `height` float NOT NULL,
  `bmi_value` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `user_calorie` (
  `calorie_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `weight` float NOT NULL,
  `height` float NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `calorie` float NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `user_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `completion_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('Active','Completed','Abandoned','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `workout_plan_id` int(11) NOT NULL,
  `exercise_id` int(11) NOT NULL,
  `sets` int(11) DEFAULT NULL,
  `reps` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `date` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `workout_exercises` (
  `id` int(11) NOT NULL,
  `workout_plan_id` int(11) NOT NULL,
  `exercise_id` int(11) NOT NULL,
  `sets_default` int(11) DEFAULT NULL,
  `reps_default` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `workout_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_weeks` int(11) NOT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_plans`
--

INSERT INTO `workout_plans` (`id`, `name`, `description`, `duration_weeks`, `level`, `created_at`) VALUES
(1, 'weight gain', 'rfesrf54', 54, 'intermediate', '2025-10-28 10:07:06');

--
-- Indexes and Keys
--

ALTER TABLE `admins` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `adminname` (`adminname`);
ALTER TABLE `exercises` ADD PRIMARY KEY (`id`);
ALTER TABLE `feedback` ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `reviewed_by` (`reviewed_by`);
ALTER TABLE `users` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `user_bmi` ADD PRIMARY KEY (`bmi_id`), ADD KEY `id` (`id`);
ALTER TABLE `user_calorie` ADD PRIMARY KEY (`calorie_id`), ADD KEY `id` (`id`);
ALTER TABLE `user_plans` ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `plan_id` (`plan_id`);
ALTER TABLE `user_progress` ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `workout_plan_id` (`workout_plan_id`), ADD KEY `exercise_id` (`exercise_id`);
ALTER TABLE `workout_exercises` ADD PRIMARY KEY (`id`), ADD KEY `workout_plan_id` (`workout_plan_id`), ADD KEY `exercise_id` (`exercise_id`);
ALTER TABLE `workout_plans` ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT
--

ALTER TABLE `admins` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `exercises` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `feedback` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `user_bmi` MODIFY `bmi_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_calorie` MODIFY `calorie_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_plans` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_progress` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `workout_exercises` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `workout_plans` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints
--

ALTER TABLE `feedback` ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
ALTER TABLE `user_bmi` ADD CONSTRAINT `user_bmi_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`);
ALTER TABLE `user_calorie` ADD CONSTRAINT `user_calorie_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`);
ALTER TABLE `user_plans` ADD CONSTRAINT `user_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `user_plans_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_progress` ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`), ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans` (`id`), ADD CONSTRAINT `user_progress_ibfk_3` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`);
ALTER TABLE `workout_exercises` ADD CONSTRAINT `workout_exercises_ibfk_1` FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans` (`id`), ADD CONSTRAINT `workout_exercises_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`);

COMMIT;
