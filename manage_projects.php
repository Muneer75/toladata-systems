<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$message = '';
$error = '';

// Handle Delete - Only when delete parameter is present
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First, check if project exists
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    
    if($project) {
        // Check if project has records
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM segregation_records WHERE project_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if($count > 0) {
            // Delete associated records first
            $stmt = $pdo->prepare("DELETE FROM segregation_records WHERE project_id = ?");
            if($stmt->execute([$id])) {
                // Then delete the project
                $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                if($stmt->execute([$id])) {
                    $message = "Project and its $count associated records deleted successfully!";
                    // Redirect to remove delete parameter from URL
                    header("Location: manage_projects.php?deleted=success");
                    exit();
                } else {
                    $error = "Failed to delete project.";
                }
            } else {
                $error = "Failed to delete associated records.";
            }
        } else {
            // No records, safe to delete
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            if($stmt->execute([$id])) {
                $message = "Project deleted successfully!";
                // Redirect to remove delete parameter from URL
                header("Location: manage_projects.php?deleted=success");
                exit();
            } else {
                $error = "Failed to delete project.";
            }
        }
    } else {
        $error = "Project not found.";
        // Redirect to remove delete parameter
        header("Location: manage_projects.php?error=notfound");
        exit();
    }
}

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_title = trim($_POST['project_title']);
    $location = trim($_POST['location']);
    $budget = floatval($_POST['budget'] ?? 0);
    $contact_person = trim($_POST['contact_person']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $description = trim($_POST['description'] ?? '');
    
    if(empty($project_title) || empty($location)) {
        $error = "Project title and location are required!";
    } else {
        if(isset($_POST['project_id']) && !empty($_POST['project_id'])) {
            // Update existing project
            $stmt = $pdo->prepare("UPDATE projects SET 
                project_title = ?, location = ?, budget = ?, contact_person = ?,
                contact_email = ?, contact_phone = ?, start_date = ?, end_date = ?,
                description = ? WHERE id = ?");
            
            if($stmt->execute([$project_title, $location, $budget, $contact_person, 
                $contact_email, $contact_phone, $start_date, $end_date, $description, $_POST['project_id']])) {
                $message = "Project updated successfully!";
                // Redirect to prevent form resubmission
                header("Location: manage_projects.php?updated=success");
                exit();
            } else {
                $error = "Failed to update project.";
            }
        } else {
            // Add new project
            $stmt = $pdo->prepare("INSERT INTO projects 
                (project_title, location, budget, contact_person, contact_email, 
                contact_phone, start_date, end_date, description, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if($stmt->execute([$project_title, $location, $budget, $contact_person, 
                $contact_email, $contact_phone, $start_date, $end_date, $description, $_SESSION['user_id']])) {
                $message = "Project added successfully!";
                // Redirect to prevent form resubmission
                header("Location: manage_projects.php?added=success");
                exit();
            } else {
                $error = "Failed to add project.";
            }
        }
    }
}

// Get all projects with record counts
$stmt = $pdo->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM segregation_records WHERE project_id = p.id) as record_count 
    FROM projects p 
    ORDER BY p.created_at DESC
");
$projects = $stmt->fetchAll();

// Get project for editing
$edit_project = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_project = $stmt->fetch();
}

// Set message based on URL parameters
if(isset($_GET['deleted'])) {
    $message = "Operation completed successfully!";
} elseif(isset($_GET['updated'])) {
    $message = "Project updated successfully!";
} elseif(isset($_GET['added'])) {
    $message = "Project added successfully!";
} elseif(isset($_GET['error'])) {
    $error = "An error occurred. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - TolaData System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Professional Color Palette */
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
            padding: 20px 0;
            border-bottom: 3px solid var(--primary-teal);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header h2 {
            color: var(--primary-dark);
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .page-header h2 i {
            color: var(--primary-teal);
            margin-right: 15px;
            font-size: 2.5rem;
        }

        .header-badge {
            background: var(--primary-teal);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Card */
        .form-card {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 25px;
            margin: 30px 0 50px 0;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .form-title {
            color: var(--primary-dark);
            margin-bottom: 35px;
            font-size: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            border-bottom: 3px solid var(--primary-teal);
            padding-bottom: 20px;
        }

        .form-title i {
            color: var(--primary-teal);
            margin-right: 20px;
            font-size: 2.2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(43, 122, 120, 0.15);
        }

        /* Button Styles */
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            text-decoration: none;
        }

        .btn i {
            font-size: 18px;
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

        .btn-secondary {
            background: #e2e8f0;
            color: var(--primary-dark);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
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

        /* Table Card */
        .table-card {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 25px;
            margin-top: 50px;
            margin-bottom: 40px;
            box-shadow: var(--shadow);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .table-title {
            color: var(--primary-dark);
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .table-title i {
            color: var(--primary-teal);
            margin-right: 20px;
            font-size: 2.2rem;
        }

        .total-badge {
            background: var(--primary-teal);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--primary-dark);
            color: white;
            padding: 18px;
            font-weight: 600;
            text-align: left;
        }

        .data-table td {
            padding: 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .project-id {
            font-weight: 700;
            color: var(--primary-teal);
        }

        .project-title {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .contact-info {
            font-size: 0.9rem;
        }

        .contact-info i {
            color: var(--primary-teal);
            width: 16px;
            margin-right: 5px;
        }

        .record-badge {
            background: var(--primary-blue);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .warning-text {
            color: var(--accent-red);
            font-size: 0.8rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .btn-edit {
            background: var(--primary-teal);
            color: white;
        }

        .btn-edit:hover {
            background: #1e5f5c;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--accent-red);
            color: white;
        }

        .btn-delete:hover {
            background: #b71c1c;
            transform: translateY(-2px);
        }

        /* Modal - Hidden by default */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 25px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .modal-icon {
            font-size: 5rem;
            color: var(--accent-red);
            margin-bottom: 25px;
        }

        .modal-title {
            color: var(--primary-dark);
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .modal-text {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 35px;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .modal-btn {
            padding: 15px 40px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .btn-confirm {
            background: var(--accent-red);
            color: white;
        }

        .btn-confirm:hover {
            background: #b71c1c;
            transform: translateY(-3px);
        }

        .btn-cancel {
            background: #e2e8f0;
            color: var(--primary-dark);
        }

        .btn-cancel:hover {
            background: #cbd5e1;
            transform: translateY(-3px);
        }

        /* Footer */
        .footer {
            background: var(--primary-dark);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            text-align: center;
        }

        .footer p {
            margin: 5px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                margin: 80px 0 30px 0;
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-card {
                padding: 25px;
            }
            
            .table-card {
                padding: 25px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                padding: 25px;
            }
            
            .modal-buttons {
                flex-direction: column;
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
                <i class="fas fa-layer-group"></i>
                TolaData System
            </h2>
            <div class="header-badge">
                <i class="fas fa-tasks"></i>
                Project Management
            </div>
        </div>
        
        <!-- Messages -->
        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Project Form -->
        <div class="form-card">
            <div class="form-title">
                <i class="fas fa-<?php echo $edit_project ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $edit_project ? 'Edit Project' : 'Add New Project'; ?>
            </div>
            
            <form method="POST" action="">
                <?php if($edit_project): ?>
                    <input type="hidden" name="project_id" value="<?php echo $edit_project['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <!-- Left Column -->
                    <div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Project Title *</label>
                            <input type="text" name="project_title" required 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['project_title']) : ''; ?>"
                                   placeholder="Enter project title">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Budget (LKR)</label>
                            <input type="number" step="0.01" name="budget" 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['budget']) : ''; ?>"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Contact Email</label>
                            <input type="email" name="contact_email" 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['contact_email']) : ''; ?>"
                                   placeholder="email@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                            <input type="date" name="start_date" 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['start_date']) : ''; ?>"
                                   placeholder="mm/dd/yyyy">
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location *</label>
                            <input type="text" name="location" required 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['location']) : ''; ?>"
                                   placeholder="Enter location">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Contact Person</label>
                            <input type="text" name="contact_person" 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['contact_person']) : ''; ?>"
                                   placeholder="Full name">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Contact Phone</label>
                            <input type="text" name="contact_phone" 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['contact_phone']) : ''; ?>"
                                   placeholder="014 077 200 5700">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> End Date</label>
                            <input type="date" name="end_date" 
                                   value="<?php echo $edit_project ? htmlspecialchars($edit_project['end_date']) : ''; ?>"
                                   placeholder="mm/dd/yyyy">
                        </div>
                    </div>
                </div>
                
                <!-- Description - Full Width -->
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" rows="5" placeholder="Project description..."><?php echo $edit_project ? htmlspecialchars($edit_project['description']) : ''; ?></textarea>
                </div>
                
                <!-- Form Actions -->
                <div style="display: flex; gap: 20px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $edit_project ? 'save' : 'plus'; ?>"></i>
                        <?php echo $edit_project ? 'Update Project' : 'Add Project'; ?>
                    </button>
                    
                    <?php if($edit_project): ?>
                        <a href="manage_projects.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Projects List -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-project-diagram"></i>
                    All Projects
                </div>
                <span class="total-badge">
                    <i class="fas fa-database"></i> Total: <?php echo count($projects); ?> projects
                </span>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project Title</th>
                            <th>Location</th>
                            <th>Budget</th>
                            <th>Contact</th>
                            <th>Records</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($projects as $project): ?>
                        <tr>
                            <td>
                                <span class="project-id">#<?php echo $project['id']; ?></span>
                            </td>
                            <td>
                                <span class="project-title"><?php echo htmlspecialchars($project['project_title']); ?></span>
                            </td>
                            <td>
                                <i class="fas fa-map-marker-alt" style="color: var(--primary-teal);"></i>
                                <?php echo htmlspecialchars($project['location']); ?>
                            </td>
                            <td>
                                <i class="fas fa-money-bill-wave" style="color: var(--primary-gold);"></i>
                                <?php echo number_format($project['budget'], 2); ?>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($project['contact_person']); ?></div>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($project['contact_email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="record-badge">
                                    <i class="fas fa-<?php echo $project['record_count'] > 0 ? 'database' : 'folder'; ?>"></i>
                                    <?php echo $project['record_count']; ?> record(s)
                                </span>
                                <?php if($project['record_count'] > 0): ?>
                                    <div class="warning-text">
                                        
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt" style="color: var(--primary-teal);"></i>
                                <?php echo date('Y-m-d', strtotime($project['created_at'])); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="manage_projects.php?edit=<?php echo $project['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $project['id']; ?>, '<?php echo addslashes($project['project_title']); ?>', <?php echo $project['record_count']; ?>)" class="btn-action btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($projects)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px;">
                                <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
                                <p style="color: var(--text-muted);">No projects found. Click "Add Project" to create your first project.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal - Hidden by default -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">Confirm Delete</h3>
            <div class="modal-text" id="deleteMessage"></div>
            <div class="modal-buttons">
                <button onclick="executeDelete()" class="modal-btn btn-confirm">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
                <button onclick="closeModal()" class="modal-btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        let deleteId = null;
        
        function confirmDelete(id, title, recordCount) {
            deleteId = id;
            const modal = document.getElementById('deleteModal');
            const message = document.getElementById('deleteMessage');
            
            if(recordCount > 0) {
                message.innerHTML = `Are you sure you want to delete project "<strong>${title}</strong>"?<br><br>
                    <span style="color: #d64933; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i> 
                        This will also delete ${recordCount} associated segregation record(s)!
                    </span>`;
            } else {
                message.innerHTML = `Are you sure you want to delete project "<strong>${title}</strong>"?`;
            }
            
            modal.style.display = 'flex';
        }
        
        function executeDelete() {
            if(deleteId) {
                window.location.href = `manage_projects.php?delete=${deleteId}`;
            }
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteId = null;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Ensure modal is hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('deleteModal').style.display = 'none';
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>