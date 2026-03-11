<?php
session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User'; 
require_once 'db.php';

// --- LOGIC 1: UPDATE SEAT DATA ---
// --- LOGIC 1: UPDATE SEAT DATA ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = trim($_POST['hostname']);
    $campaign = $_POST['campaign'] ?? '';
    $department = $_POST['department'] ?? 'San Antonio';
    $cubicle_no = $_POST['cubicle_no_hidden'] ?? ''; // We need to add this to the form
    
    $status = (!empty($hostname)) ? 'Occupied' : 'Vacant';

    $conn->begin_transaction();

    try {
        // 1. Get the OLD hostname to clear it in inventory if it's changing or becoming vacant
        $getOld = $conn->prepare("SELECT hostname, cubicle_no FROM production_floor_map WHERE id = ?");
        $getOld->bind_param("i", $id);
        $getOld->execute();
        $oldData = $getOld->get_result()->fetch_assoc();
        $oldHost = $oldData['hostname'];
        $actual_cubicle = $oldData['cubicle_no'];

        // 2. Sync Inventory: Handle the "Move Out" (Old Host)
        if (!empty($oldHost) && $oldHost !== $hostname) {
            $clearInv = $conn->prepare("UPDATE inventory_items SET status = 'Vacant', cubicle_number = 'N/A' WHERE host_name = ?");
            $clearInv->bind_param("s", $oldHost);
            $clearInv->execute();
        }

        // 3. Sync Inventory: Handle the "Move In" (New Host)
        if (!empty($hostname)) {
            $updateInv = $conn->prepare("UPDATE inventory_items SET status = 'Active', location = 'Onsite', cubicle_number = ? WHERE host_name = ?");
            $updateInv->bind_param("ss", $actual_cubicle, $hostname);
            $updateInv->execute();
        }

        // 4. Update the Map Table
        $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=?, department=? WHERE id=?");
        $stmt->bind_param("ssssi", $hostname, $status, $campaign, $department, $id);
        $stmt->execute();

        $conn->commit();
        header("Location: prod_map.php?dept=" . urlencode($department));
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Sync Error: " . $e->getMessage());
    }
}

// --- LOGIC 2: FETCH DEPARTMENT STATISTICS ---
// Added 'Indiana' to the list
$dept_list = [
    'San Antonio', 'Phoenix', 'Denver', 'Dallas', 'Los Angeles', 
    'Chicago', 'Orlando', 'Atlanta', 'Indiana', 'Boston', 'Toronto', 'Golden State', 
    'TRN', 'Miami', 'Gray Room', 'Sacramento'
];

// --- LOGIC 3: COMPANY MAPPING FOR HOVER ---
$dept_companies = [
    'San Antonio' => ['AUNZ', 'KEYED', 'LN UK', 'LN KEYED', 'LN ANALYTICAL'],
    'Phoenix'     => ['EP', 'KEYED', 'LN_AUNZ', 'ECRASH', 'S163P33'],
    'Chicago'     => ['ECRASH', 'RIAG'],
    'Miami'       => ['RIAG'],
    'Orlando'     => ['ECRASH'],
    'Denver'      => ['AAAS', 'ESRS'],
    'Dallas'      => ['PLOS', 'HINDAWI'],
    'Gray Room'   => ['Special Projects'],
    'Atlanta'     => ['DPD', 'Wiley AOS'],
    'Indiana'     => ['NAT GEN'], // Added Indiana companies
    'Boston'      => ['Financial Services'],
    'Toronto'     => ['NAT GEN'],
    'Golden State'         => ['Global Sales'],
    'TRN'         => ['Training'],
    'Sacramento'  => ['Public Sector'],
    'Los Angeles' => ['POSTNL', 'RMG', 'XCAGO', 'STM', 'IB', 'DPD', 'ASENDIA']
];

$dept_data = [];
foreach($dept_list as $d) {
    $q = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied FROM production_floor_map WHERE department = ?");
    $q->bind_param("s", $d);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    
    $dept_data[$d] = [
        'total' => $res['total'] ?? 0,
        'occupied' => $res['occupied'] ?? 0
    ];
}

