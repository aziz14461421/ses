<?php

$servername = "localhost";
$database = "ses";
$username = "root";
$password = "";

// Create a connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check the connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully\n";

// Read JSON data from fddhrppddmtghdz.json file
$json_data = file_get_contents('./data/fddhrppddmtghdz.json');

// Decode JSON data
$data = json_decode($json_data, true);

// Extract the transfer ID from the JSON data
$transfer_uuid = $data['transfer']['id'];

// Fetch the transfer_id from the transfers table using the UUID
$transfer_id_query = "SELECT uuid FROM transfers WHERE id = '$transfer_uuid'";
$transfer_id_result = mysqli_query($conn, $transfer_id_query);

if ($transfer_id_result && mysqli_num_rows($transfer_id_result) > 0) {
    $transfer_id_row = mysqli_fetch_assoc($transfer_id_result);
    $transfer_id = $transfer_id_row['uuid'];
    
    // Loop through each file in the JSON data
    foreach ($data['transfer']['files'] as $file) {
        // Extract values from JSON
        $file_id = $file['fileid'];
        $filename = $file['filename'];
        $filesize = $file['filesize'];
        $download_url = $file['downloadurl'];
        $preview_url = $file['previewurl'];
        $has_custom_preview = $file['hascustompreview'] ? 1 : 0;
        $filetype = $file['filetype'];
        $filetype_description = $file['filetypedescription'];
        $category = $file['category'];
        $small_preview = $file['smallpreview'];
        $medium_preview = $file['mediumpreview'];
        $large_preview = $file['largepreview'];
        $has_custom_thumbnail = $file['hascustomthumbnail'] ? 1 : 0;
        $md5 = $file['md5'];
        $suspected_damage = $file['suspecteddamage'] ? 1 : 0;
        $GID = $file['fileid']; // Assuming GID is the fileid
        $download_status = "Pending"; // Assuming a default value
        $completed_size = 0; // Assuming a default value
        $percentage = 0; // Assuming a default value
        $custom_logo_url = $data['transfer']['customlogourl'];
        $compressed_file_url = $data['transfer']['compressedfileurl'];
        $compressed_file_status = $data['transfer']['compressedfilestatus'];
        $compressed_file_format = $data['transfer']['compressedfileformat'];
        $torrent_status = $data['transfer']['torrentstatus'];
        $torrent_url = $data['transfer']['torrenturl'];
        $fileserver = $data['transfer']['fileserver'];
        $fileserver_url = $data['transfer']['fileserverurl'];
        $fileserver_url_main = $data['transfer']['fileserverurl_main'];
        $footer_text = $data['transfer']['footertext'];
        $antivirus_scan_status = "Not Scanned"; // Assuming a default value
        $download_percentage = 0; // Assuming a default value

        // SQL query to insert data into files table
        $sql = "INSERT INTO files (
            file_id, transfer_id, filename, filesize, download_url, preview_url, has_custom_preview, filetype, filetype_description,
            category, small_preview, medium_preview, large_preview, has_custom_thumbnail, md5, suspected_damage, GID,
            download_status, completed_size, percentage, custom_logo_url, compressed_file_url, compressed_file_status,
            compressed_file_format, torrent_status, torrent_url, fileserver, fileserver_url, fileserver_url_main,
            footer_text, antivirus_scan_status, download_percentage
        ) VALUES (
            '$file_id', '$transfer_id', '$filename', $filesize, '$download_url', '$preview_url', '$has_custom_preview',
            '$filetype', '$filetype_description', '$category', '$small_preview', '$medium_preview', '$large_preview',
            '$has_custom_thumbnail', '$md5', '$suspected_damage', '$GID', '$download_status', $completed_size,
            $percentage, '$custom_logo_url', '$compressed_file_url', '$compressed_file_status', '$compressed_file_format',
            '$torrent_status', '$torrent_url', '$fileserver', '$fileserver_url', '$fileserver_url_main', '$footer_text',
            '$antivirus_scan_status', $download_percentage
        )";

        // Execute the query and handle errors
        try {
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("MySQL error " . mysqli_error($conn) . " when executing query: " . $sql);
            }
            echo "New record created successfully for file ID: $file_id\n";
        } catch (Exception $e) {
            // Handle exception and print a custom error message
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "Error: The file with the ID '$file_id' already exists in your DB.\n";
            } else {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
} else {
    echo "Error: Transfer with UUID '$transfer_uuid' not found in transfers table.\n";
}

// Close the connection
mysqli_close($conn);

?>

