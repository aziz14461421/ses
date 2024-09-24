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

### `transfers`

| Column                         | Type         | Description                                    |
|--------------------------------|--------------|------------------------------------------------|
| uuid                           | CHAR(36)    | Unique identifier for the transfer (primary key) |
| id                             | VARCHAR(255) | Unique transfer ID                             |
| to_email                       | VARCHAR(255) | Email of the recipient                        |
| recipient_email                | VARCHAR(255) | Recipient's email address                      |
| ...                            | ...          | Additional fields related to the transfer     |

### `files`

| Column                         | Type         | Description                                    |
|--------------------------------|--------------|------------------------------------------------|
| uuid                           | VARCHAR(255) | Unique identifier for the file (primary key)  |
| file_id                        | VARCHAR(255) | Unique file ID                                 |
| transfer_id                    | CHAR(255)    | Reference to the transfer (foreign key)       |
| ...                            | ...          | Additional fields related to the file         |

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any improvements or bug fixes.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
