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
    $registered_user_email = '';
    if (isset($_SESSION['username'])) {
        $userQuery = $conn->prepare("SELECT email FROM users WHERE username = ? LIMIT 1");
        $userQuery->bind_param("s", $_SESSION['username']);
        $userQuery->execute();
        $userRes = $userQuery->get_result()->fetch_assoc();
        $registered_user_email = $userRes['email'] ?? '';
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) throw new Exception("No data received.");

    $action         = $data['action'] ?? '';
    $id             = $data['id'] ?? null;
    $hostname       = !empty(trim($data['hostname'] ?? '')) ? trim($data['hostname']) : null;
    $status         = $data['status'] ?? 'Active';
    $current_user   = $_SESSION['username'] ?? 'Admin';
    
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
        $user_email  = trim($data['user_email'] ?? ''); 
        $accessories = trim($data['accessories'] ?? ''); 
    } else {
        $agent_name = $agent_email = $sup_email = $user_email = $accessories = '';
    }

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

    if ($action === 'create') {
        if ($cubicle_id) {
            $checkOccupied = $conn->prepare("SELECT hostname FROM production_floor_map WHERE id = ? AND status = 'Occupied'");
            $checkOccupied->bind_param("i", $cubicle_id);
            $checkOccupied->execute();
            $occResult = $checkOccupied->get_result();
            if ($occResult->num_rows > 0) {
                $occData = $occResult->fetch_assoc();
                throw new Exception("Cubicle $cubicle_input is already occupied by " . $occData['hostname']);
            }
        }

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
                syncMovement($conn, $new_id, $data, $current_user, $registered_user_email);
                notifyMovement($data, $current_user, $registered_user_email);
            }
            $response = ["success" => true];
        }
    }

    elseif ($action === 'update' && $id) {
        if (!$is_inactive && $cubicle_id && strcasecmp($location, 'Onsite') == 0) {
            $checkOccupied = $conn->prepare("SELECT hostname FROM production_floor_map WHERE id = ? AND status = 'Occupied' AND hostname != ?");
            $checkOccupied->bind_param("is", $cubicle_id, $current_hostname);
            $checkOccupied->execute();
            $occResult = $checkOccupied->get_result();
            if ($occResult->num_rows > 0) {
                $occData = $occResult->fetch_assoc();
                throw new Exception("Cannot update: Cubicle $cubicle_input is already occupied by " . $occData['hostname']);
            }
        }
        $sql = "UPDATE inventory_items SET asset_name=?, hostname=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, agent_name=?, agent_email=?, immediate_supmail=?, accessories=?, updated_at=NOW() WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssi", $asset_name, $hostname, $serial_num, $device_type, $status, $remarks, $location, $inventory_dept, $cubicle_input, $agent_name, $agent_email, $sup_email, $accessories, $id);

        if ($stmt->execute()) {
            $clearAny = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
            $clearAny->bind_param("s", $current_hostname);
            $clearAny->execute();

            if (!$is_inactive && $cubicle_id && strcasecmp($location, 'Onsite') == 0) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ?, department = ? WHERE id = ?");
                $sync->bind_param("sssi", $hostname, $switch_port, $inventory_dept, $cubicle_id);
                $sync->execute();
            }

            if (in_array($location, ['Release', 'Return'])) {
                syncMovement($conn, $id, $data, $current_user, $registered_user_email);
                notifyMovement($data, $current_user, $registered_user_email);
            }
            $response = ["success" => true];
        }
    }

    elseif ($action === 'delete' && $id) {
        $cl = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = (SELECT hostname FROM inventory_items WHERE id = ?)");
        $cl->bind_param("i", $id); 
        $cl->execute();
        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $response = ["success" => true];
    }

    $conn->commit();

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response = ["success" => false, "message" => $e->getMessage()];
}

function syncMovement($conn, $asset_id, $data, $operator, $reg_email) {
    $now = date('Y-m-d H:i:s');
    $loc = $data['location'];
    $rel = ($loc === 'Release') ? $now : null;
    $ret = ($loc === 'Return') ? $now : null;
    $log = $conn->prepare("INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, user_email, immediate_supmail, remarks, accessories) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");    
    $log->bind_param("issssssssssssss", $asset_id, $data['asset_name'], $data['hostname'], $data['serial_num'], $data['device_type'], $loc, $rel, $ret, $operator, $data['agent_name'], $data['agent_email'], $reg_email, $data['immediate_supmail'], $data['remarks'], $data['accessories']);    
    $log->execute();
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
        
        if (!empty($data['agent_email'])) $mail->addAddress($data['agent_email']);
        if (!empty($data['immediate_supmail'])) $mail->addCC($data['immediate_supmail']);

        $isReturn    = (strtolower($data['location'] ?? '') == 'return');
        $statusLabel = $isReturn ? 'SYSTEM_RETURN' : 'SYSTEM_RELEASE';
        
        $neonOrange  = '#ff6600';
        $pureWhite   = '#ffffff';
        $pureBlack   = '#000000';
        $surfaceGray = '#080808';
        
        // --- FIXED: Define these variables so they appear in the email ---
        $assetName   = strtoupper(htmlspecialchars($data['asset_name'] ?? 'N/A'));
        $hostName    = strtoupper(htmlspecialchars($data['hostname'] ?? 'N/A'));
        $deviceType  = strtoupper(htmlspecialchars($data['device_type'] ?? 'N/A'));
        $serialNum   = strtoupper(htmlspecialchars($data['serial_num'] ?? 'N/A'));
        $accessories = strtoupper(htmlspecialchars($data['accessories'] ?? 'NONE'));
        $remarks     = !empty($data['remarks']) ? nl2br(htmlspecialchars($data['remarks'])) : 'NO LOGS PROVIDED.';
        $ph_time     = date('F j, Y | h:i A');

        $mail->isHTML(true);
        $mail->Subject = "LOGISTICS_UPDATE // " . $hostName . " // " . $statusLabel;
        
        // --- RESTORED: Added back the styling wrapper so it looks "premium" again ---
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
                        <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Hostname</div>
                        <div style='font-size: 22px; font-weight: 700; color: $neonOrange; word-break: break-all;'>$hostName</div>
                    </div>

                    <div style='margin-bottom: 25px;'>
                        <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Asset Name</div>
                        <div style='font-size: 16px; font-weight: 600; color: $pureWhite;'>$assetName</div>
                    </div>

                    <div style='margin-bottom: 25px;'>
                        <div style='color: #555; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;'>Device Classification</div>
                        <div style='font-size: 14px; color: #bbb;'>$deviceType</div>
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
                        <span style='color: #555;'>EUC_Email:</span> <span style='color: $neonOrange;'>$sender_email</span><br>
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