<?php
date_default_timezone_set('Asia/Manila');
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
$response = ["success" => false, "message" => "Unknown error"];

try {
    require_once 'db.php';

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) throw new Exception("No data received.");

    $action = $data['action'] ?? '';
    $current_user = $_SESSION['username'] ?? 'Admin';

    // Generate Unique Tracking ID
    $datePrefix = date('Ymd');
    $randomSuffix = strtoupper(substr(md5(uniqid()), 0, 4));
    $tracking_id = "STRV-" . $datePrefix . "-" . $randomSuffix;
    $data['tracking_id'] = $tracking_id;

    // Fetch sender's email
    $sender_email = 'system@yourcompany.com'; 
    if (isset($_SESSION['username'])) {
        $user_stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
        $user_stmt->bind_param("s", $_SESSION['username']);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        if ($user_row = $user_res->fetch_assoc()) {
            $sender_email = $user_row['email'];
        }
    }

    // --- 1. EDIT MOVEMENT & SYNC INVENTORY ---
    if ($action === 'update_movement_sync') {
        $move_id     = $data['movement_id'] ?? null;
        $hostname    = trim($data['hostname'] ?? '');
        $asset_name  = trim($data['asset_name'] ?? '');
        $serial      = trim($data['serial_number'] ?? '');
        $type        = $data['device_type'] ?? 'Laptop';
        $location    = $data['location'] ?? 'Release';
        $accessories = trim($data['accessories'] ?? '');
        $remarks     = trim($data['remarks'] ?? ''); 
        $status      = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';
        
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');

        if (!$move_id) throw new Exception("Missing Movement ID.");

        $conn->begin_transaction();

        $upd_log = $conn->prepare("UPDATE machine_movement SET tracking_id=?, asset_name=?, serial_number=?, device_type=?, location=?, agent_name=?, agent_email=?, immediate_supmail=?, remarks=?, accessories=? WHERE id=?");
        $upd_log->bind_param("ssssssssssi", $tracking_id, $asset_name, $serial, $type, $location, $agent_name, $agent_email, $sup_email, $remarks, $accessories, $move_id);
        $upd_log->execute();

        $upd_inv = $conn->prepare("UPDATE inventory_items SET asset_name=?, serial_num=?, device_type=?, location=?, status=?, agent_name=?, agent_email=?, immediate_supmail=?, remarks=?, accessories=? WHERE hostname=?");
        $upd_inv->bind_param("sssssssssss", $asset_name, $serial, $type, $location, $status, $agent_name, $agent_email, $sup_email, $remarks, $accessories, $hostname);
        $upd_inv->execute();

        $conn->commit();
        $data['remarks'] = $remarks;
        $data['accessories'] = $accessories;
        notifyMovement($data, $current_user, $sender_email);
        $response = ["success" => true, "tracking_id" => $tracking_id];

    // --- 2. MANUAL ADD & AUTO-CREATE ---
    } elseif ($action === 'manual_sync_update') {
        $hostname    = trim($data['hostname'] ?? '');
        $asset_name  = trim($data['asset_name'] ?? ''); 
        $serial      = trim($data['serial_number'] ?? ''); 
        $device_type = $data['device_type'] ?? 'Laptop';
        $location    = $data['location'] ?? 'Release';
        $accessories = trim($data['accessories'] ?? '');
        $remarks     = trim($data['remarks'] ?? '');
        $status      = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';
        
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');

        if (empty($hostname)) throw new Exception("Hostname is required.");

        $conn->begin_transaction();

        $check = $conn->prepare("SELECT id FROM inventory_items WHERE hostname = ?");
        $check->bind_param("s", $hostname);
        $check->execute();
        $asset_res = $check->get_result();
        $asset_data = $asset_res->fetch_assoc();

        if ($asset_data) {
            $asset_id = $asset_data['id'];
            $upd = $conn->prepare("UPDATE inventory_items SET asset_name = ?, serial_num = ?, device_type = ?, location = ?, status = ?, remarks = ?, agent_name = ?, agent_email = ?, immediate_supmail = ?, accessories = ? WHERE id = ?");
            $upd->bind_param("ssssssssssi", $asset_name, $serial, $device_type, $location, $status, $remarks, $agent_name, $agent_email, $sup_email, $accessories, $asset_id);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO inventory_items (hostname, asset_name, device_type, serial_num, location, status, remarks, agent_name, agent_email, immediate_supmail, accessories) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("sssssssssss", $hostname, $asset_name, $device_type, $serial, $location, $status, $remarks, $agent_name, $agent_email, $sup_email, $accessories);
            $ins->execute();
            $asset_id = $conn->insert_id;
        }

        $now = date('Y-m-d H:i:s');
        $rel_date = ($location === 'Release') ? $now : null;
        $ret_date = ($location === 'Return') ? $now : null;

        $log = $conn->prepare("INSERT INTO machine_movement (tracking_id, asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, immediate_supmail, remarks, accessories) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $log->bind_param("sisssssssssssss", $tracking_id, $asset_id, $asset_name, $hostname, $serial, $device_type, $location, $rel_date, $ret_date, $current_user, $agent_name, $agent_email, $sup_email, $remarks, $accessories);
        $log->execute();

        $conn->commit();
        $data['remarks'] = $remarks;
        $data['accessories'] = $accessories;
        notifyMovement($data, $current_user, $sender_email);
        $response = ["success" => true, "tracking_id" => $tracking_id];
        
    } else {
        throw new Exception("Invalid action: " . $action);
    }

} catch (Exception $e) {
    if (isset($conn)) { $conn->rollback(); }
    $response = ["success" => false, "message" => $e->getMessage()];
}

