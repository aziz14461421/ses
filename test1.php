<?php
// Database credentials
$host = 'localhost';
$dbname = 'ses';
$user = 'root';
$pass = '';

// aria2c RPC endpoint
$rpcUrl = 'http://localhost:6800/jsonrpc';

// Directory for downloaded files
$downloadDir = __DIR__ . '/downloaded/';

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
    try {
        $stmt = $pdo->prepare("UPDATE files SET file_status = ?, completed_size = ?, percentage = ? WHERE gid = ?");
        $stmt->execute([$status, $completedSize, $percentage, $gid]);
    } catch (PDOException $e) {
        echo "Error updating download status: " . $e->getMessage() . "\n";
    }
}

// Function to update transfer status in the transfers table
function updateTransferStatus($pdo, $transferId) {
    try {
        $stmt = $pdo->prepare("UPDATE transfers SET transfer_status = 'completed' WHERE uuid = ?");
        $stmt->execute([$transferId]);
    } catch (PDOException $e) {
        echo "Error updating transfer status: " . $e->getMessage() . "\n";
    }
}

// Connect to the MySQL database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Handle incoming API requests
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Fetch ongoing transfers
if ($method === 'GET' && $uri === '/getTransfers') {
    $stmt = $pdo->query("SELECT f.gid, f.transfer_id, f.filename, f.file_status as status, t.uuid 
                         FROM files f
                         INNER JOIN transfers t ON f.transfer_id = t.uuid
                         WHERE t.transfer_status IS NULL OR t.transfer_status != 'completed'");
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($transfers);
    exit;
}

// Handle control actions like pause, resume, delete
if ($method === 'POST' && $uri === '/controlTransfer') {
    $input = json_decode(file_get_contents('php://input'), true);
    $gid = $input['gid'];
    $action = $input['action'];

    $response = [];
    switch ($action) {
        case 'pause':
            $response = sendRpcRequest('aria2.pause', [$gid]);
            break;
        case 'resume':
            $response = sendRpcRequest('aria2.unpause', [$gid]);
            break;
        case 'delete':
            $response = sendRpcRequest('aria2.remove', [$gid]);
            break;
        case 'download':
            // Assuming you have logic to initiate a download based on the gid
            $response = sendRpcRequest('aria2.addUri', [[/* URIs */, ['gid' => $gid]]]);
            break;
        default:
            echo json_encode(['message' => 'Unknown action']);
            exit;
    }

    if (isset($response['error'])) {
        echo json_encode(['message' => 'Failed to execute action: ' . $response['error']['message']]);
    } else {
        echo json_encode(['message' => ucfirst($action) . ' action performed successfully']);
    }
    exit;
}

// Main loop for monitoring downloads
while (true) {
    // Clear the terminal
    system('clear');

    // Fetch all ongoing transfers
    $query = "SELECT uuid FROM transfers WHERE transfer_status IS NULL OR transfer_status != 'completed'";
    $stmt = $pdo->query($query);
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if there are any transfers
    if (empty($transfers)) {
        echo "No transfers to process.\n";
        break;
    }

    foreach ($transfers as $transfer) {
        $transferId = $transfer['uuid'];

        // Fetch all files for the current transfer
        $query = "SELECT gid, file_status FROM files WHERE transfer_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$transferId]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $allFilesComplete = true; // Flag to check if all files of the current transfer are complete

        foreach ($files as $file) {
            $gid = $file['gid'];
            $fileStatus = $file['file_status'];

            // If the file is not complete, get its status
            if ($fileStatus !== 'completed') {
                $statusResponse = sendRpcRequest('aria2.tellStatus', [$gid]);

                if (isset($statusResponse['result'])) {
                    $status = $statusResponse['result']['status'];
                    $completedSize = $statusResponse['result']['completedLength'];
                    $totalLength = $statusResponse['result']['totalLength'];

                    // Calculate the percentage
                    $percentage = ($totalLength > 0) ? ($completedSize / $totalLength) * 100 : 0;

                    // Update the download status
                    updateDownloadStatus($pdo, $gid, $status, $completedSize, round($percentage, 2));

                    echo "Download status for GID $gid: $status, Completed: $completedSize bytes, Percentage: " . round($percentage, 2) . "%\n";

                    // Check if the download is complete
                    if ($status === 'complete') {
                        updateDownloadStatus($pdo, $gid, 'completed', $completedSize, 100);
                    } else {
                        $allFilesComplete = false; // At least one file is still downloading
                    }
                } else {
                    echo "Failed to fetch status for GID $gid: " . json_encode($statusResponse) . "\n";
                    $allFilesComplete = false; // Continue to check other files
                }
            }
        }

        // If all files are complete for the transfer, update the transfer status
        if ($allFilesComplete) {
            updateTransferStatus($pdo, $transferId);
            echo "All files downloaded for transfer ID: $transferId. Transfer status updated.\n";
        }
    }

    // Sleep for 1 seconds before the next status check
    sleep(1);
}
?>

