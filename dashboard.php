<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Get comprehensive statistics
$stats = [];

// Total projects
$stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
$stats['projects'] = $stmt->fetch()['total'];

// Total records
$stmt = $pdo->query("SELECT COUNT(*) as total FROM segregation_records");
$stats['records'] = $stmt->fetch()['total'];

// Total people segregated
$stmt = $pdo->query("SELECT COALESCE(SUM(total_count), 0) as total FROM segregation_records");
$stats['total_people'] = $stmt->fetch()['total'];

// Category totals with gender breakdown
$stmt = $pdo->query("
    SELECT 
        SUM(infant_male) as infant_male,
        SUM(infant_female) as infant_female,
        SUM(boys) as boys,
        SUM(girls) as girls,
        SUM(male) as male,
        SUM(female) as female,
        SUM(elder_male) as elder_male,
        SUM(elder_female) as elder_female,
        SUM(pwd_male) as pwd_male,
        SUM(pwd_female) as pwd_female,
        SUM(ewd_male) as ewd_male,
        SUM(ewd_female) as ewd_female,
        SUM(infant_male + infant_female) as infant_total,
        SUM(boys + girls) as children_total,
        SUM(male + female) as adult_total,
        SUM(elder_male + elder_female) as elder_total,
        SUM(pwd_male + pwd_female) as pwd_total,
        SUM(ewd_male + ewd_female) as ewd_total
    FROM segregation_records
");
$category_totals = $stmt->fetch();

// Default values if no records
$category_totals = array_map(function($value) {
    return $value ?? 0;
}, $category_totals);

// Calculate male/female totals
$male_total = $category_totals['infant_male'] + $category_totals['boys'] + 
              $category_totals['male'] + $category_totals['elder_male'] + 
              $category_totals['pwd_male'] + $category_totals['ewd_male'];

$female_total = $category_totals['infant_female'] + $category_totals['girls'] + 
                $category_totals['female'] + $category_totals['elder_female'] + 
                $category_totals['pwd_female'] + $category_totals['ewd_female'];

// Project-wise statistics
$stmt = $pdo->query("
    SELECT p.project_title, COUNT(sr.id) as record_count, COALESCE(SUM(sr.total_count), 0) as people_count
    FROM projects p
    LEFT JOIN segregation_records sr ON p.id = sr.project_id
    GROUP BY p.id
    ORDER BY people_count DESC
    LIMIT 5
");
$top_projects = $stmt->fetchAll();

// Recent records
$stmt = $pdo->query("
    SELECT sr.*, p.project_title 
    FROM segregation_records sr 
    JOIN projects p ON sr.project_id = p.id 
    ORDER BY sr.created_at DESC 
    LIMIT 5
");
$recent_records = $stmt->fetchAll();

// Get username safely
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : $username;


// Set timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tolar Base System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0a1929;
            --primary-blue: #1e4a6f;
            --primary-teal: #2b7a78;
            --primary-gold: #c9a227;
            --accent-red: #d64933;
            --accent-green: #2e7d32;
            --text-muted: #64748b;
            --bg-card: #ffffff;
            --shadow: 0 10px 30px -10px rgba(0,0,0,0.15);
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-blue) 100%);
            padding: 40px;
            border-radius: 25px;
            margin: 100px 0 40px 0;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-text {
            color: white;
        }

        .welcome-text h2 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome-text h2 i {
            color: var(--primary-gold);
            font-size: 2.5rem;
        }

        .welcome-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-badge {
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .date-badge i {
            font-size: 2rem;
            color: var(--primary-gold);
        }

        .date-badge .date {
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Main Statistics Cards */
        .main-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 40px 0;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-teal), var(--primary-blue));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-content .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            line-height: 1.2;
        }

        .stat-content .label {
            color: var(--text-muted);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .stat-content .sub-label {
            color: var(--primary-teal);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Gender Distribution Card */
        .gender-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin: 30px 0;
        }

        .gender-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .gender-header i {
            font-size: 2rem;
            color: var(--primary-teal);
            background: rgba(43, 122, 120, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .gender-header h3 {
            color: var(--primary-dark);
            font-size: 1.5rem;
        }

        .gender-bars {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .gender-bar-item {
            flex: 1;
        }

        .gender-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .gender-label i {
            font-size: 1.2rem;
        }

        .gender-label .male { color: #3498db; }
        .gender-label .female { color: #e83e8c; }

        .progress-bar {
            height: 30px;
            background: #e2e8f0;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-teal), var(--primary-blue));
            color: white;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 15px;
            font-weight: 600;
        }

        .gender-total {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Category Cards */
        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 40px 0 25px;
        }

        .section-title i {
            font-size: 2rem;
            color: var(--primary-teal);
            background: rgba(43, 122, 120, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .section-title h3 {
            color: var(--primary-dark);
            font-size: 1.8rem;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .category-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Category-specific icon colors */
        .category-card:nth-child(1) .category-icon { background: linear-gradient(135deg, #f8bbd0, #f06292); color: #880e4f; }
        .category-card:nth-child(2) .category-icon { background: linear-gradient(135deg, #bbdefb, #42a5f5); color: #0d47a1; }
        .category-card:nth-child(3) .category-icon { background: linear-gradient(135deg, #c8e6c9, #66bb6a); color: #1b5e20; }
        .category-card:nth-child(4) .category-icon { background: linear-gradient(135deg, #d1c4e9, #7e57c2); color: #4a148c; }
        .category-card:nth-child(5) .category-icon { background: linear-gradient(135deg, #ffe0b2, #ff9800); color: #bf360c; }
        .category-card:nth-child(6) .category-icon { background: linear-gradient(135deg, #ffcdd2, #ef5350); color: #b71c1c; }

        .category-header h4 {
            color: var(--primary-dark);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .category-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 15px 0;
        }

        .gender-split {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .gender-split-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .gender-split-item .male { color: #3498db; }
        .gender-split-item .female { color: #e83e8c; }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-teal);
            color: white;
        }

        .btn-primary:hover {
            background: #1e5f5c;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(43, 122, 120, 0.3);
        }

        .btn-success {
            background: var(--accent-green);
            color: white;
        }

        .btn-success:hover {
            background: #1e5f2e;
            transform: translateY(-3px);
        }

        .btn-info {
            background: var(--primary-blue);
            color: white;
        }

        .btn-info:hover {
            background: #123456;
            transform: translateY(-3px);
        }

        /* Project Stats */
        .project-stats {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin: 30px 0;
        }

        .project-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .project-header i {
            font-size: 2rem;
            color: var(--primary-gold);
            background: rgba(201, 162, 39, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .project-header h3 {
            color: var(--primary-dark);
            font-size: 1.5rem;
        }

        /* Recent Records Table */
        .recent-records {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin: 40px 0;
        }

        .records-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .records-header i {
            font-size: 2rem;
            color: var(--primary-teal);
            background: rgba(43, 122, 120, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .records-header h3 {
            color: var(--primary-dark);
            font-size: 1.5rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--primary-dark);
            color: white;
            padding: 15px;
            font-weight: 600;
            text-align: left;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .total-badge {
            background: var(--primary-teal);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px;
            background: var(--bg-card);
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .empty-state a {
            color: var(--primary-teal);
            text-decoration: none;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-stats,
            .category-grid {
                grid-template-columns: 1fr;
            }

            .welcome-banner {
                flex-direction: column;
                text-align: center;
                padding: 30px;
            }

            .welcome-text h2 {
                font-size: 1.8rem;
            }

            .gender-bars {
                flex-direction: column;
                gap: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Welcome Banner with Icons -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>
                    <i class="fas fa-hand-wave"></i>
                    Welcome, <?php echo $full_name; ?>!
                </h2>
                <p>
                    <i class="fas fa-chart-line"></i>
                    Here's what's happening with your projects today
                </p>
            </div>
            <div class="date-badge">
                <i class="fas fa-calendar-check"></i>
                <div class="date">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Main Statistics Cards with Icons -->
        <div class="main-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="stat-content">
                    <div class="label">TOTAL PROJECTS</div>
                    <div class="number"><?php echo $stats['projects']; ?></div>
                    <div class="sub-label">
                        <i class="fas fa-rocket"></i>
                        Active initiatives
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-content">
                    <div class="label">TOTAL RECORDS</div>
                    <div class="number"><?php echo $stats['records']; ?></div>
                    <div class="sub-label">
                        <i class="fas fa-chart-bar"></i>
                        Segregation entries
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="label">PEOPLE SERVED</div>
                    <div class="number"><?php echo number_format($stats['total_people']); ?></div>
                    <div class="sub-label">
                        <i class="fas fa-heart"></i>
                        Total beneficiaries
                    </div>
                </div>
            </div>
        </div>

        <!-- Gender Distribution Card -->
        <div class="gender-card">
            <div class="gender-header">
                <i class="fas fa-venus-mars"></i>
                <h3>Gender Distribution</h3>
            </div>
            <div class="gender-bars">
                <div class="gender-bar-item">
                    <div class="gender-label">
                        <i class="fas fa-mars male"></i>
                        <span>Male</span>
                    </div>
                    <div class="progress-bar">
                        <?php 
                        $total = $male_total + $female_total;
                        $male_percent = $total > 0 ? round(($male_total / $total) * 100) : 0;
                        ?>
                        <div class="progress-fill" style="width: <?php echo $male_percent; ?>%">
                            <?php echo $male_percent; ?>%
                        </div>
                    </div>
                    <div class="gender-total">
                        <span><i class="fas fa-mars"></i> <?php echo number_format($male_total); ?></span>
                    </div>
                </div>
                <div class="gender-bar-item">
                    <div class="gender-label">
                        <i class="fas fa-venus female"></i>
                        <span>Female</span>
                    </div>
                    <div class="progress-bar">
                        <?php $female_percent = 100 - $male_percent; ?>
                        <div class="progress-fill" style="width: <?php echo $female_percent; ?>%">
                            <?php echo $female_percent; ?>%
                        </div>
                    </div>
                    <div class="gender-total">
                        <span><i class="fas fa-venus"></i> <?php echo number_format($female_total); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="section-title">
            <i class="fas fa-chart-pie"></i>
            <h3>Category Summary</h3>
        </div>

        <!-- Category Cards with Icons -->
        <div class="category-grid">
            <!-- Infant Card -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-baby"></i>
                    </div>
                    <h4>Infants (0-2 yrs)</h4>
                </div>
                <div class="category-number"><?php echo number_format($category_totals['infant_total']); ?></div>
                <div class="gender-split">
                    <div class="gender-split-item">
                        <i class="fas fa-mars male"></i>
                        <span><?php echo number_format($category_totals['infant_male']); ?></span>
                    </div>
                    <div class="gender-split-item">
                        <i class="fas fa-venus female"></i>
                        <span><?php echo number_format($category_totals['infant_female']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Children Card -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h4>Children (3-12 yrs)</h4>
                </div>
                <div class="category-number"><?php echo number_format($category_totals['children_total']); ?></div>
                <div class="gender-split">
                    <div class="gender-split-item">
                        <i class="fas fa-mars male"></i>
                        <span><?php echo number_format($category_totals['boys']); ?></span>
                    </div>
                    <div class="gender-split-item">
                        <i class="fas fa-venus female"></i>
                        <span><?php echo number_format($category_totals['girls']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Adults Card -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h4>Adults (13-59 yrs)</h4>
                </div>
                <div class="category-number"><?php echo number_format($category_totals['adult_total']); ?></div>
                <div class="gender-split">
                    <div class="gender-split-item">
                        <i class="fas fa-mars male"></i>
                        <span><?php echo number_format($category_totals['male']); ?></span>
                    </div>
                    <div class="gender-split-item">
                        <i class="fas fa-venus female"></i>
                        <span><?php echo number_format($category_totals['female']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Elderly Card -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h4>Elderly (60+ yrs)</h4>
                </div>
                <div class="category-number"><?php echo number_format($category_totals['elder_total']); ?></div>
                <div class="gender-split">
                    <div class="gender-split-item">
                        <i class="fas fa-mars male"></i>
                        <span><?php echo number_format($category_totals['elder_male']); ?></span>
                    </div>
                    <div class="gender-split-item">
                        <i class="fas fa-venus female"></i>
                        <span><?php echo number_format($category_totals['elder_female']); ?></span>
                    </div>
                </div>
            </div>

            <!-- PWD Card -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-wheelchair"></i>
                    </div>
                    <h4>PWD</h4>
                </div>
                <div class="category-number"><?php echo number_format($category_totals['pwd_total']); ?></div>
                <div class="gender-split">
                    <div class="gender-split-item">
                        <i class="fas fa-mars male"></i>
                        <span><?php echo number_format($category_totals['pwd_male']); ?></span>
                    </div>
                    <div class="gender-split-item">
                        <i class="fas fa-venus female"></i>
                        <span><?php echo number_format($category_totals['pwd_female']); ?></span>
                    </div>
                </div>
            </div>

            <!-- EWD Card -->
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h4>EWD</h4>
                </div>
                <div class="category-number"><?php echo number_format($category_totals['ewd_total']); ?></div>
                <div class="gender-split">
                    <div class="gender-split-item">
                        <i class="fas fa-mars male"></i>
                        <span><?php echo number_format($category_totals['ewd_male']); ?></span>
                    </div>
                    <div class="gender-split-item">
                        <i class="fas fa-venus female"></i>
                        <span><?php echo number_format($category_totals['ewd_female']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons with Icons -->
        <div class="action-buttons">
            <a href="add_record.php" class="action-btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                Add New Record
            </a>
            <a href="view_records.php" class="action-btn btn-success">
                <i class="fas fa-table"></i>
                View All Records
            </a>
            <a href="manage_projects.php" class="action-btn btn-info">
                <i class="fas fa-project-diagram"></i>
                Manage Projects
            </a>
        </div>

        <!-- Top Projects -->
        <?php if(!empty($top_projects)): ?>
        <div class="project-stats">
            <div class="project-header">
                <i class="fas fa-trophy"></i>
                <h3>Top Projects by Beneficiaries</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-project-diagram"></i> Project</th>
                        <th><i class="fas fa-database"></i> Records</th>
                        <th><i class="fas fa-users"></i> People Served</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($top_projects as $project): ?>
                    <tr>
                        <td>
                            <i class="fas fa-folder-open" style="color: var(--primary-teal);"></i>
                            <?php echo htmlspecialchars($project['project_title']); ?>
                        </td>
                        <td>
                            <i class="fas fa-file-alt" style="color: var(--primary-gold);"></i>
                            <?php echo $project['record_count']; ?>
                        </td>
                        <td>
                            <span class="total-badge">
                                <i class="fas fa-users"></i>
                                <?php echo number_format($project['people_count']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Recent Records -->
        <div class="recent-records">
            <div class="records-header">
                <i class="fas fa-clock-rotate-left"></i>
                <h3>Recent Segregation Records</h3>
            </div>
            
            <?php if(empty($recent_records)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No records found. <a href="add_record.php">Add your first record</a></p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-project-diagram"></i> Project</th>
                            <th><i class="fas fa-baby"></i> Infant</th>
                            <th><i class="fas fa-child"></i> Children</th>
                            <th><i class="fas fa-user-tie"></i> Adult</th>
                            <th><i class="fas fa-user-cog"></i> Elder</th>
                            <th><i class="fas fa-wheelchair"></i> PWD</th>
                            <th><i class="fas fa-hand-holding-heart"></i> EWD</th>
                            <th><i class="fas fa-calculator"></i> Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_records as $record): ?>
                        <tr>
                            <td><i class="fas fa-calendar-alt" style="color: var(--primary-teal);"></i> <?php echo date('Y-m-d', strtotime($record['record_date'])); ?></td>
                            <td><i class="fas fa-folder" style="color: var(--primary-gold);"></i> <?php echo htmlspecialchars($record['project_title']); ?></td>
                            <td><?php echo $record['infant_male'] + $record['infant_female']; ?></td>
                            <td><?php echo $record['boys'] + $record['girls']; ?></td>
                            <td><?php echo $record['male'] + $record['female']; ?></td>
                            <td><?php echo $record['elder_male'] + $record['elder_female']; ?></td>
                            <td><?php echo $record['pwd_male'] + $record['pwd_female']; ?></td>
                            <td><?php echo $record['ewd_male'] + $record['ewd_female']; ?></td>
                            <td><span class="total-badge"><i class="fas fa-users"></i> <?php echo $record['total_count']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>