function notifyMovement($data, $operator, $sender_email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ojtstraive@gmail.com'; 
        $mail->Password   = 'qxdc udgy umcx svgl'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

        $mail->setFrom('ojtstraive@gmail.com', 'EUC LGN MACHINE MOVEMENT');
        
        if (!empty($data['agent_email'])) $mail->addAddress($data['agent_email']);
        if (!empty($data['immediate_supmail'])) $mail->addCC($data['immediate_supmail']);

        $isReturn    = (strtolower($data['location'] ?? '') == 'return');
        $statusLabel = $isReturn ? 'SYSTEM_RETURN' : 'SYSTEM_RELEASE';
        
        $neonOrange  = '#ff6600';
        $pureWhite   = '#ffffff';
        $pureBlack   = '#000000';
        $surfaceGray = '#080808';
        
        // Data Extraction (Asset Name and Hostname kept for Subject/Tracking only)
        $hostName    = strtoupper(htmlspecialchars($data['hostname'] ?? 'N/A'));
        $trackingId  = $data['tracking_id'] ?? 'N/A';
        $accessories = strtoupper(htmlspecialchars($data['accessories'] ?? 'NONE'));
        $remarks     = !empty($data['remarks']) ? nl2br(htmlspecialchars($data['remarks'])) : 'NO LOGS PROVIDED.';
        $ph_time     = date('F j, Y | h:i A');

        $mail->isHTML(true);
        // Subject line still contains Hostname for admin identification
        $mail->Subject = "LOGISTICS_UPDATE // " . $hostName . " // " . $statusLabel;
        
        $mail->Body = "
        <div style='margin-bottom: 25px;'>
    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr>
            <td style='width: 50%; padding-right: 10px; vertical-align: top;'>
                <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Hostname</div>
                <div style='font-size: 14px; font-weight: 700; color: $pureWhite; word-break: break-all;'>$hostName</div>
            </td>
            <td style='width: 50%; vertical-align: top;'>
                <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Asset Name</div>
                <div style='font-size: 14px; font-weight: 700; color: $pureWhite; word-break: break-all;'>$assetName</div>
            </td>
        </tr>
    </table>
</div>

<div style='margin-bottom: 25px;'>
    <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Device Classification</div>
    <div style='font-size: 14px; font-weight: 700; color: $pureWhite;'>$deviceType</div>
</div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
    }
}

if (ob_get_length()) {
    ob_end_clean(); 
}

echo json_encode($response);
exit();