<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Production Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ALL YOUR CSS FROM THE ORIGINAL CODE GOES HERE */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        :root { --primary: #ff6b00; --bg: #f1f5f9; --card-bg: #ffffff; --text-dark: #1e293b; --border: #e2e8f0; --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        html, body { height: 100%; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); overflow: hidden; }
        .navbar { background: #ff9800; padding: 0.5rem 2rem; display: flex; align-items: center; height: 60px; }
        .container { max-width: 1600px; margin: 0 auto; padding: 1rem 2rem; height: calc(100vh - 60px); display: flex; flex-direction: column; }
        .map-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1.5rem; }
        .seat-box { padding: 1.2rem; border-radius: 14px; border: 1px solid var(--border); background: #f8fafc; text-align: center; cursor: pointer; aspect-ratio: 1/1; }
        .Occupied { background: #ecfdf5; border-bottom: 5px solid #10b981; }
        .Vacant { background: #ffffff; border: 1px dashed #cbd5e1; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 420px; padding: 2.5rem; border-radius: 28px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        /* Include the rest of your specific Room/Hallway styles here */
    </style>
</head>
<body>
<nav class="navbar">
    <a href="prod_map.php" style="color:#fff; text-decoration:none; font-weight:800;">OJTBox | Production Map</a>
</nav>
<div class="container">