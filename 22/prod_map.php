<?php
session_start();

// Existing session check...
$username = $_SESSION['username'] ?? 'User'; 
$user_role = $_SESSION['role'] ?? 'EUC User'; // Default to User if not set

// Determine the back link based on role
$back_link = ($user_role === 'EUC Admin') ? 'index_admin.php' : 'index_user.php';
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
    $hostname = $_POST['hostname'];
    $campaign = $_POST['campaign'] ?? '';
    $department = $_POST['department'] ?? 'San Antonio';
    
    $status = (!empty(trim($hostname))) ? 'Occupied' : 'Vacant';

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=?, department=? WHERE id=?");
    $stmt->bind_param("ssssi", $hostname, $status, $campaign, $department, $id);
    
    if($stmt->execute()) {
        header("Location: prod_map.php?dept=" . urlencode($department));
        exit();
    }
}

// --- LOGIC 2: FETCH DEPARTMENT STATISTICS ---
$dept_list = [
    'San Antonio', 'Phoenix', 'Denver', 'Dallas', 'Los Angeles', 
    'Chicago', 'Orlando', 'Atlanta', 'Indiana', 'Boston', 'Toronto', 'Golden State', 
    'Training Room', 'Miami', 'Gray Room', 'Sacramento'
];

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
    'Boston'      => ['NAT GEN'],
    'Toronto'     => ['NAT GEN'],
    'Golden State'=> ['Global Sales'],
    'Training Room'=>['Training'],
    'Sacramento'  => ['STP', 'WKTA'],
    'Los Angeles' => ['POSTNL', 'RMG', 'XCAGO', 'STM', 'IB', 'ASENDIA', 'DPD']
];

