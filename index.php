<?php
require_once 'config/database.php';

// Get summary statistics
$stats = [];

// Total people segregated
$stmt = $pdo->query("SELECT COALESCE(SUM(total_count), 0) as total FROM segregation_records");
$stats['total_people'] = $stmt->fetch()['total'];

// Category totals
$stmt = $pdo->query("
    SELECT 
        SUM(infant_male + infant_female) as infant_total,
        SUM(boys + girls) as children_total,
        SUM(male + female) as adult_total,
        SUM(elder_male + elder_female) as elder_total,
        SUM(pwd_male + pwd_female) as pwd_total,
        SUM(ewd_male + ewd_female) as ewd_total
    FROM segregation_records
");
$category_totals = $stmt->fetch();

// Default values if no records exist
$category_totals = array_map(function($value) {
    return $value ?? 0;
}, $category_totals);

// Total projects
$stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
$stats['total_projects'] = $stmt->fetch()['total'];

// Total records
$stmt = $pdo->query("SELECT COUNT(*) as total FROM segregation_records");
$stats['total_records'] = $stmt->fetch()['total'];

// Set timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tolar Base System - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Professional Color Palette */
        :root {
            --primary-dark: #0a1929;
            --primary-blue: #1e4a6f;
            --primary-teal: #2b7a78;
            --primary-gold: #c9a227;
            --accent-orange: #f5923e;
            --accent-red: #d64933;
            --accent-green: #2e7d32;
            --accent-purple: #6a1b9a;
            --text-dark: #1e293b;
            --text-light: #f8fafc;
            --text-muted: #64748b;
            --bg-light: #f1f5f9;
            --bg-card: #ffffff;
            --shadow: 0 10px 30px -10px rgba(0,0,0,0.15);
            --shadow-hover: 0 20px 40px -15px rgba(0,0,0,0.2);
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .header {
            background: var(--primary-dark);
            box-shadow: var(--shadow);
            border-bottom: 3px solid var(--primary-gold);
        }

        .header h1 {
            color: var(--primary-gold);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .header h1 i {
            margin-right: 10px;
            color: var(--primary-teal);
        }

        .nav-menu a {
            color: var(--text-light);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .nav-menu a:hover {
            background: var(--primary-teal);
            color: white;
            transform: translateY(-2px);
        }

        .nav-menu a i {
            margin-right: 6px;
            font-size: 0.9rem;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: center;
            padding: 60px 30px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-blue) 100%);
            border-radius: 24px;
            margin: 20px 0 40px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .welcome-section h1 {
            color: white;
            font-size: 3.2rem;
            margin-bottom: 15px;
            font-weight: 700;
            position: relative;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .welcome-section h1 i {
            color: var(--primary-gold);
            margin: 0 15px;
        }

        .welcome-section p {
            color: rgba(255,255,255,0.9);
            font-size: 1.3rem;
            margin-bottom: 30px;
            position: relative;
        }

        .welcome-section .btn {
            background: var(--primary-gold);
            color: var(--primary-dark);
            font-weight: 600;
            padding: 14px 35px;
            border-radius: 50px;
            font-size: 1.1rem;
            box-shadow: 0 5px 20px rgba(201, 162, 39, 0.3);
        }

        .welcome-section .btn:hover {
            background: #dbb042;
            transform: translateY(-3px);
        }

        .welcome-section .btn i {
            margin-right: 8px;
        }

        /* Main Stats Cards */
        .main-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 40px 0;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 30px 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-teal), var(--primary-gold));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-teal));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        .stat-card .number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-dark);
            line-height: 1.2;
        }

        .stat-card .label {
            color: var(--text-muted);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin: 10px 0 5px;
        }

        .stat-card .sub-label {
            color: var(--primary-teal);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-card .sub-label i {
            margin-right: 5px;
        }

        /* Category Cards Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 40px 0;
        }

        .category-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 15px;
        }

        /* Category-specific colors */
        .category-card:nth-child(1) .category-icon { background: linear-gradient(135deg, #f8bbd0, #f06292); color: #880e4f; }
        .category-card:nth-child(2) .category-icon { background: linear-gradient(135deg, #bbdefb, #42a5f5); color: #0d47a1; }
        .category-card:nth-child(3) .category-icon { background: linear-gradient(135deg, #c8e6c9, #66bb6a); color: #1b5e20; }
        .category-card:nth-child(4) .category-icon { background: linear-gradient(135deg, #d1c4e9, #7e57c2); color: #4a148c; }
        .category-card:nth-child(5) .category-icon { background: linear-gradient(135deg, #ffe0b2, #ff9800); color: #bf360c; }
        .category-card:nth-child(6) .category-icon { background: linear-gradient(135deg, #ffcdd2, #ef5350); color: #b71c1c; }

        .category-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
        }

        .category-card .number {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 15px 0 10px;
            line-height: 1;
        }

        .category-card .description {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .category-card .description i {
            color: var(--primary-teal);
            margin-right: 5px;
            font-size: 0.8rem;
        }

        /* Summary Section */
        .summary-section {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin: 40px 0;
        }

        .summary-section h2 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .summary-section h2 i {
            background: var(--primary-teal);
            color: white;
            padding: 10px;
            border-radius: 12px;
            margin-right: 15px;
            font-size: 1.4rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }

        .summary-item {
            text-align: center;
            padding: 20px 15px;
            background: var(--bg-light);
            border-radius: 16px;
            transition: all 0.3s;
        }

        .summary-item:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-teal));
        }

        .summary-item:hover .category,
        .summary-item:hover .value {
            color: white;
        }

        .summary-item .category {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .summary-item .value {
            color: var(--primary-dark);
            font-size: 2rem;
            font-weight: 700;
        }

        /* Weather Widget */
        .weather-widget {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            padding: 20px 30px;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
            color: white;
            box-shadow: var(--shadow);
        }

        .weather-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .weather-icon {
            font-size: 2.5rem;
            filter: drop-shadow(2px 4px 6px rgba(0,0,0,0.2));
        }

        .weather-temp {
            font-size: 2rem;
            font-weight: 700;
            margin-right: 10px;
        }

        .weather-desc {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .weather-time {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 50px;
        }

        .weather-time i {
            font-size: 1.2rem;
            color: var(--primary-gold);
        }

        /* Footer */
        .footer {
            background: var(--primary-dark);
            color: white;
            border-top: 3px solid var(--primary-gold);
        }

        .footer p {
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-stats,
            .category-grid,
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .welcome-section h1 {
                font-size: 2rem;
            }
            
            .welcome-section h1 i {
                margin: 0 5px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>
                <i class="fas fa-chart-pie"></i>
                Welcome to TolaData System
                <i class="fas fa-chart-line"></i>
            </h1>
            <p><i class="fas fa-quote-left"></i> Efficient segregation of work and resources for development <i class="fas fa-quote-right"></i></p>
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" class="btn"><i class="fas fa-user-plus"></i> Register</a>
            <?php else: ?>
                <li><a href="../tolar-base-system/dashboard.php"></a><a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Go to Dashboard</a>
            <?php endif; ?>
        </div>

        <!-- Main Statistics Cards -->
        <div class="main-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="number"><?php echo $stats['total_projects']; ?></div>
                <div class="label">TOTAL PROJECTS</div>
                <div class="sub-label"><i class="fas fa-rocket"></i> Active initiatives</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="number"><?php echo $stats['total_records']; ?></div>
                <div class="label">TOTAL RECORDS</div>
                <div class="sub-label"><i class="fas fa-chart-bar"></i> Segregation entries</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="number"><?php echo number_format($stats['total_people']); ?></div>
                <div class="label">PEOPLE SERVED</div>
                <div class="sub-label"><i class="fas fa-heart"></i> Total beneficiaries</div>
            </div>
        </div>

        <!-- Category Cards -->
        <div class="category-grid">
            <!-- Infant Care -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-baby"></i>
                    </div>
                    <h3>Infant Care</h3>
                </div>
                <div class="number"><?php echo number_format($category_totals['infant_total']); ?></div>
                <div class="description">
                    <i class="fas fa-clock"></i> Specialized care for infants (0-2 years)
                </div>
            </div>
            
            <!-- Children -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3>Children</h3>
                </div>
                <div class="number"><?php echo number_format($category_totals['children_total']); ?></div>
                <div class="description">
                    <i class="fas fa-futbol"></i> Boys and girls (3-12 years)
                </div>
            </div>
            
            <!-- Adults -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Adults</h3>
                </div>
                <div class="number"><?php echo number_format($category_totals['adult_total']); ?></div>
                <div class="description">
                    <i class="fas fa-briefcase"></i> Male and Female (13-59 years)
                </div>
            </div>
            
            <!-- Elderly -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>Elderly</h3>
                </div>
                <div class="number"><?php echo number_format($category_totals['elder_total']); ?></div>
                <div class="description">
                    <i class="fas fa-heartbeat"></i> Senior citizens (60+ years)
                </div>
            </div>
            
            <!-- PWD -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-wheelchair"></i>
                    </div>
                    <h3>PWD</h3>
                </div>
                <div class="number"><?php echo number_format($category_totals['pwd_total']); ?></div>
                <div class="description">
                    <i class="fas fa-hands"></i> Persons with Disabilities
                </div>
            </div>
            
            <!-- EWD -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h3>EWD</h3>
                </div>
                <div class="number"><?php echo number_format($category_totals['ewd_total']); ?></div>
                <div class="description">
                    <i class="fas fa-seedling"></i> Economically Weaker Section
                </div>
            </div>
        </div>

        <!-- Category Summary Section -->
        <div class="summary-section">
            <h2>
                <i class="fas fa-chart-simple"></i>
                Category Summary
            </h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="category">Infants</div>
                    <div class="value"><?php echo number_format($category_totals['infant_total']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="category">Children</div>
                    <div class="value"><?php echo number_format($category_totals['children_total']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="category">Adults</div>
                    <div class="value"><?php echo number_format($category_totals['adult_total']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="category">Elderly</div>
                    <div class="value"><?php echo number_format($category_totals['elder_total']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="category">PWD</div>
                    <div class="value"><?php echo number_format($category_totals['pwd_total']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="category">EWD</div>
                    <div class="value"><?php echo number_format($category_totals['ewd_total']); ?></div>
                </div>
            </div>
        </div>

        <!-- Weather Widget -->
        <div class="weather-widget">
            <div class="weather-info">
                <div class="weather-icon">
                    <i class="fas fa-cloud-sun"></i>
                </div>
                <div>
                    <span class="weather-temp">28°C</span>
                    <span class="weather-desc">Mostly cloudy</span>
                </div>                          
    </div>
    <div class="weather-time">
        <i class="fas fa-map-marker-alt"></i>
        <span>Colombo, Sri Lanka</span>
        <i class="fas fa-clock"></i>
        <span><?php echo date('h:i A'); ?></span>
        <span style="margin-left: 12px;"><i class="fas fa-calendar-check"></i> <?php echo date('l, F j, Y'); ?></span>
    </div>
</div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>