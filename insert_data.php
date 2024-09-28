<?php

// API Connection Test
// URL to send the GET request to
$url = "http://echo.jsontest.com/key/value/one/two";

// Initialize a cURL session
$ch = curl_init();

// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Set timeout to 5 seconds

// Execute the GET request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    // Output error message and stop script execution
    echo 'cURL Error: ' . curl_error($ch);
    curl_close($ch);
    exit();  // Stop script execution if the API request fails
}

// If no errors, proceed to handle the response
if ($response === false) {
    echo "Error: Failed to get a valid response from the API.";
    curl_close($ch);
    exit();
}

// Decode the JSON response
$json_data = json_decode($response, true);

// Check if the response is valid JSON
if (json_last_error() === JSON_ERROR_NONE) {
    echo "API connection successful, Received a valid JSON response.\n";
} else {
    echo "API connection successful, but the response is not valid JSON.\n";
}

// Close the cURL session
curl_close($ch);

//*******************************************************************************************************
function insert_files($transfer_id)
{
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

    $json_file_path = './data/' . $transfer_id . '.json';
    if (!file_exists($json_file_path)) {
        echo "Error: File for transfer ID '$transfer_id' not found.\n";
        return;
    }

    // Read JSON data from the file
    $json_data = file_get_contents($json_file_path);

    // Decode JSON data
    $data = json_decode($json_data, true);

    // Fetch the transfer_id from the transfers table using the UUID
    $transfer_id_query = "SELECT uuid FROM transfers WHERE id = '$transfer_id'";
    $transfer_uuid_result = mysqli_query($conn, $transfer_id_query);

    if ($transfer_uuid_result && mysqli_num_rows($transfer_uuid_result) > 0) {
        $transfer_uuid_row = mysqli_fetch_assoc($transfer_uuid_result);
        $transfer_uuid = $transfer_uuid_row['uuid'];

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
            $gid = '';
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

            // Check if the file_id already exists in the files table
            $check_file_query = "SELECT file_id, md5 FROM files WHERE file_id = '$file_id'";
            $check_file_result = mysqli_query($conn, $check_file_query);

            if ($md5 === null || $md5 == "") {
                if ($check_file_result && mysqli_num_rows($check_file_result) > 0) {
                    echo "File details exist in DB and md5 is still missing for file ID: $file_id.\n";
                } else {
                    // Insert file details with null md5 and file_status = 'missing_md5'
                    $file_status = 'missing_md5';
                    $sql = "INSERT INTO files (
                        file_id, transfer_id, filename, filesize, download_url, preview_url, has_custom_preview, filetype,
                        filetype_description, category, small_preview, medium_preview, large_preview, has_custom_thumbnail, 
                        md5, suspected_damage, gid, completed_size, percentage, custom_logo_url, 
                        compressed_file_url, compressed_file_status, compressed_file_format, torrent_status, torrent_url, 
                        fileserver, fileserver_url, fileserver_url_main, footer_text, antivirus_scan_status, download_percentage, file_status
                    ) VALUES (
                        '$file_id', '$transfer_uuid', '$filename', $filesize, '$download_url', '$preview_url', '$has_custom_preview', 
                        '$filetype', '$filetype_description', '$category', '$small_preview', '$medium_preview', '$large_preview', 
                        '$has_custom_thumbnail', NULL, '$suspected_damage', '$gid', $completed_size, 
                        $percentage, '$custom_logo_url', '$compressed_file_url', '$compressed_file_status', '$compressed_file_format', 
                        '$torrent_status', '$torrent_url', '$fileserver', '$fileserver_url', '$fileserver_url_main', '$footer_text', 
                        '$antivirus_scan_status', '$file_status'
                    )";

                    if (mysqli_query($conn, $sql)) {
                        echo "File details inserted to DB and md5 is still missing for file ID: $file_id.\n";
                    } else {
                        echo "Error inserting file with missing md5: " . mysqli_error($conn) . "\n";
                    }
                }
            } else {
                if ($check_file_result && mysqli_num_rows($check_file_result) > 0) {
                    // Update md5 and set file_status to 'pending'
                    $sql_update = "UPDATE files SET md5 = '$md5', file_status = 'pending' WHERE file_id = '$file_id'";
                    if (mysqli_query($conn, $sql_update)) {
                        echo "File details exist in DB and md5 updated for file ID: $file_id.\n";
                    } else {
                        echo "Error updating file with md5: " . mysqli_error($conn) . "\n";
                    }
                } else {
                    // Insert file details with md5 and file_status = 'pending'
                    $file_status = 'pending';
                    $sql = "INSERT INTO files (
                        file_id, transfer_id, filename, filesize, download_url, preview_url, has_custom_preview, filetype,
                        filetype_description, category, small_preview, medium_preview, large_preview, has_custom_thumbnail, 
                        md5, suspected_damage, gid, completed_size, percentage, custom_logo_url, 
                        compressed_file_url, compressed_file_status, compressed_file_format, torrent_status, torrent_url, 
                        fileserver, fileserver_url, fileserver_url_main, footer_text, antivirus_scan_status, file_status
                    ) VALUES (
                        '$file_id', '$transfer_uuid', '$filename', $filesize, '$download_url', '$preview_url', '$has_custom_preview', 
                        '$filetype', '$filetype_description', '$category', '$small_preview', '$medium_preview', '$large_preview', 
                        '$has_custom_thumbnail', '$md5', '$suspected_damage', '$gid', $completed_size, 
                        $percentage, '$custom_logo_url', '$compressed_file_url', '$compressed_file_status', '$compressed_file_format', 
                        '$torrent_status', '$torrent_url', '$fileserver', '$fileserver_url', '$fileserver_url_main', '$footer_text', 
                        '$antivirus_scan_status', '$file_status'
                    )";

                    if (mysqli_query($conn, $sql)) {
                        echo "File details inserted to DB and md5 is present for file ID: $file_id.\n";
                    } else {
                        echo "Error inserting file with md5: " . mysqli_error($conn) . "\n";
                    }
                }
            }
        }
    } else {
        echo "Error: Transfer with ID '$transfer_id' not found in transfers table.\n";
    }

    // Close the database connection
    mysqli_close($conn);
}
/********************************************************************************************** */