// --- LOGIC 4: FILTERING & REDIRECTION ---
$selected_dept = isset($_GET['dept']) ? $_GET['dept'] : null;

if ($selected_dept === 'San Antonio') {
    header("Location: san_antonio.php");
    exit();
} 

$stations = []; 
if($selected_dept) {
    $stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC");
    $stmt->bind_param("s", $selected_dept);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }
}

// --- MODIFIED RENDER FUNCTION ---
function renderRoom($name, $data, $extraClass = "") {
    global $dept_companies;
    $urlName = urlencode($name);
    
    $link = "prod_map.php?dept=$urlName";

    // MANUAL OVERRIDES
    if ($name === 'San Antonio')  { $link = "san_antonio.php"; }
    if ($name === 'Phoenix')      { $link = "phoenix.php";  }
    if ($name === 'Denver')       { $link = "denver.php";   }
    if ($name === 'Dallas')       { $link = "dallas.php";   }
    if ($name === 'Chicago')      { $link = "chicago.php";  }
    if ($name === 'Orlando')      { $link = "orlando.php";  }
    if ($name === 'Miami')        { $link = "miami.php";    }
    if ($name === 'Gray Room')    { $link = "gray_room.php"; }
    if ($name === 'Atlanta')      { $link = "atlanta.php"; }
    if ($name === 'Indiana')      { $link = "indiana.php"; } 
    if ($name === 'Los Angeles')  { $link = "los_angeles.php"; }
    if ($name === 'Boston')       { $link = "boston.php"; } 
    if ($name === 'Toronto')      { $link = "toronto.php"; }
    // Added override

    $total = $data[$name]['total'] ?? 0;
    $occ = $data[$name]['occupied'] ?? 0;
    $perc = ($total > 0) ? round(($occ / $total) * 100) : 0;
    
    $color = "#f59e0b"; 
    if($perc > 85) $color = "#ef4444"; 

    $companies = $dept_companies[$name] ?? ['No Data'];
    $companyHtml = "";
    foreach($companies as $co) {
        $companyHtml .= "<li><i class='fa-solid fa-check-circle'></i> $co</li>";
    }

    echo "
    <a href='$link' class='map-room $extraClass' style='--room-color: $color;'>
        <div class='room-header'>
            <span class='room-name'>$name</span>
            <span class='room-perc' style='background: {$color}15; color: $color'>$perc%</span>
        </div>
        <div class='occupancy-bar'>
            <div class='fill' style='width: $perc%; background: $color'></div>
        </div>
        <div class='room-footer'>
            <i class='fa-solid fa-users-viewfinder'></i> $occ <span class='slash'>/</span> $total Seats
        </div>
        
        <div class='room-hover-overlay'>
            <div class='hover-content'>
                <span class='hover-title'>Active Companies</span>
                <ul>$companyHtml</ul>
            </div>
        </div>
    </a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Interactive Production Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        :root {
            --primary: #ff6b00; --primary-light: #ff8533; --primary-soft: #fff7ed;
            --bg: #f1f5f9; --card-bg: #ffffff; --text-dark: #1e293b;
            --text-muted: #64748b; --border: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
        .navbar { background: #ff9800; padding: 0.5rem 2rem; display: flex; align-items: center; justify-content: space-between; height: 60px; box-sizing: border-box; box-shadow: var(--shadow-sm); }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .btn-back-main { text-decoration: none; color: #fff; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .container { max-width: 1600px; margin: 0 auto; padding: 1rem 2rem; height: calc(100vh - 60px); display: flex; flex-direction: column; box-sizing: border-box; }
        h1 { font-weight: 800; font-size: 1.5rem; margin: 0; color: var(--text-dark); }
        .floor-plan { display: flex; flex-direction: column; gap: 1.5rem; overflow-y: auto; padding-bottom: 2rem; }
        .map-row { display: flex; gap: 1.2rem; align-items: stretch; }
        .hallway { background: #f59e0b; color: #000000; text-align: center; padding: 1rem; font-weight: 800; font-size: 2rem; letter-spacing: 0.8rem; border-radius: 12px; text-transform: uppercase; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2); }
        
        .map-room { 
            background: var(--card-bg); 
            border: 1px solid var(--border); 
            border-left: 4px solid var(--room-color); 
            text-decoration: none; color: inherit; 
            padding: 1.4rem; border-radius: 16px; 
            display: flex; flex-direction: column; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            position: relative; overflow: hidden;
        }
        .map-room:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        
        .room-hover-overlay {
            position: absolute; inset: 0; background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(4px); display: flex; align-items: center;
            padding: 1.2rem; transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 5;
        }
        .map-room:hover .room-hover-overlay { transform: translateY(0); }
        
        .hover-content { width: 100%; color: white; }
        .hover-title { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #ff9800; margin-bottom: 8px; letter-spacing: 0.05rem; }
        .hover-content ul { list-style: none; padding: 0; margin: 0; }
        .hover-content ul li { font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .hover-content ul li i { font-size: 0.7rem; color: #22c55e; }

        .room-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .room-name { font-weight: 700; font-size: 1rem; }
        .occupancy-bar { height: 8px; background: #f1f5f9; border-radius: 10px; margin: 0.6rem 0; overflow: hidden; }
        .fill { height: 100%; border-radius: 10px; transition: width 1s ease; }
        .room-footer { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .overview-search-container { position: relative; display: flex; align-items: center; }
        .overview-search-input { padding: 0.8rem 1.5rem 0.8rem 3.2rem; width: 350px; border-radius: 14px; border: 2px solid transparent; background: #fff; font-family: inherit; font-size: 0.9rem; outline: none; transition: all 0.3s ease; box-shadow: var(--shadow-sm); }
        .overview-search-input:focus { border-color: var(--primary); width: 400px; box-shadow: var(--shadow-md); }
        .overview-search-icon { position: absolute; left: 1.2rem; color: var(--primary); font-size: 1rem; pointer-events: none; }
        
        /* ADJUSTED WIDTHS FOR BETTER SPACING */
        .w-small { width: 140px; } 
        .w-med { width: 210px; } 
        .w-large { width: 260px; } 
        .w-wide { width: 350px; }
        
        .push-right { margin-left: auto; }
        .map-grid-container { background: #fff; padding: 2rem; border-radius: 24px; border: 1px solid var(--border); box-shadow: var(--shadow-md); flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .map-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1.5rem; width: 100%; }
        .seat-box { padding: 1.2rem; border-radius: 14px; text-align: center; border: 1px solid var(--border); background: #f8fafc; transition: all 0.25s; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 110px; aspect-ratio: 1 / 1; box-sizing: border-box; }
        .Occupied { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; border-bottom: 5px solid #10b981; }
        .Vacant { background: #ffffff; border: 1px dashed #cbd5e1; color: #94a3b8; }
        .Repair { background: #fff1f2; color: #9f1239; border: 1px solid #ef4444; border-bottom: 5px solid #ef4444; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 420px; padding: 2.5rem; border-radius: 28px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="index.php" class="btn-back-main">
            <i class="fa-solid fa-arrow-left"></i> OJTBox | Production Map
        </a>
    </div>
</nav>

<div class="container">

    <?php if(!$selected_dept): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1>Floor Overview</h1>
            <div class="overview-search-container">
                <i class="fa-solid fa-magnifying-glass overview-search-icon"></i>
                <input type="text" id="overviewSearch" class="overview-search-input" placeholder="Search for Hostname...">
            </div>
        </div>
        
        <div class="floor-plan">
            <div class="map-row">
                <?php renderRoom('San Antonio', $dept_data, 'w-med'); ?>
                <?php renderRoom('Phoenix', $dept_data, 'w-med'); ?>
                <div style="width: 1rem"></div> 
                <?php renderRoom('Denver', $dept_data, 'w-med'); ?>
                <?php renderRoom('Dallas', $dept_data, 'w-med'); ?>
                <div style="width: 1rem"></div>
                <?php renderRoom('Los Angeles', $dept_data, 'w-med'); ?>
                <div style="display: flex; flex-direction: column; gap: 1.2rem;" class="push-right">
                    <div style="display: flex; gap: 1.2rem;">
                        <?php renderRoom('Golden State', $dept_data, 'w-small'); ?>
                        <?php renderRoom('TRN', $dept_data, 'w-small'); ?>
                    </div>
                    <?php renderRoom('Sacramento', $dept_data, 'w-wide'); ?>
                </div>
            </div>

            <div class="hallway">CENTRAL HALLWAY</div>

            <div class="map-row" style="align-items: flex-start;">
                <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <?php renderRoom('Chicago', $dept_data, 'w-med'); ?>
                    <?php renderRoom('Miami', $dept_data, 'w-med'); ?>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <?php renderRoom('Orlando', $dept_data, 'w-med'); ?>
                    <?php renderRoom('Gray Room', $dept_data, 'w-med'); ?>
                </div>

                <?php renderRoom('Atlanta', $dept_data, 'w-large'); ?>
                <?php renderRoom('Indiana', $dept_data, 'w-large'); ?>
                <?php renderRoom('Boston', $dept_data, 'w-large'); ?>
                <?php renderRoom('Toronto', $dept_data, 'w-wide'); ?>
            </div>
        </div>

    <?php else: ?>
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <a href="prod_map.php" style="text-decoration: none; color: var(--primary); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                    <i class="fa-solid fa-arrow-left-long"></i> RETURN TO OVERVIEW
                </a>
                <h1 style="margin: 0; font-size: 1.6rem; letter-spacing: -0.02em;">
                    <?php echo htmlspecialchars($selected_dept); ?> Department
                </h1>
            </div>
            
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
                <input type="text" id="searchInput" placeholder="Search for Hostname..." onkeyup="searchMap()" 
                       style="padding: 0.8rem 1.5rem 0.8rem 3rem; width: 320px; border-radius: 14px; border: 1px solid var(--border); font-family: inherit; font-size: 0.9rem; outline: none;">
            </div>
        </div>

        <div class="map-grid-container">
            <div class="map-grid" id="mapGrid">
                <?php foreach($stations as $row): ?>
                    <div class="seat-box <?php echo htmlspecialchars($row['status']); ?>" 
                         data-hostname="<?php echo strtolower($row['hostname']); ?>" 
                         onclick="openEdit(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        <i class="fa-solid <?php echo ($row['status'] == 'Repair') ? 'fa-screwdriver-wrench' : 'fa-desktop'; ?>"></i>
                        <strong><?php echo htmlspecialchars($row['cubicle_no']); ?></strong>
                        <span><?php echo htmlspecialchars($row['hostname'] ?: 'VACANT'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="seatTitle" style="margin: 0 0 2rem 0; font-weight: 800; color: var(--primary); font-size: 1.4rem;">Edit Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <input type="hidden" name="cubicle_no_hidden" id="seatCubicleHidden">
            <input type="hidden" name="department" id="seatDept">
            <label>Cubicle Assignment</label>
            <input type="text" id="seatCubicle" readonly style="background: #f1f5f9; width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
            <label>Device Hostname</label>
            <input type="text" name="hostname" id="seatHost" placeholder="Enter name" style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
            <label>Campaign / User Notes</label>
            <textarea name="campaign" id="seatCamp" rows="4" style="resize: none; width: 100%; border-radius: 12px; border: 1px solid var(--border); padding: 1rem;"></textarea>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 1rem;">
                <button type="submit" name="update_seat" style="width:100%; padding: 1rem; background: var(--primary); color: #fff; border:none; border-radius:14px; font-weight:800; cursor:pointer;">SAVE UPDATES</button>
                <button type="button" onclick="closeModal()" style="width:100%; padding: 0.8rem; background:none; border:none; color: var(--text-muted); font-weight:600; cursor:pointer;">DISCARD</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEdit(data) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = data.id;
        document.getElementById('seatTitle').innerText = "Edit: " + data.cubicle_no;
        document.getElementById('seatDept').value = data.department;
        document.getElementById('seatCubicle').value = data.cubicle_no;
        document.getElementById('seatHost').value = data.hostname || '';
        document.getElementById('seatCamp').value = data.campaign || '';
    }
    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            seats[i].style.opacity = host.includes(input) ? "1" : "0.1";
            seats[i].style.pointerEvents = host.includes(input) ? "auto" : "none";
        }
    }
</script>
</body>
</html>