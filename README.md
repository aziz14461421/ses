# Transfers Management System

This project is a Transfers Management System built using a combination of Bash scripts and PHP to manage file transfers. It consists of a database schema for storing transfer information and a script to insert data into the database from JSON files.

## Features

- **Database Management**: Automates the process of creating and removing a MySQL database and its tables.
- **File Handling**: Inserts file transfer information into the database using JSON data.
- **API Interaction**: Retrieves and processes data from an external API.

## Prerequisites

- **MySQL**: Ensure MySQL is installed and running on your system.
- **PHP**: PHP must be installed to run the insertion scripts.
- **cURL**: Required for making HTTP requests in the PHP script.
- **Bash**: Necessary to execute the shell scripts.

## Installation

1. Clone this repository:

   ```bash
   git clone <repository-url>
   cd <repository-directory>
   ```

2. Make the Bash script executable:

   ```bash
   chmod +x recover.sh
   ```

3. Run the `recover.sh` script to create the database and tables:

   ```bash
   ./recover.sh
   ```

4. Place your JSON files in the `./data/` directory.

## Usage

To insert data into the database, use the following command:

```bash
php insert_data.php
```

The `insert_data.php` script reads the JSON data from the specified file, processes it, and inserts it into the `transfers` and `files` tables in the MySQL database.

## File Structure

- `recover.sh`: Shell script for managing the MySQL database and tables.
- `insert_data.php`: PHP script for inserting data from JSON files into the database.
- `data/`: Directory containing JSON files for processing.

## Database Schema

The database consists of the following tables:

#### Table: `transfers`

| Field Name                        | Data Type    | Description                                             |
|-----------------------------------|--------------|---------------------------------------------------------|
| `uuid`                            | CHAR(36)     | Unique identifier for the transfer (UUID).              |
| `id`                              | VARCHAR(255) | Unique ID for the transfer.                             |
| `to_email`                       | VARCHAR(255) | Recipient's email address.                              |
| `recipient_email`                | VARCHAR(255) | Additional recipient's email address.                   |
| `recipient_download_link`         | VARCHAR(255) | Link for recipient to download the transfer.            |
| `recipient_delivered`             | BOOLEAN      | Indicates if the transfer was delivered.                |
| `failed_recipients`              | INT          | Number of failed delivery attempts.                      |
| `from_email`                     | VARCHAR(255) | Sender's email address.                                 |
| `subject`                        | TEXT         | Subject of the transfer.                                |
| `message`                        | TEXT         | Message accompanying the transfer.                       |
| `expire_date`                    | BIGINT       | Expiration date in Unix timestamp format.               |
| `extended_expire_date`           | BIGINT       | Extended expiration date in Unix timestamp format.      |
| `sent_date`                      | BIGINT       | Date when the transfer was sent (Unix timestamp).      |
| `status`                         | VARCHAR(255) | Current status of the transfer.                          |
| `track_id`                       | VARCHAR(255) | Tracking ID for the transfer.                           |
| `url`                            | VARCHAR(255) | URL associated with the transfer.                       |
| `size`                           | BIGINT       | Size of the transfer in bytes.                          |
| `days`                           | INT          | Duration in days before the transfer expires.           |
| `is_expired`                     | BOOLEAN      | Indicates if the transfer is expired.                   |
| `source`                         | VARCHAR(255) | Source of the transfer.                                 |
| `custom_field_label`             | VARCHAR(255) | Label for a custom field.                               |
| `custom_field_visible`           | BOOLEAN      | Indicates if the custom field is visible.               |
| `custom_field_render_type`       | INT          | Render type for the custom field.                       |
| `custom_field_value`             | VARCHAR(255) | Value of the custom field.                              |
| `number_of_files`                | INT          | Total number of files in the transfer.                  |
| `number_of_downloads`            | INT          | Total number of downloads for the transfer.             |
| `password_protected`             | BOOLEAN      | Indicates if the transfer is password protected.        |
| `icon_color`                     | VARCHAR(7)   | Color for the transfer icon (HEX format).              |
| `icon_letter`                    | CHAR(1)      | Single letter representing the transfer.                |
| `ftp_host`                       | VARCHAR(255) | FTP host for file transfers.                            |
| `ftp_corp_password_required`     | BOOLEAN      | Indicates if a corporate password is required for FTP.  |
| `udp_threshold`                  | INT          | UDP threshold for the transfer.                         |
| `permanent`                      | BOOLEAN      | Indicates if the transfer is permanent.                 |
| `max_days`                       | INT          | Maximum days before the transfer expires.               |
| `allow_editing_expire_date`      | BOOLEAN      | Indicates if editing of the expiration date is allowed. |
| `block_downloads`                | BOOLEAN      | Indicates if downloads are blocked.                     |
| `infected`                       | BOOLEAN      | Indicates if the files are infected.                    |
| `occupies_storage`               | BOOLEAN      | Indicates if the transfer occupies storage.             |
| `transfer_status`                | VARCHAR(255) | Status of the transfer process.                         |

