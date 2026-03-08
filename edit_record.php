<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid record ID";
    header("Location: view_records.php");
    exit();
}

$id = $_GET['id'];

// Get the record to edit with better error handling
try {
    $stmt = $pdo->prepare("
        SELECT sr.*, p.project_title, p.location, p.contact_person, p.budget 
        FROM segregation_records sr
        LEFT JOIN projects p ON sr.project_id = p.id
        WHERE sr.id = ?
    ");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if(!$record) {
        $_SESSION['error'] = "Record not found";
        header("Location: view_records.php");
        exit();
    }
    
    // Check if project data exists
    if(!isset($record['project_title']) || empty($record['project_title'])) {
        $record['project_title'] = 'Unknown Project (Deleted)';
    }
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred";
    header("Location: view_records.php");
    exit();
}

// Get projects for dropdown
try {
    $stmt = $pdo->query("
        SELECT id, project_title, location, contact_person, budget 
        FROM projects 
        ORDER BY project_title
    ");
    $projects = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $projects = [];
}

// Handle Update
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
            try {
                $sql = "UPDATE segregation_records SET 
                        project_id = ?, record_date = ?, infant_male = ?, infant_female = ?,
                        boys = ?, girls = ?, male = ?, female = ?, elder_male = ?, elder_female = ?,
                        pwd_male = ?, pwd_female = ?, ewd_male = ?, ewd_female = ?, notes = ?
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                if($stmt->execute([
                    $project_id, $record_date, $infant_male, $infant_female, $boys, $girls,
                    $male, $female, $elder_male, $elder_female, $pwd_male, $pwd_female,
                    $ewd_male, $ewd_female, $notes, $id
                ])) {
                    $success = "Record updated successfully!";
                    // Refresh record data
                    $stmt = $pdo->prepare("
                        SELECT sr.*, p.project_title, p.location 
                        FROM segregation_records sr
                        LEFT JOIN projects p ON sr.project_id = p.id
                        WHERE sr.id = ?
                    ");
                    $stmt->execute([$id]);
                    $record = $stmt->fetch();
                    
                    if(!isset($record['project_title']) || empty($record['project_title'])) {
                        $record['project_title'] = 'Unknown Project (Deleted)';
                    }
                }
            } catch(PDOException $e) {
                error_log("Update error: " . $e->getMessage());
                $error = "Failed to update record. Please try again.";
            }
        }
    }
}

// Safely get values with defaults
$project_title = isset($record['project_title']) ? $record['project_title'] : 'Unknown Project';
$record_date = isset($record['record_date']) ? $record['record_date'] : date('Y-m-d');
$infant_male = isset($record['infant_male']) ? $record['infant_male'] : 0;
$infant_female = isset($record['infant_female']) ? $record['infant_female'] : 0;
$boys = isset($record['boys']) ? $record['boys'] : 0;
$girls = isset($record['girls']) ? $record['girls'] : 0;
$male = isset($record['male']) ? $record['male'] : 0;
$female = isset($record['female']) ? $record['female'] : 0;
$elder_male = isset($record['elder_male']) ? $record['elder_male'] : 0;
$elder_female = isset($record['elder_female']) ? $record['elder_female'] : 0;
$pwd_male = isset($record['pwd_male']) ? $record['pwd_male'] : 0;
$pwd_female = isset($record['pwd_female']) ? $record['pwd_female'] : 0;
$ewd_male = isset($record['ewd_male']) ? $record['ewd_male'] : 0;
$ewd_female = isset($record['ewd_female']) ? $record['ewd_female'] : 0;
$notes = isset($record['notes']) ? $record['notes'] : '';
$total_count = isset($record['total_count']) ? $record['total_count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record - Tolar Base System</title>
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

        .btn-back {
            background: var(--primary-blue);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #123456;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 74, 111, 0.3);
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

        /* Record Info */
        .record-info {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 2px solid #e2e8f0;
        }

        .record-info i {
            font-size: 2rem;
            color: var(--primary-teal);
        }

        .record-info .info {
            flex: 1;
        }

        .record-info .label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .record-info .value {
            color: var(--primary-dark);
            font-size: 1.2rem;
            font-weight: 600;
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

        /* Responsive */
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
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
                <i class="fas fa-edit"></i>
                Edit Record #<?php echo $id; ?>
            </h2>
            <a href="view_records.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Records
            </a>
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

        <!-- Record Info -->
        <div class="record-info">
            <i class="fas fa-info-circle"></i>
            <div class="info">
                <div class="label">Currently Editing</div>
                <div class="value">
                    <?php echo htmlspecialchars($project_title); ?> - 
                    <?php echo date('F j, Y', strtotime($record_date)); ?>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="form-card">
            <div class="form-title">
                <i class="fas fa-clipboard-list"></i>
                Edit Record Details
            </div>
            
            <form method="POST" action="" id="editForm">
                <!-- Project Selection -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-project-diagram"></i>
                        Project *
                    </label>
                    <select name="project_id" required>
                        <option value="">-- Select Project --</option>
                        <?php foreach($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" 
                                <?php echo (isset($record['project_id']) && $project['id'] == $record['project_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Record Date -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-alt"></i>
                        Record Date *
                    </label>
                    <input type="date" name="record_date" 
                           value="<?php echo $record_date; ?>" required>
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
                            <input type="number" name="infant_male" min="0" 
                                   value="<?php echo $infant_male; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="infant_female" min="0" 
                                   value="<?php echo $infant_female; ?>">
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
                            <input type="number" name="boys" min="0" 
                                   value="<?php echo $boys; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Girls
                            </label>
                            <input type="number" name="girls" min="0" 
                                   value="<?php echo $girls; ?>">
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
                            <input type="number" name="male" min="0" 
                                   value="<?php echo $male; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="female" min="0" 
                                   value="<?php echo $female; ?>">
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
                            <input type="number" name="elder_male" min="0" 
                                   value="<?php echo $elder_male; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="elder_female" min="0" 
                                   value="<?php echo $elder_female; ?>">
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
                            <input type="number" name="pwd_male" min="0" 
                                   value="<?php echo $pwd_male; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="pwd_female" min="0" 
                                   value="<?php echo $pwd_female; ?>">
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
                            <input type="number" name="ewd_male" min="0" 
                                   value="<?php echo $ewd_male; ?>">
                        </div>
                        <div class="input-row">
                            <label>
                                <i class="fas fa-venus female-icon"></i>
                                Female
                            </label>
                            <input type="number" name="ewd_female" min="0" 
                                   value="<?php echo $ewd_female; ?>">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-sticky-note"></i>
                        Notes / Additional Information
                    </label>
                    <textarea name="notes" rows="4" placeholder="Enter any additional notes..."><?php echo htmlspecialchars($notes); ?></textarea>
                </div>

                <!-- Total Display -->
                <div class="total-display">
                    <div>
                        <i class="fas fa-users"></i>
                        Total People
                    </div>
                    <div>
                        <span class="total-number" id="total-display">
                            <?php echo $total_count; ?>
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Record
                    </button>
                    <a href="view_records.php" class="btn btn-danger">
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

        // Confirm before leaving with unsaved changes
        let formChanged = false;
        const form = document.getElementById('editForm');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
            input.addEventListener('input', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if(formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        form.addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>