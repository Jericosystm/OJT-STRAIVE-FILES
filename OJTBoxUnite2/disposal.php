<?php
// 1. Include your database connection
include 'db.php'; 

// 2. Fetch only items where status is 'Disposed'
$query = "SELECT * FROM assets WHERE status = 'Disposed' ORDER BY updated_at DESC";
$result = mysqli_query($conn, $query);

// 3. Initialize the variable as an empty array
// This prevents the "null given" warning if the database is empty
$disposed_items = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $disposed_items[] = $row;
    }
}
?>