//Database Connection


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
echo "DB connection successfull\n";

/* Function to convert empty strings to NULL
function emptyToNull($value)
{
    $value = 'test';
    return $value = '' ? NULL : $value;
}*/

// Read JSON data from data.json file
$json_data = file_get_contents('./data/data.json');

// Decode JSON data
$data = json_decode($json_data, true);

// Loop through each transfer in the JSON data
foreach ($data['transfers'] as $transfer) {
    // Extract values from JSON
    $to_email = $transfer['to'][0];
    $recipients_email = $transfer['recipients'][0]['email'];
    $recipients_download_link = $transfer['recipients'][0]['downloadlink'];
    $recipients_delivered = $transfer['recipients'][0]['delivered'] ? 1 : 0;
    $failed_recipients = $transfer['failedRecipients'];
    $from_email = $transfer['from'];
    $subject = $transfer['subject'];
    $message = $transfer['message'];
    $expire_date = $transfer['expiredate'];
    $extended_expire_date = $transfer['extendedexpiredate'];
    $sent_date = $transfer['sentdate'];
    $status = $transfer['status'];
    $id = $transfer['id'];
    $track_id = $transfer['trackid'];
    $url = $transfer['url'];
    $size = $transfer['size'];
    $days = $transfer['days'];
    $is_expired = $transfer['isexpired'] ? 1 : 0;
    $source = $transfer['source'];
    $custom_field_label = $transfer['customfields'][0]['label'];
    $custom_field_visible = $transfer['customfields'][0]['visible'] ? 1 : 0;
    $custom_field_render_type = $transfer['customfields'][0]['rendertype'];
    $custom_field_value = mysqli_real_escape_string($conn, $transfer['customfields'][0]['value']);
    $number_of_files = $transfer['numberoffiles'];
    $number_of_downloads = $transfer['numberofdownloads'];
    $password_protected = $transfer['passwordprotected'] ? 1 : 0;
    $icon_color = $transfer['iconcolor'];
    $icon_letter = $transfer['iconletter'];
    $ftp_host = $transfer['ftphost'];
    $ftp_corp_password_required = $transfer['ftpcorppasswordrequired'] ? 1 : 0;
    $udp_threshold = $transfer['udpthreshold'];
    $permanent = $transfer['permanent'] ? 1 : 0;
    $max_days = $transfer['maxdays'];
    $allow_editing_expire_date = $transfer['alloweditingexpiredate'] ? 1 : 0;
    $block_downloads = $transfer['blockdownloads'] ? 1 : 0;
    $infected = $transfer['infected'] ? 1 : 0;
    $occupies_storage = $transfer['occupiesstorage'] ? 1 : 0;
    $transfer_status = 'pending';
    $percentage = 0;
    // SQL query to insert data into transfers table
    $sql = "INSERT INTO transfers (id, to_email, recipient_email, recipient_download_link, recipient_delivered, failed_recipients, from_email, subject, message, expire_date, extended_expire_date, sent_date, status, track_id, url, size, days, is_expired, source, custom_field_label, custom_field_visible, custom_field_render_type, custom_field_value, number_of_files, number_of_downloads, password_protected, icon_color, icon_letter, ftp_host, ftp_corp_password_required, udp_threshold, permanent, max_days, allow_editing_expire_date, block_downloads, infected, occupies_storage, transfer_status,percentage)
    VALUES ('$id', '$to_email', '$recipients_email', '$recipients_download_link', '$recipients_delivered', '$failed_recipients', '$from_email', '$subject', '$message', '$expire_date', '$extended_expire_date', '$sent_date', '$status', '$track_id', '$url', $size, $days, '$is_expired', '$source', '$custom_field_label', '$custom_field_visible', $custom_field_render_type, '$custom_field_value', $number_of_files, $number_of_downloads, '$password_protected', '$icon_color', '$icon_letter', '$ftp_host', '$ftp_corp_password_required', $udp_threshold, '$permanent', $max_days, '$allow_editing_expire_date', '$block_downloads', '$infected', '$occupies_storage', '$transfer_status', '$percentage')";

    // Execute the query and handle errors
    try {
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("MySQL error " . mysqli_error($conn) . " when executing query: " . $sql);
        }
        echo "New transfer created successfully for ID: $id\n";
        insert_files($id);
    } catch (Exception $e) {
        // Handle exception and print a custom error message
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "Error: The transfer with the ID '$id' already exists in your DB.\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

// Close the connection
mysqli_close($conn);

