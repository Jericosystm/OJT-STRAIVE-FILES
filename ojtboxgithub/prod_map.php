<?php
session_start();

// Security Check: If 'user_id' isn't set, they aren't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Now we can safely get the username (make sure you set this in auth.php!)
$username = $_SESSION['username'] ?? 'User'; 
?>
<?php
/**
 * OJTBox Production Floor Map
 * Latest Revision: Fixed Grid Sizing and Spacing Logic
 * Total Lines: Aiming for 470+
 */

require_once 'db.php';

// --- LOGIC 1: UPDATE SEAT DATA ---
// This handles the form submission from the modal
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = $_POST['hostname'];
    $campaign = $_POST['campaign'] ?? '';
    $department = $_POST['department'] ?? 'San Antonio';
    
    // Status Logic: 
    // If hostname input has value, status is "Occupied"
    // If hostname is empty/blank, status is "Vacant"
    $status = (!empty(trim($hostname))) ? 'Occupied' : 'Vacant';

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=?, department=? WHERE id=?");
    $stmt->bind_param("ssssi", $hostname, $status, $campaign, $department, $id);
    
    if($stmt->execute()) {
        // Redirect back to the specific department view
        header("Location: prod_map.php?dept=" . urlencode($department));
        exit();
    }
}

// --- LOGIC 2: FETCH DEPARTMENT STATISTICS ---
// Used for the overview page cards
$dept_list = [
    'San Antonio', 
    'Phoenix', 
    'Denver', 
    'Dallas', 
    'Los Angeles', 
    'Chicago', 
    'Atlanta', 
    'Boston', 
    'Toronto', 
    'GSW', 
    'TRN', 
    'Miami', 
    'Gray Room', 
    'Sacramento'
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

// --- LOGIC 3: FILTERING AND GRID GENERATION ---
$selected_dept = isset($_GET['dept']) ? $_GET['dept'] : null;
$stations = []; 

if($selected_dept) {
    // Fetch all stations assigned to the selected department
    $stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY grid_row, grid_col");
    $stmt->bind_param("s", $selected_dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }

    // --- FALLBACK: 49 STATIONS SIMULATION ---
    // Generates dummy data if the database is currently empty for testing
    if ($selected_dept == 'San Antonio' && count($stations) == 0) {
        $statuses = ['Occupied', 'Vacant', 'Repair'];
        for ($i = 1; $i <= 49; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];
            $stations[] = [
                'id' => $i,
                'cubicle_no' => "SA-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'hostname' => ($randomStatus == 'Vacant') ? '' : "SA-PC-" . $i,
                'status' => $randomStatus,
                'campaign' => ($randomStatus == 'Vacant') ? '' : 'San Antonio Campaign',
                'department' => 'San Antonio'
            ];
        }
    }
}

/**
 * renderRoom Function
 * Generates the HTML for department cards on the main dashboard
 */
