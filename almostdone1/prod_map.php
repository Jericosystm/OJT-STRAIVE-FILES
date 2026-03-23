<?php
session_start();

// Existing session check...
$username = $_SESSION['username'] ?? 'User'; 
$user_role = $_SESSION['role'] ?? 'euc_user'; 

$back_link = ($user_role === 'euc_admin') ? 'index_admin.php' : 'index_user.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// --- LOGIC 3: FILTERING ---
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

function renderRoom($name, $data, $extraClass = "") {
    global $dept_companies, $overrides;
    
    $link = "prod_map.php?dept=" . urlencode($name);
    if (isset($overrides[$name])) { $link = $overrides[$name]; }

    $total = $data[$name]['total'] ?? 0;
    $occ = $data[$name]['occupied'] ?? 0;
    $hosts = $data[$name]['hostnames'] ?? ''; 
    $perc = ($total > 0) ? round(($occ / $total) * 100) : 0;
    
    $color = ($perc > 85) ? "#ef4444" : "#ff6600"; 

    $companies = $dept_companies[$name] ?? ['No Data'];
    $companyHtml = "";
    foreach($companies as $co) {
        $companyHtml .= "<li><i class='fa-solid fa-check-circle'></i> $co</li>";
    }

    echo "
    <a href='$link' class='map-room $extraClass' style='--room-color: $color;' data-hosts='$hosts' data-deptname='$name'>
        <div class='room-header'>
            <span class='room-name'>$name</span>
            <span class='room-perc'>$perc%</span>
        </div>
        <div class='occupancy-bar'>
            <div class='fill' style='width: $perc%; background: $color; box-shadow: 0 0 15px {$color}cc;'></div>
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
    <title>OJTBox | Production Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Default Dark Mode */
            --primary: #ff6600;
            --primary-glow: rgba(255, 102, 0, 0.4);
            --bg: #030303;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #FFFFFF;
            --text-muted: rgba(255, 255, 255, 0.5);
            --modal-bg: #080808;
            --panel-bg: rgba(10, 10, 10, 0.98);
        }

        /* Light Mode Overrides */
        [data-theme="light"] {
            --bg: #F5F5F7;
            --card-bg: #FFFFFF;
            --card-hover: #E8E8ED;
            --border: rgba(0, 0, 0, 0.1);
            --text-main: #1D1D1F;
            --text-muted: #6E6E73;
            --modal-bg: #FFFFFF;
            --panel-bg: rgba(255, 255, 255, 0.98);
        }

        /* --- Page Reveal Animations --- */
        @keyframes pageReveal {
            from { opacity: 0; transform: translateY(20px) scale(0.98); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        @keyframes staggerIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.05), transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(255, 102, 0, 0.03), transparent 40%);
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
            animation: pageReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .container { max-width: 1600px; margin: 0 auto; padding: 40px; }

        h1 { font-weight: 800; font-size: 2.8rem; margin: 0; letter-spacing: -2px; color: var(--text-main); }

        /* --- Map Layout Styles --- */
        .floor-plan { display: flex; flex-direction: column; gap: 1.5rem; padding-top: 2rem; }
        .map-row { display: flex; gap: 1.2rem; align-items: stretch; animation: staggerIn 0.8s ease forwards; }
        
        .hallway { 
            background: linear-gradient(90deg, transparent, rgba(255, 102, 0, 0.08), transparent);
            color: var(--primary); 
            text-align: center; 
            padding: 1.5rem; 
            font-weight: 800; 
            font-size: 1rem; 
            letter-spacing: 1.2rem; 
            border-radius: 30px; 
            border: 1px dashed rgba(255, 102, 0, 0.3);
            margin: 20px 0;
            text-shadow: 0 0 10px var(--primary-glow);
        }
        
        .map-room { 
            background: var(--card-bg); 
            border: 1px solid var(--border); 
            text-decoration: none; color: inherit; 
            padding: 1.8rem; border-radius: 28px; 
            display: flex; flex-direction: column; 
            transition: all 0.5s cubic-bezier(0.2, 1, 0.2, 1); 
            position: relative; overflow: hidden;
            backdrop-filter: blur(12px);
        }

        .map-room:hover { 
            background: var(--card-hover);
            border-color: var(--primary);
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0,0,0,0.2), 0 0 20px rgba(255, 102, 0, 0.1);
        }

        .room-hover-overlay {
            position: absolute; inset: 0; background: var(--panel-bg);
            backdrop-filter: blur(8px); display: flex; align-items: center;
            padding: 1.8rem; transform: translateX(-100%);
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); z-index: 5;
        }
        .map-room:hover .room-hover-overlay { transform: translateX(0); }
        
        .hover-content ul { list-style: none; padding: 0; margin: 0; }
        .hover-content ul li { font-size: 0.9rem; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; opacity: 0.8; color: var(--text-main); }
        .hover-title { color: var(--primary); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 2px; display: block; margin-bottom: 12px; }

        .room-header { display: flex; justify-content: space-between; align-items: center; }
        .room-name { font-weight: 700; font-size: 1.2rem; letter-spacing: -0.5px; color: var(--text-main); }
        .room-perc { font-family: 'JetBrains Mono'; color: var(--primary); font-size: 0.9rem; font-weight: bold; }
        
        .occupancy-bar { height: 8px; background: rgba(0,0,0,0.05); border-radius: 20px; margin: 1.2rem 0; overflow: hidden; }
        .fill { height: 100%; border-radius: 20px; transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .room-footer { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }

        /* --- Search & Grid UI --- */
        .overview-search-input { 
            padding: 1rem 1.5rem 1rem 3.5rem; width: 350px; border-radius: 18px; 
            border: 1px solid var(--border); background: var(--card-bg); color: var(--text-main);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); backdrop-filter: blur(10px);
        }
        .overview-search-input:focus { width: 450px; border-color: var(--primary); outline: none; box-shadow: 0 0 20px var(--primary-glow); }

        #searchPanel {
            position: absolute; top: 120px; right: 40px; width: 420px; max-height: 500px;
            background: var(--panel-bg); border-radius: 24px; box-shadow: 0 40px 100px rgba(0,0,0,0.3);
            border: 1px solid var(--border); display: none; flex-direction: column; z-index: 1000; overflow: hidden;
        }

        .map-grid-container { 
            background: var(--card-bg); padding: 3rem; border-radius: 40px; 
            border: 1px solid var(--border); backdrop-filter: blur(20px); margin-top: 2rem;
            animation: staggerIn 0.6s ease-out;
        }
        .map-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1.5rem; }
        
        .seat-box { 
            padding: 1.8rem; border-radius: 24px; text-align: center; 
            border: 1px solid var(--border); background: var(--card-bg); color: var(--text-main);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer;
        }
        .seat-box:hover { transform: scale(1.1); background: var(--card-hover); border-color: var(--primary); }
        .Occupied { border-color: #10b981; color: #10b981; }
        .Vacant { border-style: dashed; color: var(--text-muted); opacity: 0.6; }
        .Repair { border-color: #ef4444; color: #ef4444; animation: pulse 2s infinite; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(15px); z-index: 2000; }
        .modal-content { 
            background: var(--modal-bg); width: 450px; padding: 3rem; border-radius: 40px; 
            border: 1px solid var(--border); position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            box-shadow: 0 50px 100px rgba(0,0,0,0.3); color: var(--text-main);
        }

        .modal-content input, .modal-content textarea {
            width: 100%; background: var(--bg); border: 1px solid var(--border); 
            color: var(--text-main); padding: 12px; border-radius: 12px; margin: 10px 0 20px 0;
        }

        .btn-save {
            width: 100%; background: var(--primary); color: white; border: none; 
            padding: 15px; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s;
        }
        .btn-save:hover { background: #e65c00; transform: translateY(-2px); }

        /* --- Sizing Utilities --- */
        .w-small { width: 180px; } 
        .w-med { width: 240px; } 
        .w-large { width: 300px; } 
        .w-wide { width: 400px; }
        .push-right { margin-left: auto; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

<div class="container">

    <?php if(!$selected_dept): ?>
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem;">
            <div>
                <p style="color: var(--primary); font-weight: 800; font-size: 0.75rem; letter-spacing: 5px; text-transform: uppercase; margin-bottom: 10px;">Network Architecture</p>
                <h1>Live Production Map</h1>
            </div>
            <div class="overview-search-container" style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.1rem; z-index: 10;"></i>
                <input type="text" id="overviewSearch" class="overview-search-input" placeholder="Search workstation..." onkeyup="globalSearch()" autocomplete="off">
            </div>
        </div>

        <div id="searchPanel">
            <div class="panel-header" style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between;">
                <span style="font-weight: 800; color: var(--primary); font-size: 0.8rem;">SYSTEM QUERY RESULTS</span>
                <i class="fa-solid fa-times" onclick="clearSearch()" style="cursor:pointer; color: var(--text-muted)"></i>
            </div>
            <div id="searchResults" style="overflow-y: auto;"></div>
        </div>
        
        <div class="floor-plan">
            <div class="map-row" style="animation-delay: 0.1s;">
                <?php renderRoom('San Antonio', $dept_data, 'w-med'); ?>
                <?php renderRoom('Phoenix', $dept_data, 'w-med'); ?>
                <div style="width: 2rem"></div> 
                <?php renderRoom('Denver', $dept_data, 'w-med'); ?>
                <?php renderRoom('Dallas', $dept_data, 'w-med'); ?>
                <div style="width: 2rem"></div>
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

            <div class="map-row" style="align-items: flex-start; animation-delay: 0.3s;">
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
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <a href="prod_map.php" style="text-decoration: none; color: var(--primary); font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 12px; margin-bottom: 15px; transition: 0.3s;" onmouseover="this.style.gap='18px'" onmouseout="this.style.gap='12px'">
                    <i class="fa-solid fa-arrow-left-long"></i> BACK TO GLOBAL MAP
                </a>
                <h1 style="font-size: 3rem; text-transform: uppercase;"><?php echo htmlspecialchars($selected_dept); ?></h1>
            </div>
            
            <div style="position: relative;">
                <i class="fa-solid fa-filter" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--primary); z-index: 10;"></i>
                <input type="text" id="searchInput" placeholder="Filter hostnames..." onkeyup="searchMap()" 
                       class="overview-search-input" style="width: 350px;">
            </div>
        </div>

        <div class="map-grid-container">
            <div class="map-grid" id="mapGrid">
                <?php foreach($stations as $index => $row): ?>
                    <div class="seat-box <?php echo htmlspecialchars($row['status']); ?>" 
                         style="animation: staggerIn 0.5s ease forwards; animation-delay: <?php echo $index * 0.02; ?>s;"
                         data-hostname="<?php echo strtolower($row['hostname']); ?>" 
                         onclick="openEdit(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        <i class="fa-solid <?php echo ($row['status'] == 'Repair') ? 'fa-screwdriver-wrench' : 'fa-desktop'; ?>" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
                        <div style="font-weight: 800; font-size: 1.1rem;"><?php echo htmlspecialchars($row['cubicle_no']); ?></div>
                        <div style="font-family: 'JetBrains Mono'; font-size: 0.7rem; margin-top: 5px; opacity: 0.7;"><?php echo htmlspecialchars($row['hostname'] ?: '---'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="seatTitle" style="margin: 0 0 2rem 0; font-weight: 800; font-size: 1.8rem; color: var(--primary); letter-spacing: -1px;">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <input type="hidden" name="department" id="seatDept">
            
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px;">Hardware Identifier</label>
            <input type="text" id="seatCubicle" readonly style="opacity: 0.6; cursor: not-allowed;">
            
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px;">Assigned Hostname</label>
            <input type="text" name="hostname" id="seatHost" placeholder="PH-PC-XXXXX" style="font-family: 'JetBrains Mono';">
            
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px;">Deployment Notes</label>
            <textarea name="campaign" id="seatCamp" rows="4" placeholder="Campaign details or repair status..."></textarea>
            
            <button type="submit" name="update_seat" class="btn-save" style="margin-top: 10px;">APPLY CONFIGURATION</button>
            <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:var(--text-muted); margin-top:20px; cursor:pointer; font-weight:700; font-size: 0.8rem;">DISCARD CHANGES</button>
        </form>
    </div>
</div>

<script>
    // --- Advanced Global Search ---
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
                rooms[i].style.transform = "scale(1)";
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

            if (matches.length > 0 || deptName.toLowerCase().includes(input)) {
                foundAny = true;
                rooms[i].style.opacity = "1";
                rooms[i].style.filter = "none";
                rooms[i].style.transform = "scale(1.05)";
                rooms[i].style.borderColor = "var(--primary)";
                
                matches.forEach(m => {
                    let item = document.createElement('div');
                    item.className = 'search-item';
                    item.style.padding = '1.2rem';
                    item.style.borderBottom = '1px solid var(--border)';
                    item.style.cursor = 'pointer';
                    item.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:center">
                            <div>
                                <div style="font-weight:800; color:var(--text-main); font-size:0.9rem; font-family:'JetBrains Mono'">${m.toUpperCase()}</div>
                                <div style="font-size:0.7rem; color:var(--primary); font-weight:800; margin-top:2px;">${deptName}</div>
                            </div>
                            <i class="fa-solid fa-arrow-right" style="color:var(--primary)"></i>
                        </div>
                    `;
                    item.onclick = () => { window.location.href = rooms[i].href; };
                    resultsDiv.appendChild(item);
                });
            } else {
                rooms[i].style.opacity = "0.15";
                rooms[i].style.filter = "blur(4px) grayscale(1)";
                rooms[i].style.transform = "scale(0.95)";
            }
        }
        if (!foundAny) resultsDiv.innerHTML = "<div style='padding:3rem; text-align:center; color:var(--text-muted); font-weight:600;'>NO ACTIVE HOSTS FOUND</div>";
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
            if(host.includes(input)) {
                seats[i].style.display = "block";
                seats[i].style.opacity = "1";
                seats[i].style.transform = "scale(1)";
            } else {
                seats[i].style.opacity = "0";
                setTimeout(() => { if(seats[i].style.opacity === "0") seats[i].style.display = "none"; }, 400);
            }
        }
    }

    function openEdit(data) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = data.id;
        document.getElementById('seatTitle').innerText = "Configure " + data.cubicle_no;
        document.getElementById('seatDept').value = data.department;
        document.getElementById('seatCubicle').value = data.cubicle_no;
        document.getElementById('seatHost').value = data.hostname || '';
        document.getElementById('seatCamp').value = data.campaign || '';
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    
    document.addEventListener('keydown', (e) => { if(e.key === "Escape") closeModal(); });
</script>
</body>
</html>