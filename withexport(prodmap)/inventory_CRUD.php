<?php
date_default_timezone_set('Asia/Manila');

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // --- YOUR ORIGINAL DATA EXTRACTION ---
    $action         = $data['action'] ?? '';
    $id             = $data['id'] ?? null;
    $hostname       = !empty(trim($data['hostname'] ?? '')) ? trim($data['hostname']) : null;
    $status         = $data['status'] ?? 'Active';
    $current_user   = $_SESSION['username'] ?? 'Admin';
    
    // --- NEW: Fetch the sender's email from the users table ---
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
    
    $is_inactive = in_array($status, ['Vacant', 'Dispose']);
    
    if ($is_inactive) {
        $location = 'N/A';
        $cubicle_input = null;
        $inventory_dept = null;
    } else {
        $location = $data['location'] ?? 'WFH';
        $cubicle_input = str_replace(' ', '', trim($data['cubicle_number'] ?? ''));
        $inventory_dept = trim($data['department'] ?? '');
        
        if ((empty($inventory_dept) || $inventory_dept == "Null") && strpos($cubicle_input, 'GR-') !== false) {
            $inventory_dept = 'Gray Room';
        }
    }
    
    $switch_port    = trim($data['switch_port'] ?? 'Not Set'); 
    $asset_name     = $data['asset_name'] ?? '';
    $serial_num     = $data['serial_num'] ?? '';
    $device_type    = $data['device_type'] ?? '';
    $remarks        = $data['remarks'] ?? '';

    if (in_array($location, ['Release', 'Return', 'WFH'])) {
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');
    } else {
        $agent_name = $agent_email = $sup_email = '';
    }

    // --- YOUR ORIGINAL VALIDATION ---
    $current_hostname = "";
    if ($action === 'update' && $id) {
        $fetchCurrent = $conn->prepare("SELECT hostname FROM inventory_items WHERE id = ?");
        $fetchCurrent->bind_param("i", $id);
        $fetchCurrent->execute();
        $resCurrent = $fetchCurrent->get_result()->fetch_assoc();
        $current_hostname = $resCurrent['hostname'] ?? '';
    }

    $cubicle_id = null;
    if (!$is_inactive && strcasecmp($location, 'Onsite') == 0 && !empty($cubicle_input)) {
        $lookUp = $conn->prepare("SELECT id FROM production_floor_map WHERE REPLACE(cubicle_no, ' ', '') = ? LIMIT 1");
        $lookUp->bind_param("s", $cubicle_input);
        $lookUp->execute();
        $res = $lookUp->get_result()->fetch_assoc();
        $cubicle_id = $res['id'] ?? null;
    }

    $conn->begin_transaction();

    // --- 1. CREATE ACTION ---
    if ($action === 'create') {
        $sql = "INSERT INTO inventory_items (asset_name, hostname, department, cubicle_number, location, serial_num, device_type, status, remarks, agent_name, agent_email, immediate_supmail, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssss", $asset_name, $hostname, $inventory_dept, $cubicle_input, $location, $serial_num, $device_type, $status, $remarks, $agent_name, $agent_email, $sup_email);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $details = "Initialized new node: " . $asset_name . " (SN: " . $serial_num . ")";
            recordActivity($conn, $_SESSION['user_id'] ?? 0, "INSERT_ASSET", $details);

            if ($cubicle_id) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ?, department = ? WHERE id = ?");
                $sync->bind_param("sssi", $hostname, $switch_port, $inventory_dept, $cubicle_id);
                $sync->execute();
            }

            if (in_array($location, ['Release', 'Return'])) {
                syncMovement($conn, $new_id, $data, $current_user);
                notifyMovement($data, $current_user, $sender_email);
            }

            $response = ["success" => true];
        }
    }

    // --- 2. UPDATE ACTION ---
    elseif ($action === 'update' && $id) {
        $sql = "UPDATE inventory_items SET asset_name=?, hostname=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, agent_name=?, agent_email=?, immediate_supmail=?, updated_at=NOW() WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssi", $asset_name, $hostname, $serial_num, $device_type, $status, $remarks, $location, $inventory_dept, $cubicle_input, $agent_name, $agent_email, $sup_email, $id);
        
        if ($stmt->execute()) {
            $clearAny = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
            $clearAny->bind_param("s", $current_hostname);
            $clearAny->execute();

            $logDetails = "Updated Node: $asset_name | Status: $status";
            if ($current_hostname !== $hostname) {
                $logDetails .= " | Hostname changed from $current_hostname to $hostname";
            }
            recordActivity($conn, $_SESSION['user_id'] ?? null, "UPDATE_ASSET", $logDetails);

            if (!$is_inactive && $cubicle_id && strcasecmp($location, 'Onsite') == 0) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ?, department = ? WHERE id = ?");
                $sync->bind_param("sssi", $hostname, $switch_port, $inventory_dept, $cubicle_id);
                $sync->execute();
            }

            if (in_array($location, ['Release', 'Return'])) {
                syncMovement($conn, $id, $data, $current_user);
                notifyMovement($data, $current_user, $sender_email);
            }
            $response = ["success" => true];
        }

    // --- 3. DELETE ACTION ---
    } elseif ($action === 'delete' && $id) {
        $fetchName = $conn->prepare("SELECT asset_name FROM inventory_items WHERE id = ?");
        $fetchName->bind_param("i", $id);
        $fetchName->execute();
        $nameRes = $fetchName->get_result()->fetch_assoc();
        $deletedName = $nameRes['asset_name'] ?? "Unknown Asset";

        $cl = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = (SELECT hostname FROM inventory_items WHERE id = ?)");
        $cl->bind_param("i", $id); 
        $cl->execute();

        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $details = "Permanently deleted node: $deletedName (ID: $id)";
            recordActivity($conn, $_SESSION['user_id'] ?? null, "DELETE_ASSET", $details);
            $response = ["success" => true];
        }
    }

    $conn->commit();

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response = ["success" => false, "message" => $e->getMessage()];
}