$dept_data = [];
foreach($dept_list as $d) {
    $q = $conn->prepare("SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied,
                        GROUP_CONCAT(hostname SEPARATOR ',') as all_hosts 
                        FROM production_floor_map WHERE department = ?");
    $q->bind_param("s", $d);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    
    $dept_data[$d] = [
        'total' => $res['total'] ?? 0,
        'occupied' => $res['occupied'] ?? 0,
        'hostnames' => strtolower($res['all_hosts'] ?? '') 
    ];
}

// --- LOGIC 3: FILTERING & REDIRECTION ---
$selected_dept = isset($_GET['dept']) ? $_GET['dept'] : null;

$overrides = [
    'San Antonio' => 'san_antonio.php', 'Phoenix' => 'phoenix.php', 'Denver' => 'denver.php',
    'Dallas' => 'dallas.php', 'Chicago' => 'chicago.php', 'Orlando' => 'orlando.php',
    'Miami' => 'miami.php', 'Gray Room' => 'gray_room.php', 'Atlanta' => 'atlanta.php',
    'Indiana' => 'indiana.php', 'Los Angeles' => 'los_angeles.php', 'Boston' => 'boston.php',
    'Toronto' => 'toronto.php', 'Sacramento' => 'sacramento.php', 'Golden State' => 'golden_state.php',
    'Training Room' => 'training_room.php'
];

if ($selected_dept && isset($overrides[$selected_dept])) {
    header("Location: " . $overrides[$selected_dept]);
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

// --- RENDER FUNCTION ---
function renderRoom($name, $data, $extraClass = "") {
    global $dept_companies, $overrides;
    
    $link = "prod_map.php?dept=" . urlencode($name);
    if (isset($overrides[$name])) { $link = $overrides[$name]; }

    $total = $data[$name]['total'] ?? 0;
    $occ = $data[$name]['occupied'] ?? 0;
    $hosts = $data[$name]['hostnames'] ?? ''; 
    $perc = ($total > 0) ? round(($occ / $total) * 100) : 0;
    
    $color = ($perc > 85) ? "#ef4444" : "#f59e0b"; 

    $companies = $dept_companies[$name] ?? ['No Data'];
    $companyHtml = "";
    foreach($companies as $co) {
        $companyHtml .= "<li><i class='fa-solid fa-check-circle'></i> $co</li>";
    }

    echo "
    <a href='$link' class='map-room $extraClass' style='--room-color: $color;' data-hosts='$hosts' data-deptname='$name'>
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
        
        /* --- MODIFIED DARK MODE (Lower Contrast) --- */
        :root {
            --primary: #ff6b00; 
            --primary-light: #ff8533; 
            --primary-soft: rgba(255, 107, 0, 0.1);
            --bg: #050505;           /* Softened from pure black */
            --card-bg: #17181a;      /* Lighter slate for cards */
            --text-dark: #f1f5f9;    /* Off-white to reduce glare */
            --text-muted: #94a3b8; 
            --border: #334155;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.2);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            --input-bg: #17181a;
        }

        /* --- LIGHT MODE OVERRIDES --- */
        body.light-mode {
            --bg: #f1f5f9; 
            --card-bg: #ffffff; 
            --text-dark: #1e293b;
            --text-muted: #64748b; 
            --border: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --input-bg: #ffffff;
        }

        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; transition: background 0.4s ease, color 0.4s ease; }
        
        .container { max-width: 1600px; margin: 0 auto; padding: 1rem 2rem; height: calc(100vh - 72px); display: flex; flex-direction: column; box-sizing: border-box; position: relative; }
        h1 { font-weight: 800; font-size: 1.5rem; margin: 0; color: var(--text-dark); }
        
        .floor-plan { display: flex; flex-direction: column; gap: 1.5rem; overflow-y: auto; padding-bottom: 2rem; }
        .map-row { display: flex; gap: 1.2rem; align-items: stretch; }
        
        /* Softened Hallway Contrast */
        .hallway { 
            background: #d97706; /* Slightly deeper amber */
            color: #ffffff;      /* Changed from black to white for better readability */
            text-align: center; padding: 1rem; font-weight: 800; font-size: 2rem; letter-spacing: 0.8rem; border-radius: 12px; text-transform: uppercase; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2); 
        }
        
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
        .map-room:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--primary); }
        
        .room-hover-overlay {
            position: absolute; inset: 0; background: rgba(15, 23, 42, 0.9);
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
        .occupancy-bar { height: 8px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 0.6rem 0; overflow: hidden; }
        .fill { height: 100%; border-radius: 10px; transition: width 1s ease; }
        .room-footer { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        
        .overview-search-container { position: relative; display: flex; align-items: center; z-index: 1001; }
        .overview-search-input { padding: 0.8rem 1.5rem 0.8rem 3.2rem; width: 350px; border-radius: 14px; border: 2px solid var(--border); background: var(--input-bg); color: var(--text-dark); font-family: inherit; font-size: 0.9rem; outline: none; transition: all 0.3s ease; box-shadow: var(--shadow-sm); }
        .overview-search-input:focus { border-color: var(--primary); width: 400px; box-shadow: var(--shadow-md); }
        .overview-search-icon { position: absolute; left: 1.2rem; color: var(--primary); font-size: 1rem; pointer-events: none; }
        
        #searchPanel {
            position: absolute; top: 75px; right: 2rem; width: 400px; max-height: 500px;
            background: var(--card-bg); border-radius: 20px; box-shadow: var(--shadow-md);
            border: 1px solid var(--border); display: none; flex-direction: column;
            z-index: 1000; overflow: hidden; animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .panel-header { padding: 1rem 1.5rem; background: rgba(255,107,0,0.1); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .panel-header span { font-weight: 800; font-size: 0.9rem; color: var(--primary); }
        #searchResults { overflow-y: auto; padding: 0.5rem; }
        .search-item { 
            padding: 1rem; border-radius: 12px; margin-bottom: 5px; 
            display: flex; justify-content: space-between; align-items: center;
            transition: background 0.2s; border: 1px solid transparent;
            color: var(--text-dark);
        }
        .search-item:hover { background: var(--bg); border-color: var(--border); }
        .search-item .host-name { font-weight: 700; color: var(--text-dark); font-size: 0.95rem; }
        .search-item .host-dept { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .w-small { width: 140px; } 
        .w-med { width: 210px; } 
        .w-large { width: 260px; } 
        .w-wide { width: 350px; }
        
        .push-right { margin-left: auto; }
        .map-grid-container { background: var(--card-bg); padding: 2rem; border-radius: 24px; border: 1px solid var(--border); box-shadow: var(--shadow-md); flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .map-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1.5rem; width: 100%; }
        .seat-box { padding: 1.2rem; border-radius: 14px; text-align: center; border: 1px solid var(--border); background: var(--bg); transition: all 0.25s; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 110px; aspect-ratio: 1 / 1; box-sizing: border-box; color: var(--text-dark); }
        
        .Occupied { background: rgba(16, 185, 129, 0.08); color: #34d399; border: 1px solid #10b981; border-bottom: 5px solid #10b981; }
        .Vacant { background: transparent; border: 1px dashed var(--border); color: var(--text-muted); }
        .Repair { background: rgba(239, 68, 68, 0.08); color: #f87171; border: 1px solid #ef4444; border-bottom: 5px solid #ef4444; }
        
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index: 2000; }
        .modal-content { background: var(--card-bg); color: var(--text-dark); width: 420px; padding: 2.5rem; border-radius: 28px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 1px solid var(--border); }
        .modal-content label { color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .modal-content input, .modal-content textarea { background: var(--bg); color: var(--text-dark); border: 1px solid var(--border); width: 100%; padding: 12px; border-radius: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">

    <?php if(!$selected_dept): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1>Floor Overview</h1>
            <div class="overview-search-container">
                <i class="fa-solid fa-magnifying-glass overview-search-icon"></i>
                <input type="text" id="overviewSearch" class="overview-search-input" placeholder="Search for Hostname..." onkeyup="globalSearch()" autocomplete="off">
            </div>
        </div>

        <div id="searchPanel">
            <div class="panel-header">
                <span><i class="fa-solid fa-list-ul"></i> SEARCH RESULTS</span>
                <i class="fa-solid fa-times" onclick="clearSearch()" style="cursor:pointer; color: var(--text-muted)"></i>
            </div>
            <div id="searchResults"></div>
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
                        <?php renderRoom('Training Room', $dept_data, 'w-small'); ?>
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
                       class="overview-search-input" style="width: 320px;">
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
            <input type="text" id="seatCubicle" readonly>
            <label>Device Hostname</label>
            <input type="text" name="hostname" id="seatHost" placeholder="Enter name">
            <label>Campaign / User Notes</label>
            <textarea name="campaign" id="seatCamp" rows="4"></textarea>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 1rem;">
                <button type="submit" name="update_seat" style="width:100%; padding: 1rem; background: var(--primary); color: #fff; border:none; border-radius:14px; font-weight:800; cursor:pointer;">SAVE UPDATES</button>
                <button type="button" onclick="closeModal()" style="width:100%; padding: 0.8rem; background:none; border:none; color: var(--text-muted); font-weight:600; cursor:pointer;">DISCARD</button>
            </div>
        </form>
    </div>
</div>

<script>
    function globalSearch() {
        let input = document.getElementById('overviewSearch').value.toLowerCase();
        let rooms = document.getElementsByClassName('map-room');
        let panel = document.getElementById('searchPanel');
        let resultsDiv = document.getElementById('searchResults');
        
        resultsDiv.innerHTML = ""; 

        if (input === "") {
            panel.style.display = "none";
            for (let i = 0; i < rooms.length; i++) {
                rooms[i].style.opacity = "1";
                rooms[i].style.filter = "none";
            }
            return;
        }

        panel.style.display = "flex";
        let foundAny = false;

        for (let i = 0; i < rooms.length; i++) {
            let hostsString = rooms[i].getAttribute('data-hosts') || "";
            let deptName = rooms[i].getAttribute('data-deptname');
            let hostsArray = hostsString.split(',').filter(h => h.trim() !== "");
            let matches = hostsArray.filter(h => h.includes(input));

            if (matches.length > 0) {
                foundAny = true;
                rooms[i].style.opacity = "1";
                rooms[i].style.filter = "drop-shadow(0 0 10px var(--primary))";
                
                matches.forEach(m => {
                    let item = document.createElement('div');
                    item.className = 'search-item';
                    item.innerHTML = `
                        <div class="host-info">
                            <div class="host-name">${m.toUpperCase()}</div>
                            <div class="host-dept">${deptName}</div>
                        </div>
                        <i class="fa-solid fa-chevron-right go-icon" style="color:var(--primary)"></i>
                    `;
                    item.onclick = () => { window.location.href = rooms[i].href; };
                    resultsDiv.appendChild(item);
                });
            } else {
                rooms[i].style.opacity = "0.2";
                rooms[i].style.filter = "grayscale(1)";
            }
        }

        if (!foundAny) {
            resultsDiv.innerHTML = "<div style='padding:2rem; text-align:center; color:var(--text-muted); font-size:0.85rem;'>No matches found</div>";
        }
    }

    function clearSearch() {
        document.getElementById('overviewSearch').value = "";
        globalSearch();
    }

    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            seats[i].style.opacity = host.includes(input) ? "1" : "0.1";
            seats[i].style.pointerEvents = host.includes(input) ? "auto" : "none";
        }
    }

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
</script>
</body>
</html>

<?php include 'footer.php'; ?>