<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

$janitors_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE role = 'janitor' AND status = 'active' ORDER BY first_name";
$janitors_result = $conn->query($janitors_query);
$janitors = [];
if ($janitors_result) {
    while ($row = $janitors_result->fetch_assoc()) {
        $janitors[] = $row;
    }
}

// --- ADDED: POST handler for add_bin and GET handler for AJAX loading ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_bin') {
    try {
        $bin_code = $_POST['bin_code'] ?? '';
        $location = $_POST['location'] ?? '';
        $type = $_POST['bin_type'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $status = $_POST['status'] ?? '';
        $assigned_to = $_POST['assigned_janitor'] ?? null;
        if ($assigned_to === '') $assigned_to = null;

        if (empty($bin_code) || empty($location) || empty($type) || empty($status)) {
            throw new Exception('Required fields are missing');
        }

        $stmt = $conn->prepare("INSERT INTO bins (bin_code, location, type, capacity, status, assigned_to, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("ssssss", $bin_code, $location, $type, $capacity, $status, $assigned_to);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $new_bin_id = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS janitor_name FROM bins b LEFT JOIN users u ON b.assigned_to = u.user_id WHERE b.bin_id = ?");
        $stmt->bind_param("i", $new_bin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_bin = $result->fetch_assoc();
        $stmt->close();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'bin' => $new_bin]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'load_bins') {
    $filter = $_GET['filter'] ?? 'all';

    $bins_query = "SELECT bins.*, CONCAT(u.first_name, ' ', u.last_name) AS janitor_name FROM bins LEFT JOIN users u ON bins.assigned_to = u.user_id";
    if ($filter !== 'all') {
        $bins_query .= " WHERE bins.status = ?";
    }
    $bins_query .= " ORDER BY bins.created_at DESC";

    $stmt = $conn->prepare($bins_query);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    if ($filter !== 'all') {
        $stmt->bind_param('s', $filter);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $bins = [];
    while ($row = $result->fetch_assoc()) {
        $bins[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'bins' => $bins]);
    exit;
}

// Fetch all bins for initial render (optional fallback)
$bins = [];
$bins_query = "SELECT bins.*, CONCAT(u.first_name, ' ', u.last_name) AS janitor_name FROM bins LEFT JOIN users u ON bins.assigned_to = u.user_id ORDER BY bins.created_at DESC";
$bins_result = $conn->query($bins_query);
if ($bins_result) {
    while ($row = $bins_result->fetch_assoc()) {
        $bins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bins Management - Trashbin Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/admin-dashboard.css">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin-dashboard.php">
        <span class="brand-circle me-2"><i class="fa-solid fa-trash-can"></i></span>
        <span class="d-none d-sm-inline">Trashbin Admin</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item me-2">
            <a class="nav-link position-relative" href="notifications.php" title="Notifications">
              <i class="fa-solid fa-bell"></i>
              <span class="badge rounded-pill bg-danger position-absolute translate-middle" id="notificationCount" style="top:8px; left:18px; display:none;">0</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="profile.php" title="My Profile">
              <i class="fa-solid fa-user me-1"></i><span class="d-none d-sm-inline">Profile</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">
              <i class="fa-solid fa-right-from-bracket me-1"></i><span class="d-none d-sm-inline">Logout</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header d-none d-md-block">
        <h6 class="sidebar-title">Menu</h6>
      </div>
      <a href="admin-dashboard.php" class="sidebar-item">
        <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
      </a>
      <a href="bins.php" class="sidebar-item active">
        <i class="fa-solid fa-trash-alt"></i><span>Bins</span>
      </a>
      <a href="janitors.php" class="sidebar-item">
        <i class="fa-solid fa-users"></i><span>Janitors</span>
      </a>
      <a href="reports.php" class="sidebar-item">
        <i class="fa-solid fa-chart-line"></i><span>Reports</span>
      </a>
      <a href="notifications.php" class="sidebar-item">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
      </a>
      <a href="profile.php" class="sidebar-item">
        <i class="fa-solid fa-user"></i><span>My Profile</span>
      </a>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <div class="section-header flex-column flex-md-row">
        <div>
          <h1 class="page-title">Bin Management</h1>
          <p class="page-subtitle">Manage all trashbins in the system</p>
        </div>
        <div class="d-flex gap-2 flex-column flex-md-row mt-3 mt-md-0">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" id="searchBinsInput" placeholder="Search bins...">
          </div>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterBinsDropdown" data-bs-toggle="dropdown">
              <i class="fas fa-filter me-1"></i>Filter
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterBinsDropdown">
              <li><a class="dropdown-item" href="#" data-filter="all">All Bins</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" data-filter="full">Full</a></li>
              <li><a class="dropdown-item" href="#" data-filter="empty">Empty</a></li>
              <li><a class="dropdown-item" href="#" data-filter="needs_attention">Needs Attention</a></li>
            </ul>
          </div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBinModal">
            <i class="fas fa-plus me-1"></i>Add New Bin
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Bin ID</th>
                  <th>Location</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th class="d-none d-lg-table-cell">Capacity</th>
                  <th class="d-none d-md-table-cell">Assigned To</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="allBinsTableBody">
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">No bins found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Bin Modal -->
  <div class="modal fade" id="addBinModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i>Add New Bin</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addBinForm">
            <!-- Removed binId field - bin_id auto-generates in database -->
            <div class="mb-3">
              <label for="binCode" class="form-label">Bin Code</label>
              <input type="text" class="form-control" id="binCode" required>
            </div>
            <div class="mb-3">
              <label for="binLocation" class="form-label">Location</label>
              <input type="text" class="form-control" id="binLocation" required>
            </div>
            <div class="mb-3">
              <label for="binType" class="form-label">Bin Type</label>
              <select class="form-select" id="binType" required>
                <option value="">Select type</option>
                <option value="General">General Waste</option>
                <option value="Recyclable">Recyclable</option>
                <option value="Organic">Organic</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="binCapacity" class="form-label">Capacity (%)</label>
              <input type="number" class="form-control" id="binCapacity" min="0" max="100" value="0" required>
            </div>
            <div class="mb-3">
              <label for="binStatus" class="form-label">Status</label>
              <select class="form-select" id="binStatus" required>
                <option value="">Select status</option>
                <option value="empty">Empty</option>
                <option value="full">Full</option>
                <option value="needs_attention">Needs Attention</option>
              </select>
            </div>
            <!-- Added Assign Janitor field to the form -->
            <div class="mb-3">
              <label for="binAssignedJanitor" class="form-label">Assign Janitor</label>
              <select class="form-select" id="binAssignedJanitor">
                <option value="">Select janitor (optional)</option>
                <?php foreach ($janitors as $janitor): ?>
                  <option value="<?php echo $janitor['user_id']; ?>"><?php echo htmlspecialchars($janitor['full_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveNewBin()">Save Bin</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        function refreshBinsTable(filter = 'all') {
            $.ajax({
                url: 'bins.php',
                method: 'GET',
                data: { action: 'load_bins', filter: filter },
                dataType: 'json',
                success: function(response) {
                    if (!response || !response.success) return;
                    const tbody = $('#allBinsTableBody');
                    tbody.empty();

                    if (!response.bins || response.bins.length === 0) {
                        tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">No bins found</td></tr>');
                        return;
                    }

                    response.bins.forEach(bin => {
                        const statusClass = bin.status === 'full' ? 'danger' : (bin.status === 'empty' ? 'success' : 'warning');
                        tbody.append(`
                            <tr>
                                <td>${bin.bin_code}</td>
                                <td>${bin.location}</td>
                                <td>${bin.type}</td>
                                <td><span class="badge bg-${statusClass}">${bin.status}</span></td>
                                <td class="d-none d-lg-table-cell">${bin.capacity}%</td>
                                <td class="d-none d-md-table-cell">${bin.janitor_name || 'Unassigned'}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary edit-bin" data-bin-id="${bin.bin_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                },
                error: function(xhr, status, err) {
                    console.error('Failed to load bins', err);
                }
            });
        }

        function saveNewBin() {
            const formData = {
                action: 'add_bin',
                bin_code: document.getElementById('binCode').value.trim(),
                location: document.getElementById('binLocation').value.trim(),
                bin_type: document.getElementById('binType').value,
                capacity: document.getElementById('binCapacity').value,
                status: document.getElementById('binStatus').value,
                assigned_janitor: document.getElementById('binAssignedJanitor').value || ''
            };

            if (!formData.bin_code || !formData.location || !formData.bin_type || !formData.status) {
                alert('Please fill in all required fields');
                return;
            }

            $.ajax({
                url: window.location.pathname,
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success && response.bin) {
                        // Prefer reloading table to keep ordering consistent
                        refreshBinsTable();

                        document.getElementById('addBinForm').reset();
                        $('#addBinModal').modal('hide');
                        alert('Bin added successfully!');
                    } else {
                        alert((response && response.error) ? response.error : 'Failed to add bin');
                    }
                },
                error: function(xhr, status, err) {
                    console.error('Error saving bin:', err);
                    alert('Error occurred while saving bin');
                }
            });
        }

        // Attach handlers
        const saveBtn = document.querySelector('#addBinModal .btn-primary');
        if (saveBtn) saveBtn.onclick = saveNewBin;

        // Search filtering (client-side)
        const searchInput = document.getElementById('searchBinsInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('#allBinsTableBody tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        // Filter dropdown - uses data-filter on items
        document.querySelectorAll('#filterBinsDropdown + .dropdown-menu .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter') || 'all';
                refreshBinsTable(filter);
            });
        });

        // Initial load
        refreshBinsTable();
    });
  </script>
</body>
</html>
