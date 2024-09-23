<?php

// Database connection
function db_connect() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ses";  // Your database name
    return new mysqli($host, $username, $password, $dbname);
}

// Function 1: send_uri - Send download URIs to aria2c
function send_uri($uuid) {
    echo "Sending URIs for transfer UUID: $uuid...\n";
    $db = db_connect();

    // Fetch download links and md5 for the specified uuid
    $query = "SELECT file_id, download_url, md5 FROM files WHERE transfer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "No files found for transfer UUID: $uuid.\n";
        return; // Exit if no files found
    }

    while ($row = $result->fetch_assoc()) {
        $downloadUrl = $row['download_url'];
        $md5 = $row['md5'];
        $file_id = $row['file_id'];

        // Prepare aria2c request
        $ariaRequest = [
            'jsonrpc' => '2.0',
            'method' => 'aria2.addUri',
            'id' => 'download',
            'params' => [[$downloadUrl], ['md5' => $md5]]
        ];
        $ariaResponse = send_to_aria(json_encode($ariaRequest));

        if ($ariaResponse && isset($ariaResponse->result)) {
            $gid = $ariaResponse->result;  // The GID from aria2c
            echo "File ID: $file_id sent to aria2c with GID: $gid\n";
            // Update file status in the database
            $updateQuery = "UPDATE files SET download_status = 'sent_to_aria', gid = ? WHERE file_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bind_param("si", $gid, $file_id);
            $updateStmt->execute();
        }
    }

    // Update package status
    $updatePackageQuery = "UPDATE transfers SET transfer_status = 'queued' WHERE uuid = ?";
    $updatePackageStmt = $db->prepare($updatePackageQuery);
    $updatePackageStmt->bind_param("s", $uuid);
    $updatePackageStmt->execute();

    echo "Transfer UUID: $uuid is now queued.\n";
    $stmt->close();
    $db->close();
}

// Helper function: send request to aria2c
function send_to_aria($jsonPayload) {
    $ch = curl_init('http://localhost:6800/jsonrpc');  // Adjust aria2c RPC URL as needed
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
}

// Function 2: update_uri - Monitor progress and update status in the database
function update_uri() {
    echo "Updating download progress...\n";
    $db = db_connect();

    // Get all files with status 'downloading'
    $query = "SELECT file_id, gid FROM files WHERE download_status = 'downloading'";
    $result = $db->query($query);

    if ($result->num_rows === 0) {
        echo "No files are currently downloading.\n";
        return; // Exit if no files found
    }

    while ($row = $result->fetch_assoc()) {
        $gid = $row['gid'];
        $file_id = $row['file_id'];

        // Query aria2c for the status of each download
        $ariaRequest = [
            'jsonrpc' => '2.0',
            'method' => 'aria2.tellStatus',
            'id' => 'status',
            'params' => [$gid]
        ];
        $ariaResponse = send_to_aria(json_encode($ariaRequest));

        if ($ariaResponse && isset($ariaResponse->result)) {
            $completedLength = $ariaResponse->result->completedLength;
            $totalLength = $ariaResponse->result->totalLength;
            $status = $ariaResponse->result->status;

            // Update database with download progress
            $percentage = ($totalLength > 0) ? ($completedLength / $totalLength) * 100 : 0;
            $updateQuery = "UPDATE files SET completed_size = ?, download_percentage = ?, download_status = ? WHERE file_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bind_param("dssi", $completedLength, $percentage, $status, $file_id);
            $updateStmt->execute();
            
            echo "File ID: $file_id - Status: $status - Progress: $percentage%\n";
        }
    }

    $db->close();
}

// Function 3: aria2_error - Handle aria2c download errors
function aria2_error() {
    echo "Checking for download errors...\n";
    $db = db_connect();

    // Get all files with an error
    $query = "SELECT file_id, gid FROM files WHERE download_status = 'error'";
    $result = $db->query($query);

    if ($result->num_rows === 0) {
        echo "No files have encountered errors.\n";
        return; // Exit if no errors found
    }

    while ($row = $result->fetch_assoc()) {
        $gid = $row['gid'];
        $file_id = $row['file_id'];

        // Query aria2c for the error status
        $ariaRequest = [
            'jsonrpc' => '2.0',
            'method' => 'aria2.tellStatus',
            'id' => 'error_status',
            'params' => [$gid]
        ];
        $ariaResponse = send_to_aria(json_encode($ariaRequest));

        if ($ariaResponse && isset($ariaResponse->result)) {
            $errorCode = $ariaResponse->result->errorCode;
            echo "File ID: $file_id encountered an error - Error Code: $errorCode\n";
            // Log the error code and update the database
            $updateQuery = "UPDATE files SET error_code = ? WHERE file_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bind_param("si", $errorCode, $file_id);
            $updateStmt->execute();
        }
    }

    $db->close();
}

// Function 4: update_package - Update package status based on file statuses
function update_package($uuid) {
    echo "Updating package status for UUID: $uuid...\n";
    $db = db_connect();

    // Check if all files in the package have completed
    $query = "SELECT download_status FROM files WHERE transfer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();

    $allComplete = true;
    while ($row = $result->fetch_assoc()) {
        if ($row['download_status'] != 'completed') {
            $allComplete = false;
            break;
        }
    }

    if ($allComplete) {
        // Update package status to 'completed'
        $updateQuery = "UPDATE transfers SET transfer_status = 'completed' WHERE uuid = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bind_param("s", $uuid);
        $updateStmt->execute();
        echo "Transfer UUID: $uuid has been marked as completed.\n";
    } else {
        echo "Transfer UUID: $uuid is not yet complete.\n";
    }

    $stmt->close();
    $db->close();
}

// Function 5: stage_complete - Mark files and package as fully completed
function stage_complete($uuid) {
    echo "Marking files and package as fully completed for UUID: $uuid...\n";
    $db = db_connect();

    // Mark all files related to this transfer as completed
    $updateFilesQuery = "UPDATE files SET download_status = 'completed' WHERE transfer_id = ?";
    $updateFilesStmt = $db->prepare($updateFilesQuery);
    $updateFilesStmt->bind_param("s", $uuid);
    $updateFilesStmt->execute();

    // Mark the transfer (package) as completed
    $updateTransferQuery = "UPDATE transfers SET transfer_status = 'completed' WHERE uuid = ?";
    $updateTransferStmt = $db->prepare($updateTransferQuery);
    $updateTransferStmt->bind_param("s", $uuid);
    $updateTransferStmt->execute();

    echo "Transfer UUID: $uuid has been fully completed.\n";
    $db->close();
}

// Main process to handle all transfers
function process_transfers() {
    echo "Starting transfer processing...\n";
    $db = db_connect();

    // Fetch all transfers that have files
    $query = "SELECT uuid FROM transfers WHERE EXISTS (SELECT 1 FROM files WHERE transfer_id = uuid)";
    $result = $db->query($query);

    if ($result->num_rows === 0) {
        echo "No transfers found with associated files.\n";
        return; // Exit if no transfers found
    }

    while ($row = $result->fetch_assoc()) {
        $uuid = $row['uuid'];
        send_uri($uuid);
        update_uri();
        aria2_error();
        update_package($uuid);
    }

    echo "Transfer processing completed.\n";
    $db->close();
}

// Execute the process
process_transfers();
?>
