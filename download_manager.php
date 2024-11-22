<?php
// Function to start downloads
function startDownloads($pdo, $uuid, $downloadDir) {
    // Fetch all files for the given transfer UUID where status is not 'complete'
    $stmt = $pdo->prepare("SELECT file_id, download_url, gid FROM files WHERE transfer_id = ? AND (file_status IS NULL OR file_status != 'complete')");
    $stmt->execute([$uuid]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $downloadsInitiated = 0;

    foreach ($files as $file) {
        $downloadUrl = $file['download_url'];
        $fileId = $file['file_id'];

        // Check if file already has a GID (ongoing download), if not, start a new download
        if (empty($file['gid'])) {
            // Start a new download via aria2c
            $response = sendRpcRequest('aria2.addUri', [[$downloadUrl], ['dir' => $downloadDir]]);

            if (isset($response['result'])) {
                // Get the GID from the response
                $gid = $response['result'];

                // Save the GID in the database and update file status to 'downloading'
                $stmt = $pdo->prepare("UPDATE files SET gid = ?, file_status = 'downloading' WHERE file_id = ?");
                $stmt->execute([$gid, $fileId]);

                $downloadsInitiated++;
            }
        }
    }

    if ($downloadsInitiated > 0) {
        updateTransferStatus($pdo, $uuid, 'in_progress', 0);
        return ['message' => "Downloads started for transfer $uuid"];
    } else {
        return ['message' => "No downloads were initiated. All files may have already been started or completed."];
    }
}

// Function to pause or resume downloads
function pauseOrResumeDownloads($pdo, $uuid, $action) {
    $stmt = $pdo->prepare("SELECT gid FROM files WHERE transfer_id = ? AND file_status = 'downloading'");
    $stmt->execute([$uuid]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($files as $file) {
        $gid = $file['gid'];

        if ($action === 'pause') {
            // Pause the download
            $response = sendRpcRequest('aria2.pause', [$gid]);
            if (isset($response['result'])) {
                updateDownloadStatus($pdo, $gid, 'paused');
            }
        } elseif ($action === 'resume') {
            // Resume the download
            $response = sendRpcRequest('aria2.unpause', [$gid]);
            if (isset($response['result'])) {
                updateDownloadStatus($pdo, $gid, 'downloading');
            }
        }
    }

    return ['message' => "Downloads for transfer $uuid have been {$action}d."];
}

// Fetch the request method and payload
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        if (isset($input['uuid'], $input['action'])) {
            $uuid = $input['uuid'];
            $action = $input['action'];

            if ($action === 'start') {
                $response = startDownloads($pdo, $uuid, $downloadDir);
                echo json_encode($response);
            } elseif ($action === 'pause' || $action === 'resume') {
                $response = pauseOrResumeDownloads($pdo, $uuid, $action);
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Invalid action. Supported actions are start, pause, and resume.']);
            }
        } else {
            echo json_encode(['error' => 'Missing transfer uuid or action.']);
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

            // Fetch all downloads under the transfer
            $stmt = $pdo->prepare("SELECT gid FROM files WHERE transfer_id = ? AND (file_status IS NULL OR file_status != 'complete')");
            $stmt->execute([$uuid]);
            $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $allDownloadsComplete = true;

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

                    // If the download is complete, update the status
                    if ($status !== 'complete') {
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
