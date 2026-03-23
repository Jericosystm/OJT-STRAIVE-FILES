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

    // --- 1. EDIT MOVEMENT & SYNC INVENTORY ---
    if ($action === 'update_movement_sync') {
        $move_id     = $data['movement_id'] ?? null;
        $hostname    = trim($data['hostname'] ?? '');
        $asset_name  = trim($data['asset_name'] ?? '');
        $serial      = trim($data['serial_number'] ?? '');
        $type        = $data['device_type'] ?? 'Laptop';
        $location    = $data['location'] ?? 'Release';
        $remarks     = trim($data['remarks'] ?? ''); 
        $status      = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';
        
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');

        if (!$move_id) throw new Exception("Missing Movement ID.");

        $conn->begin_transaction();

        $upd_log = $conn->prepare("UPDATE machine_movement SET asset_name=?, serial_number=?, device_type=?, location=?, agent_name=?, agent_email=?, immediate_supmail=?, remarks=? WHERE id=?");
        $upd_log->bind_param("ssssssssi", $asset_name, $serial, $type, $location, $agent_name, $agent_email, $sup_email, $remarks, $move_id);
        $upd_log->execute();

        $upd_inv = $conn->prepare("UPDATE inventory_items SET asset_name=?, serial_num=?, device_type=?, location=?, status=?, agent_name=?, agent_email=?, immediate_supmail=?, remarks=? WHERE hostname=?");
        $upd_inv->bind_param("ssssssssss", $asset_name, $serial, $type, $location, $status, $agent_name, $agent_email, $sup_email, $remarks, $hostname);
        $upd_inv->execute();

        $conn->commit();
        $data['remarks'] = $remarks;
        notifyMovement($data, $current_user);
        $response = ["success" => true];

    // --- 2. MANUAL ADD & AUTO-CREATE ---
    } elseif ($action === 'manual_sync_update') {
        $hostname    = trim($data['hostname'] ?? '');
        $asset_name  = trim($data['asset_name'] ?? ''); // <--- NEW: Capture from form
        $serial      = trim($data['serial_number'] ?? ''); // <--- NEW: Capture from form
        $device_type = $data['device_type'] ?? 'Laptop';
        $location    = $data['location'] ?? 'Release';
        $remarks     = trim($data['remarks'] ?? '');
        $status      = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';
        
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');

        if (empty($hostname)) throw new Exception("Hostname is required.");

        $conn->begin_transaction();

        // Check if asset exists
        $check = $conn->prepare("SELECT id FROM inventory_items WHERE hostname = ?");
        $check->bind_param("s", $hostname);
        $check->execute();
        $asset_res = $check->get_result();
        $asset_data = $asset_res->fetch_assoc();

        if ($asset_data) {
            $asset_id = $asset_data['id'];
            // Update existing with the new form values
            $upd = $conn->prepare("UPDATE inventory_items SET asset_name = ?, serial_num = ?, device_type = ?, location = ?, status = ?, remarks = ?, agent_name = ?, agent_email = ?, immediate_supmail = ? WHERE id = ?");
            $upd->bind_param("sssssssssi", $asset_name, $serial, $device_type, $location, $status, $remarks, $agent_name, $agent_email, $sup_email, $asset_id);
            $upd->execute();
        } else {
            // Create new inventory item
            $ins = $conn->prepare("INSERT INTO inventory_items (hostname, asset_name, device_type, serial_num, location, status, remarks, agent_name, agent_email, immediate_supmail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("ssssssssss", $hostname, $asset_name, $device_type, $serial, $location, $status, $remarks, $agent_name, $agent_email, $sup_email);
            $ins->execute();
            $asset_id = $conn->insert_id;
        }

        $now = date('Y-m-d H:i:s');
        $rel_date = ($location === 'Release') ? $now : null;
        $ret_date = ($location === 'Return') ? $now : null;

        $log = $conn->prepare("INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, immediate_supmail, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $log->bind_param("issssssssssss", $asset_id, $asset_name, $hostname, $serial, $device_type, $location, $rel_date, $ret_date, $current_user, $agent_name, $agent_email, $sup_email, $remarks);
        $log->execute();

        $conn->commit();
        $data['remarks'] = $remarks;
        notifyMovement($data, $current_user);
        $response = ["success" => true];
        
    } else {
        throw new Exception("Invalid action: " . $action);
    }

} catch (Exception $e) {
    if (isset($conn)) { $conn->rollback(); }
    $response = ["success" => false, "message" => $e->getMessage()];
}

// --- ENHANCED UI EMAIL FUNCTION ---
function notifyMovement($data, $operator) {
    $mail = new PHPMailer(true);
    try {
        // --- SMTP CONFIG ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ojtstraive@gmail.com'; 
        $mail->Password   = 'qxdc udgy umcx svgl'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

        // --- RECIPIENTS ---
        $mail->setFrom('ojtstraive@gmail.com', 'OJTBox Logistics');
        if (!empty($data['agent_email'])) $mail->addAddress($data['agent_email']);
        if (!empty($data['immediate_supmail'])) $mail->addCC($data['immediate_supmail']);
        //$mail->addCC('ts@yourcompany.com', 'Technical Support Laguna');

        // --- THEME VARIABLES (NEON ORANGE & BLACK) ---
        $isReturn    = (strtolower($data['location'] ?? '') == 'return');
        $statusLabel = $isReturn ? 'SYSTEM_RETURN' : 'SYSTEM_RELEASE';
        
        $neonOrange  = '#ff6600'; // Vibrant Neon Orange
        $pureWhite   = '#ffffff';
        $pureBlack   = '#000000';
        $surfaceGray = '#080808'; // Premium "Elevated" Black
        
        $assetName = strtoupper(htmlspecialchars($data['asset_name'] ?? 'N/A'));
        $hostName  = strtoupper(htmlspecialchars($data['hostname'] ?? 'N/A'));
        $serialNum = strtoupper(htmlspecialchars($data['serial_number'] ?? 'N/A'));
        $devType   = strtoupper(htmlspecialchars($data['device_type'] ?? 'LAPTOP'));
        $remarks   = !empty($data['remarks']) ? nl2br(htmlspecialchars($data['remarks'])) : 'NO LOGS PROVIDED.';

        $mail->isHTML(true);
        $mail->Subject = "LOGISTICS_UPDATE // " . $hostName . " // " . $statusLabel;
        
        $mail->Body = "
        <div style='background-color: $pureBlack; padding: 50px 20px; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: $pureWhite;'>
            
            <div style='max-width: 600px; margin: 0 auto; background-color: $surfaceGray; border: 1px solid #1a1a1a; border-top: 4px solid $neonOrange; border-radius: 4px; box-shadow: 0 20px 40px rgba(0,0,0,0.8);'>
                
                <div style='padding: 40px 40px 20px 40px;'>
                    <div style='color: $neonOrange; font-size: 10px; font-weight: 800; letter-spacing: 5px; text-transform: uppercase; margin-bottom: 10px;'>OJTBox Logistics Sync</div>
                    <h1 style='margin: 0; font-size: 32px; font-weight: 200; color: $pureWhite; letter-spacing: -1px;'>Machine Movement</h1>
                </div>

                <div style='padding: 0 40px 20px 40px;'>
                    <span style='background-color: $neonOrange; color: $pureBlack; padding: 4px 12px; font-size: 11px; font-weight: 900; border-radius: 2px; letter-spacing: 1px;'>$statusLabel</span>
                </div>

                <hr style='border: 0; border-top: 1px solid #1a1a1a; margin: 0;'>

                <div style='padding: 40px;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding-bottom: 30px;'>
                                <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Asset Name</div>
                                <div style='font-size: 20px; font-weight: 600; color: $pureWhite;'>$assetName</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table style='width: 100%;'>
                                    <tr>
                                        <td style='width: 50%; padding-bottom: 30px;'>
                                            <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Hostname</div>
                                            <div style='font-size: 15px; font-weight: 700; color: $neonOrange; font-family: monospace;'>$hostName</div>
                                        </td>
                                        <td style='width: 50%; padding-bottom: 30px;'>
                                            <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Serial Number</div>
                                            <div style='font-size: 15px; font-weight: 700; color: $pureWhite;'>$serialNum</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding-bottom: 30px;'>
                                <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Device Type</div>
                                <div style='font-size: 15px; color: $pureWhite;'>$devType</div>
                            </td>
                        </tr>
                    </table>

                    <div style='background-color: $pureBlack; padding: 25px; border-radius: 4px; border: 1px solid #1a1a1a; border-left: 2px solid $neonOrange;'>
                        <div style='color: #444; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;'>Log_Remarks</div>
                        <div style='font-size: 14px; line-height: 1.6; color: #ddd; font-family: \"Courier New\", Courier, monospace;'>$remarks</div>
                    </div>
                </div>

                <div style='padding: 0 40px 40px 40px;'>
                    <div style='font-size: 10px; color: #333; line-height: 2; text-transform: uppercase; letter-spacing: 1px;'>
                        <span style='color: #555;'>Operator_ID:</span> $operator<br>
                        <span style='color: #555;'>Timestamp:</span> " . date('Y-m-d H:i:s') . "<br>
                        <span style='color: #555;'>Network:</span> OJTBOX_PRD_STR
                    </div>
                </div>
            </div>

            <div style='text-align: center; margin-top: 30px; font-size: 10px; color: #222; letter-spacing: 4px; text-transform: uppercase;'>
                &copy; Straive Logistics // Confidential Data
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