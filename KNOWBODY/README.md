# KnowBody - Personal Fitness Companion

KnowBody is a comprehensive web-based fitness tracking platform designed to help users transform their health and reach their fitness goals. It provides tools for tracking BMI, monitoring calorie intake, managing personalized workout plans, and providing feedback.

## 🚀 Features

### User Features
- **Dashboard:** At-a-glance view of active workout plans, latest BMI, and calorie intake.
- **BMI Calculator:** Calculate and track Body Mass Index over time with visual history.
- **Calorie Calculator:** Estimate daily calorie needs based on age, weight, height, and gender.
- **My Workouts:** View assigned workout plans and update progress (0% to 100%).
- **Progress Reports:** Visual charts (using Chart.js) showing BMI trends and calorie tracking.
- **Feedback:** Submit suggestions or report issues directly to administrators.
- **Exercise List:** Browse a library of available exercises.

### Admin Features
- **Admin Dashboard:** Monitor total users, exercises, active plans, and pending feedback.
- **User Management:** View and delete user accounts.
- **Workout Management:** Create and edit workout plans and exercises.
- **Plan Assignment:** Assign specific workout plans to users with custom start and end dates.
- **Feedback Review:** Read and respond to user feedback.
- **User Reports:** Detailed statistical view of individual user progress.

## 🛠 Tech Stack
- **Frontend:** HTML5, CSS3 (Glassmorphism design), JavaScript, Google Fonts, Material Icons.
- **Charts:** Chart.js for data visualization.
- **Backend:** PHP (Procedural/Object-Oriented mix).
- **Database:** MySQL (MariaDB).

## 📥 Installation

### Prerequisites
1. **XAMPP** (or any local server with PHP and MySQL support).
2. A web browser.

### Setup Steps
1. **Clone/Download the repository:**
   Place the project folder in your `C:/xampp/htdocs/` directory. (e.g., `C:/xampp/htdocs/KNOWBODY/`).

2. **Configure Database:**
   - Start **Apache** and **MySQL** in the XAMPP Control Panel.
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a new database named `knowbody`.
   - Select the `knowbody` database and go to the **Import** tab.
   - Choose the `database/knowbody.sql` file and click **Import**.
   - *(Optional)* To see dummy data, import `database/dummy_sql.sql` after.

3. **Update Configuration:**
   - Open `config.php` in the project root.
   - Ensure `BASE_URL` matches your local path (default set to `/dashboard/KNOWBODY/KNOWBODY`).

4. **Access the App:**
   - Go to `http://localhost/dashboard/KNOWBODY/KNOWBODY/index.php`.

## 🔑 Default Credentials

### Admin Login
- **Username:** `admin`
- **Password:** `admin` (or original hashed password in SQL)

### Test User (from dummy_sql.sql)
- **Username:** `testuser`
- **Password:** `password123`

## 📄 License
This project is for educational purposes as part of a fitness management system.