#### Table: `files`

| Field Name                        | Data Type    | Description                                             |
|-----------------------------------|--------------|---------------------------------------------------------|
| `uuid`                            | VARCHAR(255) | Unique identifier for the file (UUID).                 |
| `file_id`                        | VARCHAR(255) | Unique ID for the file.                                |
| `transfer_id`                    | CHAR(255)    | Foreign key referencing the `transfers` table.         |
| `filename`                       | VARCHAR(255) | Name of the file.                                      |
| `filesize`                       | BIGINT       | Size of the file in bytes.                             |
| `download_url`                   | VARCHAR(255) | URL to download the file.                              |
| `preview_url`                    | VARCHAR(255) | URL for the file preview.                              |
| `has_custom_preview`             | BOOLEAN      | Indicates if there is a custom preview.                |
| `filetype`                       | VARCHAR(50)  | Type of the file (e.g., PDF, DOCX).                   |
| `filetype_description`           | VARCHAR(255) | Description of the file type.                           |
| `category`                       | VARCHAR(50)  | Category of the file.                                   |
| `small_preview`                  | VARCHAR(255) | URL for the small preview image.                        |
| `medium_preview`                 | VARCHAR(255) | URL for the medium preview image.                       |
| `large_preview`                  | VARCHAR(255) | URL for the large preview image.                        |
| `has_custom_thumbnail`           | BOOLEAN      | Indicates if there is a custom thumbnail.              |
| `md5`                            | VARCHAR(32)  | MD5 checksum of the file.                              |
| `suspected_damage`               | BOOLEAN      | Indicates if the file is suspected to be damaged.      |
| `gid`                            | VARCHAR(255) | Global identifier for the file.                        |
| `completed_size`                 | INT          | Size of the completed download.                         |
| `percentage`                     | INT          | Download completion percentage.                         |
| `custom_logo_url`                | VARCHAR(255) | URL for a custom logo associated with the file.        |
| `compressed_file_url`            | VARCHAR(255) | URL for the compressed file.                            |
| `compressed_file_status`          | VARCHAR(50)  | Status of the compressed file.                          |
| `compressed_file_format`         | VARCHAR(10)  | Format of the compressed file.                          |
| `torrent_status`                 | VARCHAR(50)  | Status of the torrent associated with the file.        |
| `torrent_url`                    | VARCHAR(255) | URL for the torrent file.                              |
| `fileserver`                     | VARCHAR(255) | Name of the file server.                               |
| `fileserver_url`                 | VARCHAR(255) | URL for the file server.                               |
| `fileserver_url_main`            | VARCHAR(255) | Main URL for the file server.                          |
| `footer_text`                    | VARCHAR(255) | Footer text associated with the file.                  |
| `antivirus_scan_status`          | VARCHAR(50)  | Status of the antivirus scan for the file.             |
| `file_status`                    | VARCHAR(255) | Status of the file (e.g., completed, pending).        |

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any improvements or bug fixes.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
