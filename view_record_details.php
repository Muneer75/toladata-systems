<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid record ID";
    header("Location: view_records.php");
    exit();
}

$id = $_GET['id'];

// Get record details with error handling
try {
    $stmt = $pdo->prepare("
        SELECT 
            sr.*,
            p.project_title,
            p.location,
            p.contact_person,
            p.contact_email,
            p.contact_phone,
            p.budget,
            p.description as project_description,
            u.username as created_by_name,
            u.full_name,
            u.email as user_email
        FROM segregation_records sr
        LEFT JOIN projects p ON sr.project_id = p.id
        LEFT JOIN users u ON sr.created_by = u.id
        WHERE sr.id = ?
    ");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if(!$record) {
        $_SESSION['error'] = "Record not found";
        header("Location: view_records.php");
        exit();
    }

    // Set defaults for missing data
    $project_title = isset($record['project_title']) ? $record['project_title'] : 'Unknown Project (Deleted)';
    $location = isset($record['location']) ? $record['location'] : 'N/A';
    $contact_person = isset($record['contact_person']) ? $record['contact_person'] : 'N/A';
    $contact_email = isset($record['contact_email']) ? $record['contact_email'] : 'N/A';
    $contact_phone = isset($record['contact_phone']) ? $record['contact_phone'] : 'N/A';
    $budget = isset($record['budget']) ? $record['budget'] : 0;
    $project_description = isset($record['project_description']) ? $record['project_description'] : 'No description available';
    $created_by = isset($record['full_name']) ? $record['full_name'] : (isset($record['created_by_name']) ? $record['created_by_name'] : 'Unknown User');
    $user_email = isset($record['user_email']) ? $record['user_email'] : 'N/A';
    
    // Calculate category totals
    $infant_total = $record['infant_male'] + $record['infant_female'];
    $children_total = $record['boys'] + $record['girls'];
    $adult_total = $record['male'] + $record['female'];
    $elder_total = $record['elder_male'] + $record['elder_female'];
    $pwd_total = $record['pwd_male'] + $record['pwd_female'];
    $ewd_total = $record['ewd_male'] + $record['ewd_female'];
    
    // Calculate gender totals
    $male_total = $record['infant_male'] + $record['boys'] + $record['male'] + 
                  $record['elder_male'] + $record['pwd_male'] + $record['ewd_male'];
    $female_total = $record['infant_female'] + $record['girls'] + $record['female'] + 
                    $record['elder_female'] + $record['pwd_female'] + $record['ewd_female'];

} catch(PDOException $e) {
    error_log("Database error in view_record_details: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred";
    header("Location: view_records.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Details - Tolar Base System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Page Header */
        .page-header {
            margin: 100px 0 40px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h2 {
            color: var(--primary-dark);
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h2 i {
            color: var(--primary-teal);
            font-size: 2.5rem;
            background: rgba(43, 122, 120, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .action-header-buttons {
            display: flex;
            gap: 15px;
        }

        .btn-header {
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-edit {
            background: var(--primary-teal);
            color: white;
        }

        .btn-edit:hover {
            background: #1e5f5c;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(43, 122, 120, 0.3);
        }

        .btn-back {
            background: var(--primary-blue);
            color: white;
        }

        .btn-back:hover {
            background: #123456;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 74, 111, 0.3);
        }

        /* Main Content Grid */
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Cards */
        .detail-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        .card-title {
            color: var(--primary-dark);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--primary-teal);
            padding-bottom: 15px;
        }

        .card-title i {
            color: var(--primary-teal);
            font-size: 1.5rem;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: rgba(43, 122, 120, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-teal);
            font-size: 1.2rem;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .info-value {
            color: var(--primary-dark);
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .category-box {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            border: 2px solid #e2e8f0;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .category-header i {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .category-header h4 {
            color: var(--primary-dark);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px;
            background: white;
            border-radius: 8px;
        }

        .category-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed #e2e8f0;
            text-align: center;
            font-weight: 600;
            color: var(--primary-teal);
            font-size: 1.2rem;
        }

        /* Gender colors */
        .male-color { color: #3498db; }
        .female-color { color: #e83e8c; }

        /* Summary Cards */
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-teal), var(--primary-blue));
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Notes Section */
        .notes-section {
            background: #f8fafc;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            border: 2px solid #e2e8f0;
        }

        .notes-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--primary-dark);
        }

        .notes-content {
            color: var(--text-muted);
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .category-grid {
                grid-template-columns: 1fr;
            }

            .summary-stats {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-header-buttons {
                width: 100%;
                flex-direction: column;
            }

            .btn-header {
                width: 100%;
                justify-content: center;
            }
        }

        /* Print Styles */
        @media print {
            .header, .footer, .btn-header {
                display: none;
            }

            body {
                background: white;
            }

            .container {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h2>
                <i class="fas fa-file-alt"></i>
                Record Details
            </h2>
            <div class="action-header-buttons">
                <a href="edit_record.php?id=<?php echo $id; ?>" class="btn-header btn-edit">
                    <i class="fas fa-edit"></i>
                    Edit Record
                </a>
                <a href="view_records.php" class="btn-header btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Records
                </a>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="summary-stats">
            <div class="stat-card">
                <i class="fas fa-calendar"></i>
                <div class="stat-value"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></div>
                <div class="stat-label">Record Date</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-value"><?php echo $record['total_count']; ?></div>
                <div class="stat-label">Total People</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-value">#<?php echo $id; ?></div>
                <div class="stat-label">Record ID</div>
            </div>
        </div>

        <!-- Main Details Grid -->
        <div class="details-grid">
            <!-- Left Column - Project Info -->
            <div>
                <!-- Project Information Card -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="fas fa-project-diagram"></i>
                        Project Information
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Project Title</div>
                                <div class="info-value"><?php echo htmlspecialchars($project_title); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Location</div>
                                <div class="info-value"><?php echo htmlspecialchars($location); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Contact Person</div>
                                <div class="info-value"><?php echo htmlspecialchars($contact_person); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Contact Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($contact_email); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Contact Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($contact_phone); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Budget</div>
                                <div class="info-value">LKR <?php echo number_format($budget, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php if(!empty($project_description) && $project_description != 'No description available'): ?>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <div style="color: var(--text-muted); margin-bottom: 8px;">Project Description</div>
                        <p style="color: var(--primary-dark); line-height: 1.6;"><?php echo nl2br(htmlspecialchars($project_description)); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Segregation Categories -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Segregation Breakdown
                    </div>
                    
                    <div class="category-grid">
                        <!-- Infants -->
                        <div class="category-box">
                            <div class="category-header">
                                <i class="fas fa-baby" style="background: linear-gradient(135deg, #f8bbd0, #f06292); color: #880e4f;"></i>
                                <h4>Infants (0-2 yrs)</h4>
                            </div>
                            <div class="category-stats">
                                <span><i class="fas fa-mars male-color"></i> Male: <?php echo $record['infant_male']; ?></span>
                                <span><i class="fas fa-venus female-color"></i> Female: <?php echo $record['infant_female']; ?></span>
                            </div>
                            <div class="category-total">
                                Total: <?php echo $infant_total; ?>
                            </div>
                        </div>

                        <!-- Children -->
                        <div class="category-box">
                            <div class="category-header">
                                <i class="fas fa-child" style="background: linear-gradient(135deg, #bbdefb, #42a5f5); color: #0d47a1;"></i>
                                <h4>Children (3-12 yrs)</h4>
                            </div>
                            <div class="category-stats">
                                <span><i class="fas fa-mars male-color"></i> Boys: <?php echo $record['boys']; ?></span>
                                <span><i class="fas fa-venus female-color"></i> Girls: <?php echo $record['girls']; ?></span>
                            </div>
                            <div class="category-total">
                                Total: <?php echo $children_total; ?>
                            </div>
                        </div>

                        <!-- Adults -->
                        <div class="category-box">
                            <div class="category-header">
                                <i class="fas fa-user-tie" style="background: linear-gradient(135deg, #c8e6c9, #66bb6a); color: #1b5e20;"></i>
                                <h4>Adults (13-59 yrs)</h4>
                            </div>
                            <div class="category-stats">
                                <span><i class="fas fa-mars male-color"></i> Male: <?php echo $record['male']; ?></span>
                                <span><i class="fas fa-venus female-color"></i> Female: <?php echo $record['female']; ?></span>
                            </div>
                            <div class="category-total">
                                Total: <?php echo $adult_total; ?>
                            </div>
                        </div>

                        <!-- Elderly -->
                        <div class="category-box">
                            <div class="category-header">
                                <i class="fas fa-user-cog" style="background: linear-gradient(135deg, #d1c4e9, #7e57c2); color: #4a148c;"></i>
                                <h4>Elderly (60+ yrs)</h4>
                            </div>
                            <div class="category-stats">
                                <span><i class="fas fa-mars male-color"></i> Male: <?php echo $record['elder_male']; ?></span>
                                <span><i class="fas fa-venus female-color"></i> Female: <?php echo $record['elder_female']; ?></span>
                            </div>
                            <div class="category-total">
                                Total: <?php echo $elder_total; ?>
                            </div>
                        </div>

                        <!-- PWD -->
                        <div class="category-box">
                            <div class="category-header">
                                <i class="fas fa-wheelchair" style="background: linear-gradient(135deg, #ffe0b2, #ff9800); color: #bf360c;"></i>
                                <h4>PWD</h4>
                            </div>
                            <div class="category-stats">
                                <span><i class="fas fa-mars male-color"></i> Male: <?php echo $record['pwd_male']; ?></span>
                                <span><i class="fas fa-venus female-color"></i> Female: <?php echo $record['pwd_female']; ?></span>
                            </div>
                            <div class="category-total">
                                Total: <?php echo $pwd_total; ?>
                            </div>
                        </div>

                        <!-- EWD -->
                        <div class="category-box">
                            <div class="category-header">
                                <i class="fas fa-hand-holding-heart" style="background: linear-gradient(135deg, #ffcdd2, #ef5350); color: #b71c1c;"></i>
                                <h4>EWD</h4>
                            </div>
                            <div class="category-stats">
                                <span><i class="fas fa-mars male-color"></i> Male: <?php echo $record['ewd_male']; ?></span>
                                <span><i class="fas fa-venus female-color"></i> Female: <?php echo $record['ewd_female']; ?></span>
                            </div>
                            <div class="category-total">
                                Total: <?php echo $ewd_total; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Additional Info -->
            <div>
                <!-- Gender Summary -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="fas fa-venus-mars"></i>
                        Gender Summary
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><i class="fas fa-mars male-color"></i> Male Total</span>
                            <span style="font-weight: 600;"><?php echo $male_total; ?></span>
                        </div>
                        <div class="progress-bar" style="height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
                            <?php 
                            $total_gender = $male_total + $female_total;
                            $male_percent = $total_gender > 0 ? round(($male_total / $total_gender) * 100) : 0;
                            ?>
                            <div style="width: <?php echo $male_percent; ?>%; height: 100%; background: #3498db;"></div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><i class="fas fa-venus female-color"></i> Female Total</span>
                            <span style="font-weight: 600;"><?php echo $female_total; ?></span>
                        </div>
                        <div class="progress-bar" style="height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden;">
                            <div style="width: <?php echo 100 - $male_percent; ?>%; height: 100%; background: #e83e8c;"></div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                        <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 10px;">
                            <div style="color: #3498db; font-size: 1.5rem; font-weight: 700;"><?php echo $male_percent; ?>%</div>
                            <div style="color: var(--text-muted); font-size: 0.9rem;">Male</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 10px;">
                            <div style="color: #e83e8c; font-size: 1.5rem; font-weight: 700;"><?php echo 100 - $male_percent; ?>%</div>
                            <div style="color: var(--text-muted); font-size: 0.9rem;">Female</div>
                        </div>
                    </div>
                </div>

                <!-- Record Information -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Record Information
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <div class="info-icon">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Record ID</div>
                            <div class="info-value">#<?php echo $id; ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <div class="info-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Created Date</div>
                            <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($record['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <div class="info-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Created By</div>
                            <div class="info-value"><?php echo htmlspecialchars($created_by); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($user_email); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="edit_record.php?id=<?php echo $id; ?>" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8fafc; border-radius: 10px; text-decoration: none; color: var(--primary-dark); transition: all 0.3s;">
                            <i class="fas fa-edit" style="color: var(--primary-teal);"></i>
                            <span style="flex: 1;">Edit this record</span>
                            <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
                        </a>
                        
                        <a href="add_record.php?project_id=<?php echo $record['project_id']; ?>" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8fafc; border-radius: 10px; text-decoration: none; color: var(--primary-dark); transition: all 0.3s;">
                            <i class="fas fa-plus-circle" style="color: var(--accent-green);"></i>
                            <span style="flex: 1;">Add new record for same project</span>
                            <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
                        </a>
                        
                        <a href="view_records.php?project=<?php echo $record['project_id']; ?>" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8fafc; border-radius: 10px; text-decoration: none; color: var(--primary-dark); transition: all 0.3s;">
                            <i class="fas fa-filter" style="color: var(--primary-blue);"></i>
                            <span style="flex: 1;">View all records for this project</span>
                            <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
                        </a>
                        
                        <button onclick="window.print()" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8fafc; border-radius: 10px; text-decoration: none; color: var(--primary-dark); border: none; width: 100%; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-print" style="color: var(--primary-gold);"></i>
                            <span style="flex: 1;">Print this record</span>
                            <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <?php if(!empty($record['notes'])): ?>
        <div class="notes-section">
            <div class="notes-title">
                <i class="fas fa-sticky-note" style="color: var(--primary-teal); font-size: 1.3rem;"></i>
                <h3 style="color: var(--primary-dark);">Additional Notes</h3>
            </div>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Add print functionality
        document.querySelector('button[onclick="window.print()"]').addEventListener('click', function() {
            window.print();
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>