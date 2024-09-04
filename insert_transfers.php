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
    $recipients_delivered = $transfer['recipients'][0]['delivered']? 1 : 0;
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

    // SQL query to insert data into transfers table
    $sql = "INSERT INTO transfers (id, to_email, recipient_email, recipient_download_link, recipient_delivered, failed_recipients, from_email, subject, message, expire_date, extended_expire_date, sent_date, status, track_id, url, size, days, is_expired, source, custom_field_label, custom_field_visible, custom_field_render_type, custom_field_value, number_of_files, number_of_downloads, password_protected, icon_color, icon_letter, ftp_host, ftp_corp_password_required, udp_threshold, permanent, max_days, allow_editing_expire_date, block_downloads, infected, occupies_storage)
    VALUES ('$id', '$to_email', '$recipients_email', '$recipients_download_link', '$recipients_delivered', '$failed_recipients', '$from_email', '$subject', '$message', '$expire_date', '$extended_expire_date', '$sent_date', '$status', '$track_id', '$url', $size, $days, '$is_expired', '$source', '$custom_field_label', '$custom_field_visible', $custom_field_render_type, '$custom_field_value', $number_of_files, $number_of_downloads, '$password_protected', '$icon_color', '$icon_letter', '$ftp_host', '$ftp_corp_password_required', $udp_threshold, '$permanent', $max_days, '$allow_editing_expire_date', '$block_downloads', '$infected', '$occupies_storage')";

    // Execute the query and handle errors
    try {
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("MySQL error " . mysqli_error($conn) . " when executing query: " . $sql);
        }
        echo "New record created successfully for ID: $id\n";
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

?>