function renderRoom($name, $data, $extraClass = "") {
    $urlName = urlencode($name);
    $total = $data[$name]['total'] ?? 0;
    $occ = $data[$name]['occupied'] ?? 0;
    $perc = ($total > 0) ? round(($occ / $total) * 100) : 0;
    
    // Dynamic color coding based on density
    $color = "#f59e0b"; 
    if($perc > 50) $color = "#f59e0b"; 
    if($perc > 85) $color = "#ef4444"; 

    echo "
    <a href='prod_map.php?dept=$urlName' class='map-room $extraClass' style='--room-color: $color;'>
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

        /* Root Variables for Theme Consistency */
        :root {
            --primary: #ff6b00;
            --primary-light: #ff8533;
            --primary-soft: #fff7ed;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* Base Body Reset */
        html, body { 
            height: 100%; 
            margin: 0; 
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); 
            color: var(--text-dark); 
            overflow: hidden; 
        }
        
        /* Navbar Styling */
        .navbar { 
            background: #ff9800; 
            padding: 0.5rem 2rem; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            height: 60px; 
            box-sizing: border-box; 
            box-shadow: var(--shadow-sm); 
        }

        .nav-left { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }

        .btn-back-main { 
            text-decoration: none; 
            color: #fff; 
            font-size: 1.1rem; 
            font-weight: 700; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            transition: opacity 0.2s; 
        }

        .btn-back-main:hover { 
            opacity: 0.8; 
        }
        
        /* Main Application Container */
        .container { 
            max-width: 1600px; 
            margin: 0 auto; 
            padding: 1rem 2rem; 
            height: calc(100vh - 60px); 
            display: flex; 
            flex-direction: column; 
            box-sizing: border-box;
        }

        h1 { 
            font-weight: 800; 
            font-size: 1.5rem; 
            margin: 0 0 1rem 0; 
            color: var(--text-dark); 
        }

        /* Overview Page Layout */
        .floor-plan { 
            display: flex; 
            flex-direction: column; 
            gap: 1.5rem; 
            overflow-y: auto; 
        }

        .map-row { 
            display: flex; 
            gap: 1.2rem; 
            align-items: stretch; 
        }

        .hallway { 
            background: #f59e0b; 
            color: #000000; 
            text-align: center; 
            padding: 1rem; 
            font-weight: 800; 
            font-size: 2rem; 
            letter-spacing: 0.8rem; 
            border-radius: 12px; 
            text-transform: uppercase; 
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.2); 
        }

        /* Room Selection Cards */
        .map-room {
            background: var(--card-bg); 
            border: 1px solid var(--border); 
            border-left: 4px solid var(--room-color);
            text-decoration: none; 
            color: inherit; 
            padding: 1.4rem; 
            border-radius: 16px;
            display: flex; 
            flex-direction: column; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .room-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1rem; 
        }

        .room-name { 
            font-weight: 700; 
            font-size: 1rem; 
        }

        .occupancy-bar { 
            height: 8px; 
            background: #f1f5f9; 
            border-radius: 10px; 
            margin: 0.6rem 0; 
            overflow: hidden; 
        }

        .fill { 
            height: 100%; 
            border-radius: 10px; 
            transition: width 1s ease; 
        }

        .room-footer { 
            font-size: 0.85rem; 
            font-weight: 600; 
            color: var(--text-muted); 
            display: flex; 
            align-items: center; 
            gap: 6px; 
        }

        /* Dimension Helpers */
        .w-small { width: 150px; }
        .w-med { width: 210px; }
        .w-large { width: 280px; }
        .w-wide { width: 400px; }
        .push-right { margin-left: auto; }

        /* --- GRID SYSTEM: FIXED SIZES AND SPACING --- */
        .map-grid-container { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 24px; 
            border: 1px solid var(--border); 
            box-shadow: var(--shadow-md); 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
            overflow-y: auto; 
            scrollbar-width: thin;
        }
        
        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr); 
            grid-template-rows: repeat(7, 1fr); 
            gap: 1.5rem; /* Space between cubicles to prevent clutter */
            width: 100%;
            margin: 0 auto;
        }
        
        .seat-box {
            padding: 1.2rem; 
            border-radius: 14px; 
            text-align: center; 
            border: 1px solid var(--border); 
            background: #f8fafc; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            min-height: 110px; /* Fixed Height */
            aspect-ratio: 1 / 1; /* Fixed Aspect Ratio */
            box-sizing: border-box;
            position: relative;
        }
        
        .seat-box:hover { 
            transform: translateY(-5px); 
            border-color: var(--primary); 
            background: #fff; 
            z-index: 10; 
            box-shadow: var(--shadow-md);
        }

        .seat-box i { 
            font-size: 1.3rem !important; 
            margin-bottom: 10px !important; 
            opacity: 0.5; 
        }

        .seat-box strong { 
            font-size: 0.9rem !important; 
            font-weight: 800;
            line-height: 1; 
            margin-bottom: 5px;
        }

        .seat-box span { 
            font-size: 0.75rem !important; 
            font-weight: 600; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            max-width: 100%; 
            color: var(--text-muted);
        }
        
        /* Seat Status Visual Identifiers */
        .Occupied { 
            background: #ecfdf5; 
            color: #065f46; 
            border: 1px solid #10b981;
            border-bottom: 5px solid #10b981; 
        }
        
        .Occupied span { color: #059669; }

        .Vacant { 
            background: #ffffff; 
            border: 1px dashed #cbd5e1; 
            color: #94a3b8; 
        }

        .Repair { 
            background: #fff1f2; 
            color: #9f1239; 
            border: 1px solid #ef4444;
            border-bottom: 5px solid #ef4444; 
        }

        /* Modal Visuals */
        .modal-overlay { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: rgba(15, 23, 42, 0.65); 
            backdrop-filter: blur(8px); 
            z-index: 1000; 
        }

        .modal-content { 
            background: #fff; 
            width: 420px; 
            padding: 2.5rem; 
            border-radius: 28px; 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-content label { 
            font-size: 0.75rem; 
            font-weight: 800; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            display: block; 
            margin-bottom: 8px; 
            letter-spacing: 0.05em;
        }

        .modal-content input, 
        .modal-content textarea { 
            width: 100%; 
            padding: 1rem; 
            border-radius: 12px; 
            border: 1px solid var(--border); 
            margin-bottom: 1.5rem; 
            box-sizing: border-box; 
            font-family: inherit; 
            font-size: 0.95rem;
            background: #fcfcfc;
        }

        .modal-content input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">

    <?php if(!$selected_dept): ?>
        <h1>Floor Overview</h1>
        
        <div class="floor-plan">
            <div class="map-row">
                <?php renderRoom('San Antonio', $dept_data, 'w-med'); ?>
                <?php renderRoom('Phoenix', $dept_data, 'w-med'); ?>
                
                <div style="width: 2rem"></div> 
                <?php renderRoom('Denver', $dept_data, 'w-med'); ?>
                <?php renderRoom('Dallas', $dept_data, 'w-med'); ?>
                
                <div style="width: 2rem"></div>
                <?php renderRoom('Los Angeles', $dept_data, 'w-med'); ?>

                <div style="display: flex; flex-direction: column; gap: 1.2rem;" class="push-right">
                    <div style="display: flex; gap: 1.2rem;">
                        <?php renderRoom('GSW', $dept_data, 'w-small'); ?>
                        <?php renderRoom('TRN', $dept_data, 'w-small'); ?>
                    </div>
                    <?php renderRoom('Sacramento', $dept_data, 'w-wide'); ?>
                </div>
            </div>

            <div class="hallway">CENTRAL HALLWAY</div>

            <div class="map-row" style="align-items: flex-start;">
                <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <?php renderRoom('Chicago', $dept_data, 'w-wide'); ?>
                    <div style="display: flex; gap: 1.2rem;">
                        <?php renderRoom('Miami', $dept_data, 'w-med'); ?>
                        <?php renderRoom('Gray Room', $dept_data, 'w-med'); ?>
                    </div>
                </div>

                <?php renderRoom('Atlanta', $dept_data, 'w-wide'); ?>
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
                       style="padding: 0.8rem 1.5rem 0.8rem 3rem; width: 320px; border-radius: 14px; border: 1px solid var(--border); font-family: inherit; font-size: 0.9rem; outline: none; transition: border-color 0.2s;">
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
            <input type="hidden" name="department" id="seatDept">
            
            <label>Cubicle Assignment</label>
            <input type="text" id="seatCubicle" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed; border-style: dashed;">
            
            <label>Physical Switch Port</label>
            <input type="text" name="switch_port" id="seatPort" placeholder="e.g. SW01-P45">

            <label>Device Hostname</label>
            <input type="text" name="hostname" id="seatHost" placeholder="Enter name or leave blank for Vacant">
            
            <div style="background: #fff7ed; padding: 10px 15px; border-radius: 10px; border: 1px solid #ffedd5; margin-bottom: 1.5rem;">
                <p style="font-size: 0.7rem; color: #9a3412; margin: 0; font-weight: 600;">
                    <i class="fa-solid fa-circle-info"></i>Status
                </p>
                <p style="font-size: 0.65rem; color: #c2410c; margin: 3px 0 0 0;">
                    Clearing the hostname will mark this cubicle as Vacant.
                </p>
            </div>

            <label>Campaign / User Notes</label>
            <textarea name="campaign" id="seatCamp" rows="4" style="resize: none;" placeholder="Enter campaign name or specific user instructions..."></textarea>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="submit" name="update_seat" style="width:100%; padding: 1rem; background: var(--primary); color: #fff; border:none; border-radius:14px; font-weight:800; cursor:pointer; font-size: 1rem; transition: background 0.2s;">
                    SAVE UPDATES
                </button>
                <button type="button" onclick="closeModal()" style="width:100%; padding: 0.8rem; background:none; border:none; color: var(--text-muted); font-weight:600; cursor:pointer; font-size: 0.9rem;">
                    DISCARD CHANGES
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * openEdit: Opens the modal and populates it with station data
     */
    function openEdit(data) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = data.id;
        document.getElementById('seatTitle').innerText = "Edit: " + data.cubicle_no;
        document.getElementById('seatDept').value = data.department;
        
        // Populate form inputs
        document.getElementById('seatCubicle').value = data.cubicle_no;
        document.getElementById('seatPort').value = data.switch_port || ''; 
        document.getElementById('seatHost').value = data.hostname || '';
        document.getElementById('seatCamp').value = data.campaign || '';
    }

    /**
     * closeModal: Hides the modal overlay
     */
    function closeModal() { 
        document.getElementById('modalOverlay').style.display = 'none'; 
    }
    
    /**
     * searchMap: Real-time filtering based on Hostname input
     */
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            // Uses opacity for filtering to preserve the 7x7 grid layout
            if(host.includes(input)) {
                seats[i].style.opacity = "1";
                seats[i].style.pointerEvents = "auto";
            } else {
                seats[i].style.opacity = "0.1";
                seats[i].style.pointerEvents = "none";
            }
        }
    }
</script>

<style>
    /* Keyframe for the pulsing Live Indicator */
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.98); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    /* Custom Scrollbar for the Grid */
    .map-grid-container::-webkit-scrollbar { width: 8px; }
    .map-grid-container::-webkit-scrollbar-track { background: transparent; }
    .map-grid-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

</body>
</html>