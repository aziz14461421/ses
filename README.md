# Transfers Management System

This project is a Transfers Management System built using a combination of Bash scripts and PHP to manage file transfers. It consists of a database schema for storing transfer information and a script to insert data into the database from JSON files.

## Features

- **Database Management**: Automates the process of creating and removing a MySQL database and its tables.
- **File Handling**: Inserts file transfer information into the database using JSON data.
- **API Interaction**: Retrieves and processes data from an external API.
- **Download Management**: Manages file downloads using the `aria2c` download manager and updates the database with download statuses.

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

### Download Files Script

The `download_files.php` script is responsible for managing file downloads using the `aria2c` download manager and updating the MySQL database with the download status.

#### Features

- **JSON-RPC Communication**: The script communicates with the `aria2c` RPC server to monitor and control download tasks.
- **Database Updates**: It updates the download status of files in the `files` table and the overall transfer status in the `transfers` table.
- **Progress Monitoring**: The script checks the status of ongoing transfers and files, displaying real-time progress in the terminal.

#### Dependencies

- PHP with PDO extension for MySQL.
- `aria2c` download manager installed and running.

#### Configuration

Before running the script, ensure that the following variables are correctly set:


- **Aria2c RPC Endpoint**:

    ```php
    $rpcUrl = 'http://localhost:6800/jsonrpc'; // URL for aria2c RPC
    ```

- **Download Directory**:

    ```php
    $downloadDir = __DIR__ . '/downloaded/'; // Directory for downloaded files
    ```

#### Usage

To run the script, execute it from the command line:

```bash
php download_files.php
```

The script will continuously check for ongoing transfers in the database, fetch their statuses from `aria2c`, and update the database accordingly. The script clears the terminal after each iteration to display the latest status information.

### Important Notes

- The script runs in an infinite loop and checks the download status every second. Ensure to stop it manually when needed (e.g., using `CTRL+C`).
- Make sure the `downloaded` directory exists before running the script, as files will be saved there.

## File Structure

- `recover.sh`: Shell script for managing the MySQL database and tables.
- `insert_data.php`: PHP script for inserting data from JSON files into the database.
- `download_files.php`: PHP script for managing downloads and updating the database.
- `data/`: Directory containing JSON files for processing.

## Database Schema Overview

This project uses a MySQL database with two main tables: `transfers` and `files`.

For a complete overview of the database schema, including all fields and their descriptions, please refer to the [Database Schema Documentation](docs/database-schema.md).

## Client-Side Application

The client-side application is a simple web interface to display and manage transfers. It consists of HTML, CSS, and JavaScript files.

### File Structure

- `client/index.html`: The main HTML file for the client-side application.
- `client/script.js`: JavaScript file for handling dynamic content and interactions.
- `client/style.css`: CSS file for styling the client-side application.

### Features

- **Dynamic Table**: Displays a table of transfers with columns for title, subject, files, source, downloads, status, progress, and actions.
- **Progress Bar**: Shows the download progress for each transfer.
- **Action Buttons**: Allows users to start, pause, and resume downloads.

### Usage

1. Open `client/index.html` in a web browser.
2. The table will be populated with transfer data fetched from the server.
3. Use the action buttons to manage downloads.

### Important Notes

- The client-side application communicates with the server using the `download_manager.php` script.
- Ensure that the server is running and accessible at the specified URL.

## Server-Side Scripts

### `download_manager.php`

This script handles various actions related to download management, such as starting, pausing, and resuming downloads. It also fetches and updates the status of transfers.

#### Features

- **CORS Headers**: Allows cross-origin requests from any domain.
- **Action Handling**: Supports actions like starting, pausing, and resuming downloads.
- **Status Updates**: Fetches and updates the status of transfers and files.

#### Usage

The script handles different HTTP methods (GET, POST, PUT) to perform various actions:

- **GET**: Fetches the status of a specific transfer or all transfers.
- **POST**: Starts, pauses, or resumes downloads based on the action specified in the request payload.
- **PUT**: Periodically checks the status of all files under a specific transfer and updates the database.

#### Example Request

To start a download for a specific transfer:

```bash
curl -X POST http://192.168.1.28:7000/download_manager.php -H "Content-Type: application/json" -d '{"action":"start", "uuid":"transfer_uuid"}'
```

To fetch the status of a specific transfer:

```bash
curl http://192.168.1.28:7000/download_manager.php?uuid=transfer_uuid
```

### Important Notes

- Ensure that the `aria2c` RPC server is running and accessible at the specified URL.
- The script updates the database with the download status and progress.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any improvements or bug fixes.
