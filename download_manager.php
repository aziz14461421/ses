<?php
// Add CORS headers at the beginning
header('Access-Control-Allow-Origin: *');  // Replace '*' with your domain for security
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Database credentials
$host = 'localhost';
$dbname = 'ses';
$user = 'admin';
$pass = 'admin';

// aria2c RPC endpoint
$rpcUrl = 'http://localhost:6800/jsonrpc';

// Directory for downloaded files
$downloadDir = __DIR__ . '/downloaded/';

// Connect to the MySQL database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Function to make a JSON-RPC request to aria2c
function sendRpcRequest($method, $params = array()) {
    global $rpcUrl;

    // Prepare JSON payload
    $data = json_encode([
        'jsonrpc' => '2.0',
        'id' => '1',
        'method' => $method,
        'params' => $params
    ]);

    // Send request using cURL
    $ch = curl_init($rpcUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Function to update download status in the database
function updateDownloadStatus($pdo, $gid, $status, $completedSize, $percentage) {
    $stmt = $pdo->prepare("UPDATE files SET file_status = ?, completed_size = ?, percentage = ? WHERE gid = ?");
    $stmt->execute([$status, $completedSize, $percentage, $gid]);
}

// Function to update the transfer status
function updateTransferStatus($pdo, $uuid, $status, $percentage) {
    $stmt = $pdo->prepare("UPDATE transfers SET transfer_status = ?, percentage = ? WHERE uuid = ?");
    $stmt->execute([$status, $percentage, $uuid]);
}

// Function to calculate overall transfer progress based on file progress
function calculateTransferProgress($pdo, $uuid) {
    $stmt = $pdo->prepare("SELECT AVG(percentage) AS avg_percentage FROM files WHERE transfer_id = ?");
    $stmt->execute([$uuid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avg_percentage'];
}

// Fetch the request method and payload
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        // Check for action and uuid
        if (isset($input['action']) && isset($input['uuid'])) {
            $uuid = $input['uuid'];
            switch ($input['action']) {
                case 'start':
                    // Start downloads for all files associated with a specific transfer
                    $stmt = $pdo->prepare("SELECT file_id, download_url, gid FROM files WHERE transfer_id = ? AND (file_status IS NULL OR file_status != 'complete')");
                    $stmt->execute([$uuid]);
                    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $downloadsInitiated = 0;

                    foreach ($files as $file) {
                        $downloadUrl = $file['download_url'];
                        $fileId = $file['file_id'];

                        if (empty($file['gid'])) {
                            $response = sendRpcRequest('aria2.addUri', [[$downloadUrl], ['dir' => $downloadDir]]);
                            if (isset($response['result'])) {
                                $gid = $response['result'];
                                $stmt = $pdo->prepare("UPDATE files SET gid = ?, file_status = 'downloading' WHERE file_id = ?");
                                $stmt->execute([$gid, $fileId]);
                                $downloadsInitiated++;
                            }
                        }
                    }

                    if ($downloadsInitiated > 0) {
                        updateTransferStatus($pdo, $uuid, 'in_progress', 0);
                        echo json_encode(['message' => "Downloads started for transfer $uuid"]);
                    } else {
                        echo json_encode(['message' => "No downloads were initiated. All files may have already been started or completed."]);
                    }
                    break;

                case 'pause':
                    // Pause all downloads associated with the transfer
                    $stmt = $pdo->prepare("SELECT gid FROM files WHERE transfer_id = ?");
                    $stmt->execute([$uuid]);
                    $gids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($gids as $gid) {
                        sendRpcRequest('aria2.pause', [$gid]);
                    }
                    updateTransferStatus($pdo, $uuid, 'paused', 0);
                    echo json_encode(['message' => "Downloads paused for transfer $uuid"]);
                    break;

                case 'resume':
                    // Resume all downloads associated with the transfer
                    $stmt = $pdo->prepare("SELECT gid FROM files WHERE transfer_id = ?");
                    $stmt->execute([$uuid]);
                    $gids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($gids as $gid) {
                        sendRpcRequest('aria2.unpause', [$gid]);
                    }
                    updateTransferStatus($pdo, $uuid, 'in_progress', 0);
                    echo json_encode(['message' => "Downloads resumed for transfer $uuid"]);
                    break;

                default:
                    echo json_encode(['error' => 'Invalid action']);
                    break;
            }
        } else {
            echo json_encode(['error' => 'Missing action or transfer uuid']);
        }
        break;

    case 'GET':
        // Fetch the status of a specific transfer or all transfers
        if (isset($_GET['uuid'])) {
            $stmt = $pdo->prepare("SELECT * FROM transfers WHERE uuid = ?");
            $stmt->execute([$_GET['uuid']]);
        } else {
            $stmt = $pdo->query("SELECT * FROM transfers");
        }
        $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($transfers);
        break;

    case 'PUT':
        // Periodically check the status of all files under a specific transfer and update the database
        if (isset($input['uuid'])) {
            $uuid = $input['uuid'];
            $stmt = $pdo->prepare("SELECT gid FROM files WHERE transfer_id = ? AND (file_status IS NULL OR file_status != 'complete')");
            $stmt->execute([$uuid]);
            $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allDownloadsComplete = true;

            foreach ($downloads as $download) {
                $gid = $download['gid'];
                $statusResponse = sendRpcRequest('aria2.tellStatus', [$gid]);

                if (isset($statusResponse['result'])) {
                    $status = $statusResponse['result']['status'];
                    $completedSize = $statusResponse['result']['completedLength'];
                    $totalLength = $statusResponse['result']['totalLength'];
                    $percentage = ($totalLength > 0) ? ($completedSize / $totalLength) * 100 : 0;
                    updateDownloadStatus($pdo, $gid, $status, $completedSize, round($percentage, 2));

                    if ($status === 'complete') {
                        updateDownloadStatus($pdo, $gid, 'complete', $completedSize, 100);
                    } else {
                        $allDownloadsComplete = false;
                    }
                }
            }

            $transferProgress = calculateTransferProgress($pdo, $uuid);
            updateTransferStatus($pdo, $uuid, $allDownloadsComplete ? 'complete' : 'in_progress', round($transferProgress, 2));
            echo json_encode(['message' => 'Transfer status updated', 'progress' => $transferProgress]);
        } else {
            echo json_encode(['error' => 'Missing transfer uuid']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid request method']);
        break;
}
?>
