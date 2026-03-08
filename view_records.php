<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$message = '';
$error = '';

// Handle Delete Record
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if record exists
    $stmt = $pdo->prepare("SELECT * FROM segregation_records WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if($record) {
        // Delete the record
        $stmt = $pdo->prepare("DELETE FROM segregation_records WHERE id = ?");
        if($stmt->execute([$id])) {
            $message = "Record deleted successfully!";
            // Redirect to remove delete parameter from URL
            header("Location: view_records.php?deleted=success");
            exit();
        } else {
            $error = "Failed to delete record.";
        }
    } else {
        $error = "Record not found.";
        header("Location: view_records.php?error=notfound");
        exit();
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$project_filter = isset($_GET['project']) ? $_GET['project'] : '';

$sql = "
    SELECT sr.*, p.project_title, u.username as created_by_name, u.full_name
    FROM segregation_records sr
    JOIN projects p ON sr.project_id = p.id
    JOIN users u ON sr.created_by = u.id
    WHERE 1=1
";

$params = [];

if(!empty($search)) {
    $sql .= " AND (p.project_title LIKE ? OR sr.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if(!empty($project_filter)) {
    $sql .= " AND sr.project_id = ?";
    $params[] = $project_filter;
}

$sql .= " ORDER BY sr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get projects for filter
$stmt = $pdo->query("SELECT id, project_title FROM projects ORDER BY project_title");
$projects = $stmt->fetchAll();

// Calculate totals
$grand_total = 0;
$category_totals = [
    'infant' => 0, 'children' => 0, 'adult' => 0, 
    'elder' => 0, 'pwd' => 0, 'ewd' => 0
];

foreach($records as $record) {
    $grand_total += $record['total_count'];
    $category_totals['infant'] += $record['infant_male'] + $record['infant_female'];
    $category_totals['children'] += $record['boys'] + $record['girls'];
    $category_totals['adult'] += $record['male'] + $record['female'];
    $category_totals['elder'] += $record['elder_male'] + $record['elder_female'];
    $category_totals['pwd'] += $record['pwd_male'] + $record['pwd_female'];
    $category_totals['ewd'] += $record['ewd_male'] + $record['ewd_female'];
}

// Set message based on URL parameters
if(isset($_GET['deleted'])) {
    $message = "Record deleted successfully!";
} elseif(isset($_GET['updated'])) {
    $message = "Record updated successfully!";
} elseif(isset($_GET['error'])) {
    $error = "An error occurred. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Records - Tolar Base System</title>
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

        .btn-add {
            background: var(--primary-teal);
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

        .btn-add:hover {
            background: #1e5f5c;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(43, 122, 120, 0.3);
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .summary-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .summary-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-teal), var(--primary-blue));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .summary-info {
            flex: 1;
        }

        .summary-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .summary-value {
            color: var(--primary-dark);
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Filter Card */
        .filter-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }

        .filter-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        .filter-title i {
            color: var(--primary-teal);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
        }

        .filter-group {
            position: relative;
        }

        .filter-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: var(--primary-teal);
            outline: none;
        }

        .btn-filter {
            background: var(--primary-teal);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter:hover {
            background: #1e5f5c;
        }

        .btn-reset {
            background: #e2e8f0;
            color: var(--primary-dark);
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        /* Table Card */
        .table-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1300px;
        }

        .data-table th {
            background: var(--primary-dark);
            color: white;
            padding: 15px;
            font-weight: 600;
            text-align: left;
        }

        .data-table th i {
            margin-right: 8px;
            color: var(--primary-gold);
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
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

        .btn-view {
            background: var(--primary-blue);
            color: white;
        }

        .btn-view:hover {
            background: #123456;
            transform: translateY(-2px);
        }

        /* Badge */
        .total-badge {
            background: var(--primary-teal);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px;
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

        /* Modal */
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
        }

        .modal-icon {
            font-size: 4rem;
            color: var(--accent-red);
            margin-bottom: 20px;
        }

        .modal-title {
            color: var(--primary-dark);
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .modal-text {
            color: var(--text-muted);
            margin-bottom: 25px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-confirm {
            background: var(--accent-red);
            color: white;
        }

        .btn-cancel {
            background: #e2e8f0;
            color: var(--primary-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-buttons {
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
                <i class="fas fa-table"></i>
                View Segregation Records
            </h2>
            <a href="add_record.php" class="btn-add">
                <i class="fas fa-plus-circle"></i>
                Add New Record
            </a>
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

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="summary-info">
                    <div class="summary-label">Total Records</div>
                    <div class="summary-value"><?php echo count($records); ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="summary-info">
                    <div class="summary-label">Total People</div>
                    <div class="summary-value"><?php echo number_format($grand_total); ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-baby"></i>
                </div>
                <div class="summary-info">
                    <div class="summary-label">Infants</div>
                    <div class="summary-value"><?php echo number_format($category_totals['infant']); ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-child"></i>
                </div>
                <div class="summary-info">
                    <div class="summary-label">Children</div>
                    <div class="summary-value"><?php echo number_format($category_totals['children']); ?></div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                <h3>Filter Records</h3>
            </div>
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by project or notes..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <i class="fas fa-project-diagram"></i>
                        <select name="project">
                            <option value="">All Projects</option>
                            <?php foreach($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" 
                                    <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['project_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i>
                            Apply Filters
                        </button>
                        <a href="view_records.php" class="btn-reset">
                            <i class="fas fa-redo"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Records Table -->
        <div class="table-card">
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
                        <th><i class="fas fa-user"></i> Created By</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($records as $record): ?>
                    <tr>
                        <td>
                            <i class="fas fa-calendar-alt" style="color: var(--primary-teal);"></i>
                            <?php echo date('Y-m-d', strtotime($record['record_date'])); ?>
                        </td>
                        <td>
                            <i class="fas fa-folder" style="color: var(--primary-gold);"></i>
                            <?php echo htmlspecialchars($record['project_title']); ?>
                        </td>
                        <td><?php echo $record['infant_male'] + $record['infant_female']; ?></td>
                        <td><?php echo $record['boys'] + $record['girls']; ?></td>
                        <td><?php echo $record['male'] + $record['female']; ?></td>
                        <td><?php echo $record['elder_male'] + $record['elder_female']; ?></td>
                        <td><?php echo $record['pwd_male'] + $record['pwd_female']; ?></td>
                        <td><?php echo $record['ewd_male'] + $record['ewd_female']; ?></td>
                        <td>
                            <span class="total-badge">
                                <i class="fas fa-users"></i>
                                <?php echo $record['total_count']; ?>
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-user-circle" style="color: var(--primary-teal);"></i>
                            <?php echo htmlspecialchars($record['full_name'] ?: $record['created_by_name']); ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn-action btn-edit" title="Edit Record">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo addslashes($record['project_title']); ?>', '<?php echo date('Y-m-d', strtotime($record['record_date'])); ?>')" class="btn-action btn-delete" title="Delete Record">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <a href="view_record_details.php?id=<?php echo $record['id']; ?>" class="btn-action btn-view" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($records)): ?>
                    <tr>
                        <td colspan="11" class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>No records found. <a href="add_record.php" style="color: var(--primary-teal);">Add your first record</a></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
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
        
        function confirmDelete(id, projectTitle, recordDate) {
            deleteId = id;
            const modal = document.getElementById('deleteModal');
            const message = document.getElementById('deleteMessage');
            
            message.innerHTML = `Are you sure you want to delete the record for<br>
                <strong>${projectTitle}</strong> on <strong>${recordDate}</strong>?<br><br>
                <span style="color: #d64933;">This action cannot be undone!</span>`;
            
            modal.style.display = 'flex';
        }
        
        function executeDelete() {
            if(deleteId) {
                window.location.href = `view_records.php?delete=${deleteId}`;
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