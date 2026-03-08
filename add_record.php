<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$success = '';
$error = '';

// Get projects for dropdown with additional details
$stmt = $pdo->query("
    SELECT id, project_title, location, contact_person, budget,
           (SELECT COUNT(*) FROM segregation_records WHERE project_id = projects.id) as record_count 
    FROM projects 
    ORDER BY project_title
");
$projects = $stmt->fetchAll();

// Get selected project details for display
$selected_project = null;
if(isset($_POST['project_id']) || isset($_GET['project_id'])) {
    $project_id = isset($_POST['project_id']) ? $_POST['project_id'] : $_GET['project_id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $selected_project = $stmt->fetch();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = $_POST['project_id'];
    $record_date = $_POST['record_date'];
    
    // Segregation data
    $infant_male = intval($_POST['infant_male'] ?? 0);
    $infant_female = intval($_POST['infant_female'] ?? 0);
    $boys = intval($_POST['boys'] ?? 0);
    $girls = intval($_POST['girls'] ?? 0);
    $male = intval($_POST['male'] ?? 0);
    $female = intval($_POST['female'] ?? 0);
    $elder_male = intval($_POST['elder_male'] ?? 0);
    $elder_female = intval($_POST['elder_female'] ?? 0);
    $pwd_male = intval($_POST['pwd_male'] ?? 0);
    $pwd_female = intval($_POST['pwd_female'] ?? 0);
    $ewd_male = intval($_POST['ewd_male'] ?? 0);
    $ewd_female = intval($_POST['ewd_female'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate
    if(empty($project_id)) {
        $error = "Please select a project";
    } elseif(empty($record_date)) {
        $error = "Please select a date";
    } else {
        // Check if total is greater than 0
        $total = $infant_male + $infant_female + $boys + $girls + $male + $female + 
                 $elder_male + $elder_female + $pwd_male + $pwd_female + $ewd_male + $ewd_female;
        
        if($total == 0) {
            $error = "Please enter at least one person in any category";
        } else {
            $sql = "INSERT INTO segregation_records (
                project_id, record_date, infant_male, infant_female, boys, girls,
                male, female, elder_male, elder_female, pwd_male, pwd_female,
                ewd_male, ewd_female, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([
                $project_id, $record_date, $infant_male, $infant_female, $boys, $girls,
                $male, $female, $elder_male, $elder_female, $pwd_male, $pwd_female,
                $ewd_male, $ewd_female, $notes, $_SESSION['user_id']
            ])) {
                $success = "Segregation record added successfully!";
                // Clear form
                $_POST = array();
            } else {
                $error = "Failed to add record.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Record - Tolar Base System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0a1929;
            --primary-blue: #1e4a6f;
            --primary-teal: #2b7a78;
            --primary-gold: #c9a227;
            --accent-red: #d64933;
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

        .btn-manage {
            background: var(--primary-gold);
            color: var(--primary-dark);
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-manage:hover {
            background: #dbb042;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.3);
        }

        /* Form Card */
        .form-card {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 25px;
            margin: 30px 0 50px 0;
            box-shadow: var(--shadow);
        }

        .form-title {
            color: var(--primary-dark);
            margin-bottom: 35px;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid var(--primary-teal);
            padding-bottom: 20px;
        }

        .form-title i {
            color: var(--primary-teal);
            font-size: 2rem;
        }

        /* Project Selection */
        .project-section {
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1rem;
        }

        .form-group label i {
            color: var(--primary-teal);
            margin-right: 10px;
            width: 20px;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(43, 122, 120, 0.15);
        }

        /* Project Info Panel */
        .project-info-panel {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0 30px;
            color: white;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-item i {
            font-size: 1.5rem;
            color: var(--primary-gold);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }

        .info-content {
            flex: 1;
        }

        .info-content .label {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 3px;
        }

        .info-content .value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 30px 0;
        }

        .category-card {
            background: #f8fafc;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }

        .category-card:hover {
            border-color: var(--primary-teal);
            box-shadow: var(--shadow);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
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
            margin: 0;
        }

        /* Category-specific icon colors */
        .category-card:nth-child(1) .category-header i { background: linear-gradient(135deg, #f8bbd0, #f06292); color: #880e4f; }
        .category-card:nth-child(2) .category-header i { background: linear-gradient(135deg, #bbdefb, #42a5f5); color: #0d47a1; }
        .category-card:nth-child(3) .category-header i { background: linear-gradient(135deg, #c8e6c9, #66bb6a); color: #1b5e20; }
        .category-card:nth-child(4) .category-header i { background: linear-gradient(135deg, #d1c4e9, #7e57c2); color: #4a148c; }
        .category-card:nth-child(5) .category-header i { background: linear-gradient(135deg, #ffe0b2, #ff9800); color: #bf360c; }
        .category-card:nth-child(6) .category-header i { background: linear-gradient(135deg, #ffcdd2, #ef5350); color: #b71c1c; }

        .input-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .input-row label {
            width: 80px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .input-row label i {
            width: 20px;
            margin-right: 5px;
        }

        .input-row .male-icon { color: #3498db; }
        .input-row .female-icon { color: #e83e8c; }

        .input-row input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .input-row input:focus {
            border-color: var(--primary-teal);
            outline: none;
        }

        /* Total Display */
        .total-display {
            background: linear-gradient(135deg, var(--primary-teal), var(--primary-blue));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1.5rem;
        }

        .total-display i {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .total-number {
            font-size: 3rem;
            font-weight: 700;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 35px;
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

        .btn-danger {
            background: var(--accent-red);
            color: white;
        }

        .btn-danger:hover {
            background: #b71c1c;
            transform: translateY(-3px);
        }

        /* Alert Messages */
        .alert {
            padding: 18px 25px;
            border-radius: 12px;
            margin: 30px 0 40px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
        }

        .alert i {
            font-size: 1.4rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 6px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 6px solid var(--accent-red);
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-badge {
            background: var(--bg-card);
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow);
        }

        .stat-badge i {
            font-size: 1.5rem;
            color: var(--primary-teal);
        }

        .stat-badge .stat-info {
            flex: 1;
        }

        .stat-badge .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .stat-badge .stat-value {
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .project-info-panel {
                grid-template-columns: 1fr;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
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
                <i class="fas fa-plus-circle"></i>
                Add New Segregation Record
            </h2>
            <a href="manage_projects.php" class="btn-manage">
                <i class="fas fa-project-diagram"></i>
                Manage Projects
            </a>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-badge">
                <i class="fas fa-folder-open"></i>
                <div class="stat-info">
                    <div class="stat-label">Total Projects</div>
                    <div class="stat-value"><?php echo count($projects); ?></div>
                </div>
            </div>
            <div class="stat-badge">
                <i class="fas fa-calendar"></i>
                <div class="stat-info">
                    <div class="stat-label">Today's Date</div>
                    <div class="stat-value"><?php echo date('Y-m-d'); ?></div>
                </div>
            </div>
            <div class="stat-badge">
                <i class="fas fa-user"></i>
                <div class="stat-info">
                    <div class="stat-label">Recording Officer</div>
                    <div class="stat-value"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Main Form -->
        <div class="form-card">
            <div class="form-title">
                <i class="fas fa-clipboard-list"></i>
                Record Details
            </div>
            
            <form method="POST" action="" id="recordForm">
                <!-- Project Selection -->
                <div class="project-section">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-project-diagram"></i>
                            Select Project *
                        </label>
                        <select name="project_id" id="project_select" required onchange="this.form.submit()">
                            <option value="">-- Choose a project --</option>
                            <?php foreach($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" 
                                        <?php echo (isset($_POST['project_id']) && $_POST['project_id'] == $project['id']) ? 'selected' : ''; ?>
                                        <?php echo (isset($_GET['project_id']) && $_GET['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['project_title']); ?> 
                                    (<?php echo $project['record_count']; ?> records)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Project Info Panel -->
                    <?php if($selected_project): ?>
                    <div class="project-info-panel">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <div class="label">Location</div>
                                <div class="value"><?php echo htmlspecialchars($selected_project['location']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <div class="info-content">
                                <div class="label">Contact Person</div>
                                <div class="value"><?php echo htmlspecialchars($selected_project['contact_person']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info-content">
                                <div class="label">Contact Email</div>
                                <div class="value"><?php echo htmlspecialchars($selected_project['contact_email']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="info-content">
                                <div class="label">Budget</div>
                                <div class="value">LKR <?php echo number_format($selected_project['budget'], 2); ?></div>
                            </div>
                        </div>
                        <?php if($selected_project['description']): ?>
                        <div class="info-item" style="grid-column: span 2;">
                            <i class="fas fa-align-left"></i>
                            <div class="info-content">
                                <div class="label">Description</div>
                                <div class="value"><?php echo htmlspecialchars($selected_project['description']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Record Date -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar-alt"></i>
                            Record Date *
                        </label>
                        <input type="date" name="record_date" 
                               value="<?php echo isset($_POST['record_date']) ? htmlspecialchars($_POST['record_date']) : date('Y-m-d'); ?>" 
                               required>
                    </div>
                </div>

                <!-- Segregation Categories Title -->
                <div class="form-title" style="margin-top: 40px;">
                    <i class="fas fa-chart-pie"></i>
                    Segregation Categories
                </div>

                <!-- Category Grid -->
                <div class="category-grid">
                    <!-- Infants -->
                    <div class="category-card">
                        <div class="category-header">
                            <i class="fas fa-baby"></i>
                            <h4>Infants (0-2 years)</h4>
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-mars male-icon"></i>
                                Male
                            </label>
                            <input type="number" name="infant_male" min="0" value="<?php echo isset($_POST['infant_male']) ? htmlspecialchars($_POST['infant_male']) : '0'; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="infant_female" min="0" value="<?php echo isset($_POST['infant_female']) ? htmlspecialchars($_POST['infant_female']) : '0'; ?>">
                        </div>
                    </div>

                    <!-- Children -->
                    <div class="category-card">
                        <div class="category-header">
                            <i class="fas fa-child"></i>
                            <h4>Children (3-12 years)</h4>
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-mars male-icon"></i>
                                Boys
                            </label>
                            <input type="number" name="boys" min="0" value="<?php echo isset($_POST['boys']) ? htmlspecialchars($_POST['boys']) : '0'; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Girls
                            </label>
                            <input type="number" name="girls" min="0" value="<?php echo isset($_POST['girls']) ? htmlspecialchars($_POST['girls']) : '0'; ?>">
                        </div>
                    </div>

                    <!-- Adults -->
                    <div class="category-card">
                        <div class="category-header">
                            <i class="fas fa-user-tie"></i>
                            <h4>Adults (13-59 years)</h4>
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-mars male-icon"></i>
                                Male
                            </label>
                            <input type="number" name="male" min="0" value="<?php echo isset($_POST['male']) ? htmlspecialchars($_POST['male']) : '0'; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="female" min="0" value="<?php echo isset($_POST['female']) ? htmlspecialchars($_POST['female']) : '0'; ?>">
                        </div>
                    </div>

                    <!-- Elderly -->
                    <div class="category-card">
                        <div class="category-header">
                            <i class="fas fa-user-cog"></i>
                            <h4>Elderly (60+ years)</h4>
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-mars male-icon"></i>
                                Male
                            </label>
                            <input type="number" name="elder_male" min="0" value="<?php echo isset($_POST['elder_male']) ? htmlspecialchars($_POST['elder_male']) : '0'; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="elder_female" min="0" value="<?php echo isset($_POST['elder_female']) ? htmlspecialchars($_POST['elder_female']) : '0'; ?>">
                        </div>
                    </div>

                    <!-- PWD -->
                    <div class="category-card">
                        <div class="category-header">
                            <i class="fas fa-wheelchair"></i>
                            <h4>PWD</h4>
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-mars male-icon"></i>
                                Male
                            </label>
                            <input type="number" name="pwd_male" min="0" value="<?php echo isset($_POST['pwd_male']) ? htmlspecialchars($_POST['pwd_male']) : '0'; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="pwd_female" min="0" value="<?php echo isset($_POST['pwd_female']) ? htmlspecialchars($_POST['pwd_female']) : '0'; ?>">
                        </div>
                    </div>

                    <!-- EWD -->
                    <div class="category-card">
                        <div class="category-header">
                            <i class="fas fa-hand-holding-heart"></i>
                            <h4>EWD</h4>
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-mars male-icon"></i>
                                Male
                            </label>
                            <input type="number" name="ewd_male" min="0" value="<?php echo isset($_POST['ewd_male']) ? htmlspecialchars($_POST['ewd_male']) : '0'; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="ewd_female" min="0" value="<?php echo isset($_POST['ewd_female']) ? htmlspecialchars($_POST['ewd_female']) : '0'; ?>">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-sticky-note"></i>
                        Notes / Additional Information
                    </label>
                    <textarea name="notes" rows="4" placeholder="Enter any additional notes or observations..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>

                <!-- Total Display -->
                <div class="total-display">
                    <div>
                        <i class="fas fa-users"></i>
                        Total People
                    </div>
                    <div>
                        <span class="total-number" id="total-display">0</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Record
                    </button>
                    <a href="dashboard.php" class="btn btn-danger">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Calculate total on input change
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('input', calculateTotal);
                input.addEventListener('change', calculateTotal);
            });
            calculateTotal();
        });

        function calculateTotal() {
            let total = 0;
            const inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            
            const totalDisplay = document.getElementById('total-display');
            if(totalDisplay) {
                totalDisplay.textContent = total;
            }
        }

        // Auto-submit when project changes
        document.getElementById('project_select').addEventListener('change', function(e) {
            if(this.value) {
                sessionStorage.setItem('scrollPos', window.scrollY);
            }
        });

        // Restore scroll position after page reload
        window.addEventListener('load', function() {
            const scrollPos = sessionStorage.getItem('scrollPos');
            if(scrollPos) {
                window.scrollTo(0, parseInt(scrollPos));
                sessionStorage.removeItem('scrollPos');
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>