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
    $stmt = $pdo->prepare("UPDATE files SET file_status = ?, completed_size = ?, percentage = ? WHERE GID = ?");
    $stmt->execute([$status, $completedSize, $percentage, $gid]);
}

// Connect to the MySQL database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Main loop for downloading and showing progress
while (true) {
    // Clear the terminal
    system('clear');

    // Fetch all download URIs from the files table
    $query = "SELECT file_id, download_url, gid FROM files WHERE file_status IS NULL OR file_status != 'complete'";
    $stmt = $pdo->query($query);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send each file URL to aria2c for downloading if not already started
    foreach ($files as $file) {
        $downloadUrl = $file['download_url'];
        $fileId = $file['file_id'];

        // Check if file already has a GID (existing download), or start a new one
        if (empty($file['gid'])) {
            // Start a new download via aria2c, specifying the directory for the downloaded file
            $response = sendRpcRequest('aria2.addUri', [[ $downloadUrl ], ['dir' => $downloadDir]]);

            if (isset($response['result'])) {
                // Get the GID from the response
                $gid = $response['result'];

                // Save the GID in the database
                $stmt = $pdo->prepare("UPDATE files SET gid = ? WHERE file_id = ?");
                $stmt->execute([$gid, $fileId]);

                echo "Download started for file ID $fileId with GID: $gid\n";
            } else {
                echo "Failed to start download for file ID $fileId: " . json_encode($response) . "\n";
            }
        }
    }

    // Periodically check the status of ongoing downloads
    $query = "SELECT gid FROM files WHERE file_status IS NULL OR file_status != 'complete'";
    $stmt = $pdo->query($query);
    $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allDownloadsComplete = true; // Flag to check if all downloads are complete

    foreach ($downloads as $download) {
        $gid = $download['gid'];

        // Get the status of the download from aria2c
        $statusResponse = sendRpcRequest('aria2.tellStatus', [$gid]);

        if (isset($statusResponse['result'])) {
            $status = $statusResponse['result']['status'];
            $completedSize = $statusResponse['result']['completedLength'];
            $totalLength = $statusResponse['result']['totalLength'];

            // Calculate the percentage manually
            $percentage = ($totalLength > 0) ? ($completedSize / $totalLength) * 100 : 0;

            // Update the database with the download status
            updateDownloadStatus($pdo, $gid, $status, $completedSize, round($percentage, 2));

            echo "Download status for GID $gid: $status, Completed: $completedSize bytes, Percentage: " . round($percentage, 2) . "%\n";

            // If the download is complete, update the status in the database
            if ($status === 'complete') {
                // Update the file status to 'downloaded'
                updateDownloadStatus($pdo, $gid, 'downloaded', $completedSize, 100);
                echo "Download complete for GID: $gid\n";
            } else {
                $allDownloadsComplete = false; // At least one download is still in progress
            }
        } else {
            echo "Failed to fetch status for GID $gid: " . json_encode($statusResponse) . "\n";
        }
    }

    // If all downloads are complete, exit the loop
    if ($allDownloadsComplete) {
        echo "All downloads are complete.\n";
        break;
    }

    // Sleep for 5 seconds before the next status check
    sleep(1);
}
?>


