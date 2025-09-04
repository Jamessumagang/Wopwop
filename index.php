<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RS BUILDERS PMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Inter:wght@400;600&family=Poppins:wght@400;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: url('./images/background.webp') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', 'Poppins', 'Montserrat', Arial, sans-serif;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(16, 134, 249, 0.65);
            z-index: 0;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2.2rem 3.5vw 1.2rem 3.5vw;
            position: relative;
            z-index: 1;
        }
        .logo {
            font-family: 'Russo One', 'Inter', Arial, sans-serif;
            font-size: 2rem;
            color: #fff;
            letter-spacing: 2px;
            text-shadow: 2px 2px 0 #0a5ec4;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .logo-img {
            height: 38px;
            width: auto;
            border-radius: 4px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            display: inline-block;
        }
        .nav-links {
            display: flex;
            gap: 2.5rem;
        }
        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 1px;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: #e0e7ff;
        }
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            margin-top: 2rem;
            position: relative;
            z-index: 1;
        }
        .main-content img {
            max-width: 380px;
            width: 100%;
            border-radius: 4px;
            margin-bottom: 2.2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            background: #fff;
        }
        .welcome {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
        }
        .system-name {
            font-family: 'Russo One', 'Inter', Arial, sans-serif;
            color: #fff;
            font-size: 2.1rem;
            font-weight: 900;
            margin-bottom: 1.2rem;
            letter-spacing: 1.5px;
        }
        .info {
            color: #e0e7ff;
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
        }
        .note {
            color: #fff;
            font-size: 1rem;
            margin-top: 0.7rem;
        }
        @media (max-width: 700px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.2rem 1.2rem 0.7rem 1.2rem;
            }
            .logo {
                font-size: 1.3rem;
                gap: 0.4rem;
            }
            .logo-img {
                height: 28px;
            }
            .nav-links {
                gap: 1.2rem;
                margin-top: 0.7rem;
            }
            .main-content img {
                max-width: 90vw;
            }
            .system-name {
                font-size: 1.2rem;
            }
        }
    </style>
    <style>
        /* Modernized About Us & Services Section */
        .modern-section {
            background: linear-gradient(135deg, #f7fbff 0%, #e3f0ff 100%);
            margin: 2.5rem auto 0 auto;
            max-width: 720px;
            border-radius: 22px;
            box-shadow: 0 6px 32px rgba(16,134,249,0.13), 0 2px 12px rgba(0,0,0,0.07);
            padding: 3rem 2.5rem 2.5rem 2.5rem;
            position: relative;
            z-index: 2;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
            margin-top:200px;
            font-family: 'Poppins', 'Montserrat', 'Inter', Arial, sans-serif;
        }
        .modern-section:hover {
            box-shadow: 0 12px 48px rgba(16,134,249,0.18), 0 4px 18px rgba(0,0,0,0.10);
            transform: translateY(-2px) scale(1.012);
        }
        .modern-section h2 {
            font-family: 'Montserrat', 'Russo One', 'Poppins', Arial, sans-serif;
            color: #0a5ec4;
            font-size: 2.5rem;
            margin-bottom: 1.2rem;
            letter-spacing: 1.7px;
            text-align: center;
            text-shadow: 0 2px 12px rgba(16,134,249,0.09);
            font-weight: 700;
        }
        .modern-section h3 {
            color: #1086f9;
            margin-top: 1.8rem;
            margin-bottom: 0.6rem;
            font-size: 1.35rem;
            font-weight: 600;
            letter-spacing: 0.7px;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
        }
        .modern-section p, .modern-section .services-desc {
            color: #1a2330;
            font-size: 1.18rem;
            margin-bottom: 1.15rem;
            line-height: 1.85;
            text-align: center;
            font-family: 'Poppins', 'Inter', Arial, sans-serif;
        }
        .modern-section ul {
            color: #1a2330;
            margin-bottom: 1.3rem;
            padding-left: 0;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem 1.5rem;
            justify-content: center;
        }
        .modern-section ul li {
            position: relative;
            padding-left: 2.3rem;
            margin-bottom: 0.5rem;
            font-size: 1.09rem;
            min-width: 260px;
            text-align: left;
            font-family: 'Poppins', Arial, sans-serif;
        }
        .modern-section ul li:before {
            content: '\2714'; /* ✔ */
            position: absolute;
            left: 0;
            top: 0.1rem;
            font-size: 1.3rem;
            color: #0a5ec4;
            opacity: 0.92;
        }
        .modern-section .about-highlight {
            color: #1086f9;
            font-weight: 700;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
        }
        .modern-section .about-cta {
            color: #fff;
            background: linear-gradient(90deg, #1086f9 60%, #0a5ec4 100%);
            border-radius: 12px;
            padding: 1.1rem 1.7rem;
            font-weight: 700;
            margin-top: 2.2rem;
            text-align: center;
            box-shadow: 0 2px 12px rgba(16,134,249,0.10);
            display: inline-block;
            font-size: 1.18rem;
            letter-spacing: 0.5px;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
        }
        @media (max-width: 700px) {
            .modern-section {
                padding: 1.2rem 0.7rem 1.2rem 0.7rem;
                max-width: 98vw;
            }
            .modern-section h2 {
                font-size: 1.5rem;
            }
            .modern-section ul li {
                min-width: 160px;
                font-size: 0.98rem;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show notification if sent=1 or sent=fail in URL
            if (window.location.search.indexOf('sent=1') !== -1) {
                var notif = document.getElementById('notif-success');
                notif.style.display = 'block';
                notif.style.opacity = '1';
                setTimeout(function() {
                    notif.style.opacity = '0';
                    setTimeout(function() { notif.style.display = 'none'; }, 500);
                }, 3000);
                if (window.history.replaceState) {
                    const url = window.location.protocol + '//' + window.location.host + window.location.pathname;
                    window.history.replaceState({}, document.title, url);
                }
            } else if (window.location.search.indexOf('sent=fail') !== -1) {
                var notif = document.getElementById('notif-fail');
                notif.style.display = 'block';
                notif.style.opacity = '1';
                setTimeout(function() {
                    notif.style.opacity = '0';
                    setTimeout(function() { notif.style.display = 'none'; }, 500);
                }, 3000);
                if (window.history.replaceState) {
                    const url = window.location.protocol + '//' + window.location.host + window.location.pathname;
                    window.history.replaceState({}, document.title, url);
                }
            }
            const aboutLink = document.querySelector('a[href="#about-us"]');
            const servicesLink = document.querySelector('a[href="#services"]');
            const mainContent = document.querySelector('.main-content');
            const aboutSection = document.getElementById('about-us');
            const servicesSection = document.getElementById('services');
        

            // Create a Home link
            function createHomeLink() {
                let homeLink = document.getElementById('show-home-link');
                if (!homeLink) {
                    homeLink = document.createElement('a');
                    homeLink.id = 'show-home-link';
                    homeLink.textContent = 'Show Home';
                    homeLink.href = 'index.php'; // Reloads the homepage
                    homeLink.style = 'display:block;margin:1.5rem auto 0 auto;text-align:center;font-weight:600;color:#0a5ec4;font-size:1.1rem;text-decoration:underline;cursor:pointer;';
                }
                homeLink.style.display = 'block';
                // Remove the old onclick handler if it exists
                homeLink.onclick = null;
            }

            aboutLink.addEventListener('click', function(e) {
                e.preventDefault();
                mainContent.style.display = 'none';
                aboutSection.style.display = '';
                servicesSection.style.display = 'none';
                createHomeLink();
                window.scrollTo({top: aboutSection.offsetTop - 30, behavior: 'smooth'});
            });
            servicesLink.addEventListener('click', function(e) {
                e.preventDefault();
                mainContent.style.display = 'none';
                aboutSection.style.display = 'none';
                servicesSection.style.display = '';
                createHomeLink();
                window.scrollTo({top: servicesSection.offsetTop - 30, behavior: 'smooth'});
            });
        });
    </script>
</head>
<body>
    <div id="notif-success" style="display:none;position:fixed;top:30px;right:30px;left:auto;transform:none;background:#22c55e;color:#fff;padding:1rem 2.2rem;border-radius:8px;box-shadow:0 2px 12px rgba(34,197,94,0.10);font-size:1.13rem;z-index:9999;transition:opacity 0.5s;">Send message successfully!</div>
    <div id="notif-fail" style="display:none;position:fixed;top:30px;right:30px;left:auto;transform:none;background:#ef4444;color:#fff;padding:1rem 2.2rem;border-radius:8px;box-shadow:0 2px 12px rgba(239,68,68,0.10);font-size:1.13rem;z-index:9999;transition:opacity 0.5s;">Failed to send message. Please try again later.</div>
    <div class="navbar">
        <div class="logo">
            <img src="./images/rsrs.jpeg" alt="RS Builders Logo" class="logo-img"> RS BUILDERS PMS
        </div>
        <div class="nav-links">
            <a href="index.php">HOME</a>
            <a href="#about-us">ABOUT US</a>
            <a href="#services">SERVICES</a>
            <a href="client_login.php">LOG-IN</a>
        </div>
    </div>
    <div class="main-content">
        <img src="./images/rsrs.jpeg" alt="RS Builders Logo">
        <div class="welcome">WELCOME TO</div>
        <div class="system-name">RS BUILDERS PMS &nbsp; (PROJECT MANAGEMENT SYSTEM)</div>
        <div class="info">Open hours of RS BUILDERS : Monday - Saturday &nbsp; (8.AM-6PM)</div>
        <div class="note">if you don’t have an account yet just send the email to (RSBUILDERS@gmail.com)</div>
    </div>
    <!-- Contact info section removed -->
    <section id="about-us" class="modern-section">
        <h2>About Us</h2>
        <p>At <span class="about-highlight">RS BUILDERS</span>, we specialize in empowering construction projects through smart project management solutions. Whether building homes, commercial spaces, or large-scale developments, our Construction Project Management System helps streamline every phase from planning and budgeting to scheduling, monitoring, and delivery.</p>
        <p>With a deep understanding of the construction industry, our mission is to help project managers, engineers, and builders stay on track, reduce costs, and ensure every structure is built to the highest standards on time and within budget. We bridge the gap between complexity and clarity, making construction projects easier to manage, track, and complete.</p>
        <h3>Our Mission</h3>
        <p>To simplify and optimize the way construction projects are planned, managed, and completed—helping project managers, builders, and engineers deliver quality structures on time.</p>
        <h3>Our Vision</h3>
        <p>To become the leading digital solution in construction project management, enabling smarter, faster, and more efficient building processes across the industry.</p>
        <h3>Why Choose Us</h3>
        <ul>
            <li><strong>User-Friendly Interface:</strong> Simplifies complex tasks with intuitive dashboards.</li>
            <li><strong>Real-Time Monitoring:</strong> Stay updated with progress through live dashboards and reports.</li>
            <li><strong>Data-Driven Decisions:</strong> Provides insights and analytics for better project outcomes.</li>
            <li><strong>Collaboration made Easy:</strong> Seamless communication between project owners, managers, and teams.</li>
            <li><strong>Industry Expertise: </strong> Built by experts who understand the challenges of construction projects.</li>
        </ul>
        <div class="about-cta">Join us in building the future of construction smarter, faster, and more efficiently.</div>
    </section>
    <section id="services" class="modern-section">
        <h2>Our Services</h2>
        <p class="services-desc">We provide an all-in-one Construction Project Management System that streamlines planning, budgeting, resource management, and real-time collaboration to ensure projects are completed on time and within budget.</p>
    </section>
</body>
</html> 