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
        $accessories = trim($data['accessories'] ?? ''); // ADDED
        $remarks     = trim($data['remarks'] ?? ''); 
        $status      = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';
        
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');

        if (!$move_id) throw new Exception("Missing Movement ID.");

        $conn->begin_transaction();

        // Update Log (Added accessories)
        $upd_log = $conn->prepare("UPDATE machine_movement SET asset_name=?, serial_number=?, device_type=?, location=?, agent_name=?, agent_email=?, immediate_supmail=?, remarks=?, accessories=? WHERE id=?");
        $upd_log->bind_param("sssssssssi", $asset_name, $serial, $type, $location, $agent_name, $agent_email, $sup_email, $remarks, $accessories, $move_id);
        $upd_log->execute();

        // Update Inventory (Added accessories)
        $upd_inv = $conn->prepare("UPDATE inventory_items SET asset_name=?, serial_num=?, device_type=?, location=?, status=?, agent_name=?, agent_email=?, immediate_supmail=?, remarks=?, accessories=? WHERE hostname=?");
        $upd_inv->bind_param("sssssssssss", $asset_name, $serial, $type, $location, $status, $agent_name, $agent_email, $sup_email, $remarks, $accessories, $hostname);
        $upd_inv->execute();

        $conn->commit();
        $data['remarks'] = $remarks;
        $data['accessories'] = $accessories;
        notifyMovement($data, $current_user, $sender_email);
        $response = ["success" => true];

    // --- 2. MANUAL ADD & AUTO-CREATE ---
    } elseif ($action === 'manual_sync_update') {
        $hostname    = trim($data['hostname'] ?? '');
        $asset_name  = trim($data['asset_name'] ?? ''); 
        $serial      = trim($data['serial_number'] ?? ''); 
        $device_type = $data['device_type'] ?? 'Laptop';
        $location    = $data['location'] ?? 'Release';
        $accessories = trim($data['accessories'] ?? ''); // ADDED
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

        $log = $conn->prepare("INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, immediate_supmail, remarks, accessories) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $log->bind_param("isssssssssssss", $asset_id, $asset_name, $hostname, $serial, $device_type, $location, $rel_date, $ret_date, $current_user, $agent_name, $agent_email, $sup_email, $remarks, $accessories);
        $log->execute();

        $conn->commit();
        $data['remarks'] = $remarks;
        $data['accessories'] = $accessories;
        notifyMovement($data, $current_user, $sender_email);
        $response = ["success" => true];
        
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
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ojtstraive@gmail.com'; 
        $mail->Password   = 'qxdc udgy umcx svgl'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

        $mail->setFrom('ojtstraive@gmail.com', 'EUC LGN MACHINE MOVEMENT');
        
        // Recipients
        if (!empty($data['agent_email'])) $mail->addAddress($data['agent_email']);
        if (!empty($data['immediate_supmail'])) $mail->addCC($data['immediate_supmail']);

        $isReturn    = (strtolower($data['location'] ?? '') == 'return');
        $statusLabel = $isReturn ? 'SYSTEM_RETURN' : 'SYSTEM_RELEASE';
        
        // UI Colors
        $neonOrange  = '#ff6600';
        $pureWhite   = '#ffffff';
        $pureBlack   = '#000000';
        $surfaceGray = '#080808';
        
        // Data Formatting
        $assetName   = strtoupper(htmlspecialchars($data['asset_name'] ?? 'N/A'));
        $hostName    = strtoupper(htmlspecialchars($data['hostname'] ?? 'N/A'));
        $serialNum   = strtoupper(htmlspecialchars($data['serial_number'] ?? 'N/A'));
        $accessories = strtoupper(htmlspecialchars($data['accessories'] ?? 'NONE'));
        $remarks     = !empty($data['remarks']) ? nl2br(htmlspecialchars($data['remarks'])) : 'NO LOGS PROVIDED.';
        $ph_time     = date('F j, Y | h:i A');

        $mail->isHTML(true);
        $mail->Subject = "LOGISTICS_UPDATE // " . $hostName . " // " . $statusLabel;
        
        $mail->Body = "
        <div style='background-color: $pureBlack; padding: 50px 20px; font-family: \"Segoe UI\", Roboto, sans-serif; color: $pureWhite;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: $surfaceGray; border: 1px solid #1a1a1a; border-top: 4px solid $neonOrange; border-radius: 4px;'>
                
                <div style='padding: 40px 40px 20px 40px;'>
                    <div style='color: $neonOrange; font-size: 10px; font-weight: 800; letter-spacing: 5px; text-transform: uppercase; margin-bottom: 10px;'>OJTBox Straive Laguna</div>
                    <h1 style='margin: 0; font-size: 32px; font-weight: 200; color: $pureWhite;'>Machine Movement</h1>
                </div>

                <div style='padding: 0 40px 20px 40px;'>
                    <span style='background-color: $neonOrange; color: $pureBlack; padding: 4px 12px; font-size: 11px; font-weight: 900; border-radius: 2px;'>$statusLabel</span>
                </div>

                <hr style='border: 0; border-top: 1px solid #1a1a1a; margin: 0;'>

                <div style='padding: 40px;'>
                    <div style='margin-bottom: 25px;'>
                        <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Asset Details</div>
                        <div style='font-size: 18px; font-weight: 600; color: $pureWhite;'>$assetName ($hostName)</div>
                        <div style='font-size: 13px; color: $neonOrange; font-family: monospace;'>SN: $serialNum</div>
                    </div>

                    <div style='margin-bottom: 25px;'>
                        <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Accessories Issued</div>
                        <div style='font-size: 15px; font-weight: 700; color: $pureWhite;'>$accessories</div>
                    </div>

                    <div style='background-color: $pureBlack; padding: 25px; border-radius: 4px; border: 1px solid #1a1a1a; border-left: 2px solid $neonOrange;'>
                        <div style='color: #444; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px;'>Internal_Remarks</div>
                        <div style='font-size: 14px; line-height: 1.6; color: #ddd;'>$remarks</div>
                    </div>
                </div>

                <div style='padding: 0 40px 40px 40px;'>
                    <div style='font-size: 10px; color: #333; line-height: 2; text-transform: uppercase; letter-spacing: 1px;'>
                        <span style='color: #555;'>EUC Name:</span> $operator<br>
                        <span style='color: #555;'>Operator_Email:</span> <span style='color: $neonOrange;'>$sender_email</span><br>
                        <span style='color: #555;'>Timestamp:</span> $ph_time
                    </div>
                </div>
            </div>
            <div style='text-align: center; margin-top: 20px; font-size: 9px; color: #333; letter-spacing: 2px;'>
                &copy; STRAIVE LOGISTICS // AUTO-GENERATED SYNC
            </div>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
    }
}

ob_get_clean();
echo json_encode($response);
exit();