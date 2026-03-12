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
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = $_POST['host_name'];
    $switch_port = $_POST['switch_port'] ?? 'N/A'; // Get the new field
    // ... rest of your variables

    // Add switch_port to the prepared statement
    $stmt = $conn->prepare("UPDATE all_assets_master SET host_name=?, switch_port=?, status=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("sssi", $host_name, $switch_port, $status, $id);
    // ...
}

// --- LOGIC 2: FETCH DEPARTMENT STATISTICS ---
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
    'Indiana'     => ['NAT GEN'],
    'Boston'      => ['Financial Services'],
    'Toronto'     => ['NAT GEN'],
    'Golden State' => ['Global Sales'],
    'TRN'         => ['Training'],
    'Sacramento'  => ['Public Sector'],
    'Los Angeles' => ['POSTNL', 'RMG', 'XCAGO', 'STM', 'IB', 'DPD', 'ASENDIA']
];

$dept_data = [];
foreach($dept_list as $d) {
    $q = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied FROM all_assets_master WHERE department = ?");
    $q->bind_param("s", $d);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    
    $dept_data[$d] = [
        'total' => $res['total'] ?? 0,
        'occupied' => $res['occupied'] ?? 0
    ];
}

$selected_dept = isset($_GET['dept']) ? $_GET['dept'] : null;

$stations = []; 
if($selected_dept) {
    $stmt = $conn->prepare("SELECT * FROM all_assets_master WHERE department = ? ORDER BY id ASC");
    $stmt->bind_param("s", $selected_dept);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }
}