// --- LOGISTICS SYNC HELPER ---
function syncMovement($conn, $asset_id, $data, $operator) {
    $now = date('Y-m-d H:i:s');
    $loc = $data['location'];
    $rel = ($loc === 'Release') ? $now : null;
    $ret = ($loc === 'Return') ? $now : null;
    $remarks = $data['remarks'] ?? '';

    $log = $conn->prepare("INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, immediate_supmail, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");    
    $log->bind_param("issssssssssss", $asset_id, $data['asset_name'], $data['hostname'], $data['serial_num'], $data['device_type'], $loc, $rel, $ret, $operator, $data['agent_name'], $data['agent_email'], $data['immediate_supmail'], $remarks);    
    $log->execute();
}

// --- EMAIL FUNCTION ---
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

        $mail->setFrom('ojtstraive@gmail.com', 'OJTBox Logistics');
        if (!empty($data['agent_email'])) $mail->addAddress($data['agent_email']);
        if (!empty($data['immediate_supmail'])) $mail->addCC($data['immediate_supmail']);

        $isReturn    = (strtolower($data['location'] ?? '') == 'return');
        $statusLabel = $isReturn ? 'SYSTEM_RETURN' : 'SYSTEM_RELEASE';
        
        $neonOrange  = '#ff6600'; 
        $pureWhite   = '#ffffff';
        $pureBlack   = '#000000';
        $surfaceGray = '#080808';
        
        $assetName = strtoupper(htmlspecialchars($data['asset_name'] ?? 'N/A'));
        $hostName  = strtoupper(htmlspecialchars($data['hostname'] ?? 'N/A'));
        $serialNum = strtoupper(htmlspecialchars($data['serial_num'] ?? 'N/A'));
        $remarks   = !empty($data['remarks']) ? nl2br(htmlspecialchars($data['remarks'])) : 'NO LOGS PROVIDED.';
        
        // PH Time Formatting
        $ph_time = date('F j, Y | h:i A');

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
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='width: 30%; font-size: 9px; color: " . ($isReturn ? '#444' : $neonOrange) . "; font-weight: 900;'>[ STOCK ]</td>
                            <td style='width: 40%; text-align: center; font-size: 9px; color: #444;'>-----------</td>
                            <td style='width: 30%; text-align: right; font-size: 9px; color: " . ($isReturn ? $neonOrange : '#444') . "; font-weight: 900;'>[ RETURNED ]</td>
                        </tr>
                        <tr>
                            <td colspan='3' style='padding-top: 5px;'>
                                <div style='height: 2px; background: #1a1a1a; position: relative;'>
                                    <div style='position: absolute; left: " . ($isReturn ? 'auto' : '0') . "; right: " . ($isReturn ? '0' : 'auto') . "; top: -3px; width: 8px; height: 8px; background: $neonOrange; border-radius: 50%; box-shadow: 0 0 10px $neonOrange;'></div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style='padding: 40px;'>
                    <div style='background-color: $pureBlack; padding: 25px; border-radius: 4px; border: 1px solid #1a1a1a; border-left: 2px solid $neonOrange;'>
                        <div style='color: #444; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;'>Log_Remarks</div>
                        <div style='font-size: 14px; line-height: 1.6; color: #ddd; font-family: \"Courier New\", Courier, monospace;'>$remarks</div>
                    </div>
                </div>

                <div style='padding: 0 40px 40px 40px;'>
                    <div style='font-size: 10px; color: #333; line-height: 2; text-transform: uppercase; letter-spacing: 1px;'>
                        <span style='color: #555;'>Operator:</span> $operator<br>
                        <span style='color: #555;'>Operator_Email:</span> <span style='color: $neonOrange;'>$sender_email</span><br>
                        <span style='color: #555;'>Timestamp (PH):</span> $ph_time<br>
                        <span style='color: #555;'>Network:</span> OJTBOX_PRD_STR
                    </div>
                </div>
            </div>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
    }
}

// --- ACTIVITY LOGGING HELPER ---
function recordActivity($conn, $userId, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $uId = $userId ? $userId : 0; 
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $uId, $action, $details, $ip);
    $stmt->execute();
}

ob_get_clean(); 
echo json_encode($response);
exit();