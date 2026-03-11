<?php
require_once 'db.php';

// Array of all your states/areas with their specific prefixes and seat counts
$areas = [
    ['name' => 'San Antonio',  'prefix' => 'SAN', 'count' => 49],
    ['name' => 'Phoenix',      'prefix' => 'PHO', 'count' => 35],
    ['name' => 'Denver',       'prefix' => 'DEN', 'count' => 42],
    ['name' => 'Chicago',      'prefix' => 'CHI', 'count' => 35],
    ['name' => 'Orlando',      'prefix' => 'ORL', 'count' => 20],
    ['name' => 'Miami',        'prefix' => 'MIA', 'count' => 32],
    ['name' => 'Dallas',       'prefix' => 'DAL', 'count' => 35],
    ['name' => 'Golden State', 'prefix' => 'GSW', 'count' => 40], 
    ['name' => 'TRN',          'prefix' => 'TRN', 'count' => 30], 
    ['name' => 'Sacramento',   'prefix' => 'SAC', 'count' => 40], 
    ['name' => 'Gray Room',    'prefix' => 'GRY', 'count' => 18],
    ['name' => 'Atlanta',      'prefix' => 'ATL', 'count' => 99],
    ['name' => 'Boston',       'prefix' => 'BOS', 'count' => 53],
    ['name' => 'Toronto',      'prefix' => 'TOR', 'count' => 55],
    ['name' => 'Indiana',      'prefix' => 'IND', 'count' => 37],
    ['name' => 'Los Angeles',  'prefix' => 'LAL', 'count' => 63]
];

echo "<h2>Starting Floor Map Seeding...</h2>";

foreach ($areas as $area) {
    $name = $area['name'];
    $prefix = $area['prefix'];
    $total = $area['count'];

    // We prepare the statement once per area to make it faster
    $stmt = $conn->prepare("INSERT IGNORE INTO production_floor_map (cubicle_no, department, status, hostname, campaign) VALUES (?, ?, 'Vacant', '', 'Not Set')");

    for ($i = 1; $i <= $total; $i++) {
        $cubicle = $prefix . "-" . str_pad($i, 4, '0', STR_PAD_LEFT);
        $stmt->bind_param("ss", $cubicle, $name);
        $stmt->execute();
    }

    echo "Successfully created $total seats for $name ($prefix)!<br>";
}

echo "<h3>All states have been seeded!</h3>";
?>