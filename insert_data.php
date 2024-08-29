<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$servername = "localhost";
$username = "root";
$password = "azer1234";
$dbname = "transfers";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read JSON file
$json = file_get_contents('/var/www/html/data.json');
if ($json === false) {
    die("Failed to read JSON file.");
}
$data = json_decode($json, true);
if ($data === null) {
    die("Failed to parse JSON file.");
}

// Prepare insert statement
$insert_stmt = $conn->prepare("INSERT INTO transfer (id, `to`, recipients, failedRecipients, `from`, subject, message, expiredate, extendedexpiredate, sentdate, status, trackid, url, size, days, isexpired, source, customfields, files, numberoffiles, numberofdownloads, downloads, passwordprotected, iconcolor, iconletter, ftphost, ftpcorppasswordrequired, udpthreshold, permanent, maxdays, alloweditingexpiredate, blockdownloads, infected, occupiesstorage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Check if prepare was successful
if (!$insert_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

// Bind parameters for insert statement
$insert_stmt->bind_param("sssisssiiiiissiisisissssisiiisiisii",
    $id, $to, $recipients, $failedRecipients, $from, $subject, $message, $expiredate, $extendedexpiredate, $sentdate, $status, $trackid, $url, $size, $days, $isexpired, $source, $customfields, $files, $numberoffiles, $numberofdownloads, $downloads, $passwordprotected, $iconcolor, $iconletter, $ftphost, $ftpcorppasswordrequired, $udpthreshold, $permanent, $maxdays, $alloweditingexpiredate, $blockdownloads, $infected, $occupiesstorage);

// Prepare select statement
$select_stmt = $conn->prepare("SELECT id FROM transfer WHERE id = ?");
if (!$select_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

// Bind parameter for select statement
$select_stmt->bind_param("s", $id);

// Loop through the JSON data and insert into the database if not exists
foreach ($data['transfers'] as $transfer) {
    $id = $transfer['id'];

    // Execute select statement
    $select_stmt->execute();
    $select_stmt->store_result();

    // Check if the transfer ID already exists
    if ($select_stmt->num_rows == 0) {
        // Process other fields as shown earlier
        
        // Execute the insert statement
        if (!$insert_stmt->execute()) {
            echo "Insert failed for ID $id: (" . $insert_stmt->errno . ") " . $insert_stmt->error;
        } else {
            echo "Record for ID $id inserted successfully.\n";
        }
    }
}

// Close statements and connection
$select_stmt->close();
$insert_stmt->close();
$conn->close();

echo "Records processed successfully";
?>

