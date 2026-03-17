<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowBody</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/feature/css/main.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f5f5;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem 5%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }

        .logo span {
            color: #4481eb;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #4481eb;
        }

        .login-btn {
            background: #4481eb;
            color: white !important;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            transition: background 0.3s;
        }

        .login-btn:hover {
            background: #2d6ad9;
        }

        .hero {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding: 0 5%;
            position: relative;
        }

        .hero-content {
            color: white;
            max-width: 600px;
        }

        .fitness-element {
            color: #4481eb;
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-description {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .cta-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: #4481eb;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .cta-button:hover {
            background: #2d6ad9;
        }

        .features {
            padding: 5rem 5%;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            color: #4481eb;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .story-section {
            padding: 5rem 5%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .story-image {
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .story-content {
            padding-right: 2rem;
        }

        .story-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .story-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .story-section {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .story-content {
                padding-right: 0;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
       
        <a href="index.php" class="logo">
            <span class="material-icons"
                style="font-size:2.2rem; margin-bottom: 15px;  color: var(--primary-color);">fitness_center </span>Know<span>Body</span></a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <a href="<?php echo BASE_URL; ?>/feature/login_form.php" class="login-btn">Login</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <div class="fitness-element">FITNESS ELEMENTS</div>
            <h1 class="hero-title">Transform Your Body, Transform Your Life</h1>
            <p class="hero-description">
                Start your fitness journey with KnowBody. Get personalized workout plans,
                track your BMI, monitor calories, and achieve your fitness goals with our
                comprehensive platform.
            </p>
            <a href="<?php echo BASE_URL; ?>/feature/register_form.php" class="cta-button">Get Started</a>
        </div>
    </section>

    <section id="features" class="features">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div class="feature-card">
                <span class="material-icons feature-icon">monitor_weight</span>
                <h3>BMI Calculator</h3>
                <p>Track your Body Mass Index with our easy-to-use calculator and monitor your progress over time.</p>
            </div>
            <div class="feature-card">
                <span class="material-icons feature-icon">local_fire_department</span>
                <h3>Calorie Counter</h3>
                <p>Monitor your daily calorie intake and get personalized recommendations based on your goals.</p>
            </div>
            <div class="feature-card">
                <span class="material-icons feature-icon">fitness_center</span>
                <h3>Workout Plans</h3>
                <p>Access customized workout plans designed by fitness experts for all experience levels.</p>
            </div>
        </div>
    </section>

    <section id="about" class="story-section">
        <img src="https://images.unsplash.com/photo-1599058945522-28d584b6f0ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=1050&q=80"
            alt="Fitness Story" class="story-image">
        <div class="story-content">
            <h2 class="story-title">STORY ABOUT US</h2>
            <p class="story-text">
                KnowBody is more than just a fitness platform - it's your personal companion
                on your journey to a healthier lifestyle. We combine cutting-edge technology
                with expert fitness knowledge to provide you with the tools and guidance you
                need to succeed.
            </p>
            <p class="story-text">
                Whether you're just starting your fitness journey or looking to take your
                workouts to the next level, KnowBody is here to support you every step of
                the way.
            </p>

        </div>
    </section>

    <section id="contact" style="background: #f8f9fa; padding: 5rem 5%; text-align: center;">
        <h2 style="margin-bottom: 2rem; color: #333;">Ready to Start Your Journey?</h2>
        <p style="color: #666; margin-bottom: 2rem;">
            Join thousands of others who have already transformed their lives with KnowBody.
        </p>
        <a href="<?php echo BASE_URL; ?>/feature/register_form.php" class="cta-button">Sign Up Now</a>
    </section>

    <footer style="background: #333; color: white; padding: 3rem 5%; text-align: center;">
        <p>&copy; <?php echo date('Y'); ?> KnowBody. All rights reserved.</p>
    </footer>
</body>

</html>