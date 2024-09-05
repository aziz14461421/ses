<?php

// URL to send the GET request to
$url = "http://echo.jsontest.com/key/value/one/two";

// Initialize a cURL session
$ch = curl_init();

// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the GET request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    // Decode the JSON response
    $json_data = json_decode($response, true);

    // Define the file path where the JSON data will be saved
    $file_path = './response.json';

    // Save the JSON data to the file
    if (file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT))) {
        echo "JSON data successfully saved to $file_path";
    } else {
        echo "Failed to save JSON data to $file_path";
    }
}

// Close the cURL session
curl_close($ch);

?>