// --- RENDER FUNCTION FOR ROOM CARDS ---
function renderRoom($name, $data, $extraClass = "") {
    global $dept_companies;
    $urlName = urlencode($name);
    
    // Check if separate file exists, otherwise use query param
    $link = "prod_map.php?dept=$urlName";
    
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
    <title>OJTBox | Interactive Production Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        :root {
            --primary: #ff6b00; --bg: #f1f5f9; --card-bg: #ffffff; --text-dark: #1e293b;
            --text-muted: #64748b; --border: #e2e8f0; --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
        .container { max-width: 1600px; margin: 0 auto; padding: 1rem 2rem; height: 100vh; display: flex; flex-direction: column; box-sizing: border-box; }
        .floor-plan { display: flex; flex-direction: column; gap: 1.5rem; overflow-y: auto; padding-bottom: 2rem; }
        .map-row { display: flex; gap: 1.2rem; align-items: stretch; }
        .hallway { background: #f59e0b; color: #000; text-align: center; padding: 1rem; font-weight: 800; font-size: 2rem; letter-spacing: 0.8rem; border-radius: 12px; text-transform: uppercase; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2); margin: 10px 0; }
        
        .map-room { 
            background: var(--card-bg); border: 1px solid var(--border); border-left: 4px solid var(--room-color); 
            text-decoration: none; color: inherit; padding: 1.4rem; border-radius: 16px; 
            display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;
        }
        .map-room:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .room-hover-overlay { position: absolute; inset: 0; background: rgba(30, 41, 59, 0.95); backdrop-filter: blur(4px); display: flex; align-items: center; padding: 1.2rem; transform: translateY(100%); transition: transform 0.4s ease; z-index: 5; }
        .map-room:hover .room-hover-overlay { transform: translateY(0); }
        .hover-content { width: 100%; color: white; }
        .hover-title { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #ff9800; margin-bottom: 8px; }
        .hover-content ul { list-style: none; padding: 0; margin: 0; }
        .hover-content ul li { font-size: 0.85rem; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }

        .room-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .occupancy-bar { height: 8px; background: #f1f5f9; border-radius: 10px; margin: 0.6rem 0; overflow: hidden; }
        .fill { height: 100%; transition: width 1s ease; }
        
        /* Grid styling */
        .map-grid-container { background: #fff; padding: 2rem; border-radius: 24px; border: 1px solid var(--border); box-shadow: var(--shadow-md); flex-grow: 1; overflow-y: auto; }
        .map-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 1.5rem; }
        .seat-box { padding: 1.2rem; border-radius: 14px; text-align: center; border: 1px solid var(--border); background: #f8fafc; cursor: pointer; transition: all 0.2s; }
        .Occupied { background: #ecfdf5; border-bottom: 5px solid #10b981; }
        .Vacant { background: #ffffff; border: 1px dashed #cbd5e1; }
        
        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 420px; padding: 2.5rem; border-radius: 28px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        
        .w-small { width: 140px; } .w-med { width: 210px; } .w-large { width: 260px; } .w-wide { width: 350px; }
        .push-right { margin-left: auto; }
    </style>
</head>
<body>

<div class="container">
    <?php if(!$selected_dept): ?>
        <h1 style="font-size: 2rem; margin-bottom: 1.5rem; font-weight: 800;">Production Floor Overview</h1>
        <div class="floor-plan">
            <div class="map-row">
                <?php renderRoom('San Antonio', $dept_data, 'w-med'); ?>
                <?php renderRoom('Phoenix', $dept_data, 'w-med'); ?>
                <div style="width: 1rem"></div> 
                <?php renderRoom('Denver', $dept_data, 'w-med'); ?>
                <?php renderRoom('Dallas', $dept_data, 'w-med'); ?>
                <div style="width: 1rem"></div>
                <?php renderRoom('Los Angeles', $dept_data, 'w-med'); ?>
                <div class="push-right" style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <div style="display: flex; gap: 1.2rem;">
                        <?php renderRoom('Golden State', $dept_data, 'w-small'); ?>
                        <?php renderRoom('TRN', $dept_data, 'w-small'); ?>
                    </div>
                    <?php renderRoom('Sacramento', $dept_data, 'w-wide'); ?>
                </div>
            </div>

            <div class="hallway">CENTRAL HALLWAY</div>

            <div class="map-row">
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
                <a href="prod_map.php" style="text-decoration: none; color: var(--primary); font-weight: 700; font-size: 0.9rem;">← RETURN TO OVERVIEW</a>
                <h1 style="margin-top: 5px;"><?php echo htmlspecialchars($selected_dept); ?></h1>
            </div>
            <input type="text" id="searchInput" placeholder="Search Hostname..." onkeyup="searchMap()" style="padding: 12px; border-radius: 12px; border: 1px solid var(--border); width: 300px;">
        </div>

        <div class="map-grid" id="mapGrid">
    <?php foreach($stations as $row): ?>
        <div class="seat-box <?php echo htmlspecialchars($row['status']); ?>" 
             data-host_name="<?php echo htmlspecialchars(strtolower($row['host_name'] ?? '')); ?>" 
             onclick='openEdit(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
            
            <i class="fa-solid <?php echo ($row['status'] == 'Repair') ? 'fa-screwdriver-wrench' : 'fa-desktop'; ?>"></i>
            
            <strong><?php echo htmlspecialchars($row['cubicle_no']); ?></strong>
            <span class="host-text"><?php echo htmlspecialchars($row['host_name'] ?: 'VACANT'); ?></span>
            
            <div class="port-badge">
                <i class="fa-solid fa-plug"></i> <?php echo htmlspecialchars($row['switch_port'] ?: 'N/A'); ?>
            </div>
            <div class="port-info">
    <i class="fa-solid fa-network-wired"></i> 
    Port: <?php echo htmlspecialchars($row['switch_port'] ?? 'N/A'); ?>
</div>
            
            <small class="status-label"><?php echo htmlspecialchars($row['status']); ?></small>
        </div>
    <?php endforeach; ?>
</div>
    <?php endif; ?>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="seatTitle" style="color: var(--primary); margin-top: 0;">Edit Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <input type="hidden" name="department" id="seatDept">
            <label style="font-size: 0.8rem; font-weight: bold;">Hostname</label>
            <input type="text" name="host_name" id="seatHost" style="width:100%; padding:10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd;">
            <label style="font-size: 0.8rem; font-weight: bold;">switch_port Notes</label>
            <textarea name="switch_port" id="seatCamp" style="width:100%; height:80px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd; resize: none;"></textarea>
            <button type="submit" name="update_seat" style="width:100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">SAVE CHANGES</button>
            <button type="button" onclick="closeModal()" style="width:100%; background: none; border: none; margin-top: 10px; color: #64748b; cursor: pointer;">Discard Changes</button>
        </form>
    </div>
</div>

<script>
    function openEdit(data) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = data.id;
        document.getElementById('seatTitle').innerText = "Station: " + data.cubicle_no;
        document.getElementById('seatDept').value = data.department;
        document.getElementById('seatHost').value = data.host_name || '';
        document.getElementById('seatCamp').value = data.switch_port || '';
    }
    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-host_name');
            seats[i].style.opacity = host.includes(input) ? "1" : "0.2";
        }
    }
</script>
</body>
</html>