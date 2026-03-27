<?php
require_once 'includes/db.php';
session_start();

// Simple Passcode Logic (Change 'CPE2026' to your own secret)
$secret_key = "CPE2026";

if (isset($_POST['login'])) {
    if ($_POST['passcode'] === $secret_key) {
        $_SESSION['admin_auth'] = true;
    }
}

$authenticated = $_SESSION['admin_auth'] ?? false;

if (isset($_POST['add_project']) && $authenticated) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $desc = $_POST['description'];
    $stack = $_POST['tech_stack'];
    
    // Image Handling
    $img_name = $_FILES['project_image']['name'];
    $tmp_name = $_FILES['project_image']['tmp_name'];
    $target_dir = "assets/projects/";
    
    if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    if (!empty($img_name)) {
        move_uploaded_file($tmp_name, $target_dir . $img_name);
    }

    // FIXED: Added 'status' column and 'ACTIVE' value to match the portfolio filter
    $sql = "INSERT INTO projects (title, category, description, tech_stack, image_url, status) VALUES (?, ?, ?, ?, ?, 'ACTIVE')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $category, $desc, $stack, $img_name]);
    $success = "NODE_INITIALIZED_WITH_VISUALS // SYSTEM_ACTIVE";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TERMINAL // ADMIN_ACCESS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #050505; font-family: 'Courier New', Courier, monospace; }
        .glitch-border { border: 1px solid #333; transition: all 0.3s; }
        .glitch-border:focus { border-color: #ff6600; box-shadow: 0 0 15px rgba(255, 102, 0, 0.2); outline: none; }
    </style>
</head>
<body class="text-green-500 p-10">

    <?php if (!$authenticated): ?>
        <div class="max-w-md mx-auto mt-20 p-8 border border-red-900 bg-red-900/5">
            <h1 class="text-red-500 mb-4">RESTRICTED_AREA // ACCESS_DENIED</h1>
            <form method="POST">
                <input type="password" name="passcode" placeholder="ENTER_PASSCODE" class="w-full bg-black p-3 glitch-border mb-4 text-white">
                <button name="login" class="w-full bg-red-900 text-white p-2 hover:bg-red-700 transition">AUTHORIZE</button>
            </form>
        </div>
    <?php else: ?>
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-10 border-b border-green-900 pb-4">
                <h1>COMMAND_CENTER // PROJECT_MANAGER</h1>
                <a href="index.php" class="text-xs bg-green-900 text-black px-2 py-1">EXIT_TERMINAL</a>
            </div>

            <?php if (isset($success)) echo "<p class='mb-6 bg-green-900/20 p-3 border border-green-500'>$success</p>"; ?>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs mb-2 text-gray-500">PROJECT_TITLE</label>
                    <input type="text" name="title" required class="w-full bg-black p-3 glitch-border text-white">
                </div>
                <div>
                    <label class="block text-xs mb-2 text-gray-500">CATEGORY (e.g. Embedded, Web, AI)</label>
                    <input type="text" name="category" required class="w-full bg-black p-3 glitch-border text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs mb-2 text-gray-500">TECH_STACK (Comma separated)</label>
                    <input type="text" name="tech_stack" required class="w-full bg-black p-3 glitch-border text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs mb-2 text-gray-500">SYSTEM_DESCRIPTION</label>
                    <textarea name="description" rows="4" required class="w-full bg-black p-3 glitch-border text-white"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs mb-2 text-gray-500">VISUAL_ASSET (VIDEO/IMAGE)</label>
                    <input type="file" name="project_image" class="w-full bg-black p-3 glitch-border text-white text-xs">
                </div>
                <button name="add_project" class="md:col-span-2 bg-orange-600 text-white p-4 font-bold hover:bg-orange-500 transition">DEPLOY_TO_DATABASE</button>
            </form>
        </div>
    <?php endif; ?>

</body>
</html>