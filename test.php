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
echo "Connected successfully";

// JSON data
$json_data = '{
      "to": [
        "admin@domain.com"
      ],
      "recipients": [
        {
          "email": "admin@domain.com",
          "downloadlink": "https://www.domain.com/t/WsvuRrNt",
          "delivered": true
        }
      ],
      "failedRecipients": 0,
      "from": "user0@gmail.com",
      "subject": "",
      "message": "",
      "expiredate": 1696756945319,
      "extendedexpiredate": -1,
      "sentdate": 1694163645047,
      "status": "STATUS_COMPLETE",
      "id": "fddhrppddmtghdz",
      "trackid": "WsvuRrNt",
      "url": "https://www.domain.com/t/WsvuRrNt",
      "size": 7758359136,
      "days": 30,
      "isexpired": false,
      "source": "Web",
      "customfields": [
        {
          "label": "Elokuvan titteli",
          "visible": false,
          "rendertype": 0,
          "value": "Filmskolan"
        }
      ],
      "files": [],
      "numberoffiles": 8,
      "numberofdownloads": 9,
      "downloads": [],
      "passwordprotected": false,
      "iconcolor": "#7FAD46",
      "iconletter": "M",
      "ftphost": "3008.domain.com",
      "ftpcorppasswordrequired": false,
      "udpthreshold": 10000,
      "permanent": false,
      "maxdays": 3650,
      "alloweditingexpiredate": false,
      "blockdownloads": false,
      "infected": false,
      "occupiesstorage": true
}';

// Decode JSON data
$data = json_decode($json_data, true);

// Extract values from JSON
$to_email = $data['to'][0];
$from_email = $data['from'];
$subject = $data['subject'];
$message = $data['message'];
$expire_date = $data['expiredate'];
$extended_expire_date = $data['extendedexpiredate'];
$sent_date = $data['sentdate'];
$status = $data['status'];
$id = $data['id'];
$track_id = $data['trackid'];
$url = $data['url'];
$size = $data['size'];
$days = $data['days'];
$is_expired = $data['isexpired'] ? 1 : 0;
$source = $data['source'];
$custom_field_label = $data['customfields'][0]['label'];
$custom_field_visible = $data['customfields'][0]['visible'] ? 1 : 0;
$custom_field_render_type = $data['customfields'][0]['rendertype'];
$custom_field_value = $data['customfields'][0]['value'];
$number_of_files = $data['numberoffiles'];
$number_of_downloads = $data['numberofdownloads'];
$password_protected = $data['passwordprotected'] ? 1 : 0;
$icon_color = $data['iconcolor'];
$icon_letter = $data['iconletter'];
$ftp_host = $data['ftphost'];
$ftp_corp_password_required = $data['ftpcorppasswordrequired'] ? 1 : 0;
$udp_threshold = $data['udpthreshold'];
$permanent = $data['permanent']? 1 : 0;
$max_days = $data['maxdays'];
$allow_editing_expire_date = $data['alloweditingexpiredate'] ? 1 : 0;
$block_downloads = $data['blockdownloads'] ? 1 : 0;
$infected = $data['infected'] ? 1 : 0;
$occupies_storage = $data['occupiesstorage'] ? 1 : 0;

// SQL query to insert data into transfers table
$sql = "INSERT INTO transfers (id, to_email, from_email, subject, message, expire_date, extended_expire_date, sent_date, status, track_id, url, size, days, is_expired, source, custom_field_label, custom_field_visible, custom_field_render_type, custom_field_value, number_of_files, number_of_downloads, password_protected, icon_color, icon_letter, ftp_host, ftp_corp_password_required, udp_threshold, permanent, max_days, allow_editing_expire_date, block_downloads, infected, occupies_storage)
VALUES ('$id', '$to_email', '$from_email', '$subject', '$message', '$expire_date', '$extended_expire_date', '$sent_date', '$status', '$track_id', '$url', $size, $days, '$is_expired', '$source', '$custom_field_label', '$custom_field_visible', $custom_field_render_type, '$custom_field_value', $number_of_files, $number_of_downloads, '$password_protected', '$icon_color', '$icon_letter', '$ftp_host', '$ftp_corp_password_required', $udp_threshold, '$permanent', $max_days, '$allow_editing_expire_date', '$block_downloads', '$infected', '$occupies_storage')";

// Execute the query and handle errors
try {
    // Execute the query and handle errors
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("MySQL error " . mysqli_error($conn) . " when executing query: " . $sql);
    }
    echo "New record created successfully\n";
} catch (Exception $e) {
    // Handle exception and print a custom error message
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "Error: The transfer with the ID : '$id' Already exists in your DB.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

mysqli_close($conn);

?>
