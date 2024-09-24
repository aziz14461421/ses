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

## Database Schema Overview

This project uses a MySQL database with two main tables: `transfers` and `files`. 

For a complete overview of the database schema, including all fields and their descriptions, please refer to the [Database Schema Documentation](docs/database-schema.md).

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any improvements or bug fixes.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
