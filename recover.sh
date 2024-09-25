#!bin/bash

function removeDB(
	# drop the DB called ses.
	echo "Removing ses database"
 	mysql -u root -e "DROP DATABASE ses;"
        mysql -u root -e "SHOW DATABASES;"
)
function createDB(
	# create DB called ses.
        echo "Creating new database "ses""
        mysql -u root -e "CREATE DATABASE ses;"
	mysql -u root -e "SHOW DATABASES;"
)
function createDB_transfers(
 # create the db table
 echo "Creating new DB table ses.transfers"
mysql -u root -e "CREATE TABLE ses.transfers (uuid CHAR(36) PRIMARY KEY DEFAULT (UUID()),id VARCHAR(255) UNIQUE,
    to_email VARCHAR(255),
    recipient_email VARCHAR(255),
    recipient_download_link VARCHAR(255),
    recipient_delivered BOOLEAN,
    failed_recipients INT,
    from_email VARCHAR(255),
    subject TEXT,
    message TEXT,
    expire_date BIGINT,
    extended_expire_date BIGINT,
    sent_date BIGINT,
    status VARCHAR(255),
    track_id VARCHAR(255),
    url VARCHAR(255),
    size BIGINT,
    days INT,
    is_expired BOOLEAN,
    source VARCHAR(255),
    custom_field_label VARCHAR(255),
    custom_field_visible BOOLEAN,
    custom_field_render_type INT,
    custom_field_value VARCHAR(255),
    number_of_files INT,
    number_of_downloads INT,
    password_protected BOOLEAN,
    icon_color VARCHAR(7),
    icon_letter CHAR(1),
    ftp_host VARCHAR(255),
    ftp_corp_password_required BOOLEAN,
    udp_threshold INT,
    permanent BOOLEAN,
    max_days INT,
    allow_editing_expire_date BOOLEAN,
    block_downloads BOOLEAN,
    infected BOOLEAN,
    occupies_storage BOOLEAN,
    percentage VARCHAR(255),
    transfer_status VARCHAR(255))"
 # Check the creation of the DB table
 
  mysql -u root -e "DESCRIBE ses.transfers"
 ) 


function createDB_files(
 echo "Creating new DB table ses.files"
 mysql -u root -e "CREATE TABLE ses.files (
    uuid VARCHAR(255) PRIMARY KEY DEFAULT (UUID()),
    file_id VARCHAR(255),
    transfer_id CHAR(255),
    filename VARCHAR(255),
    filesize BIGINT,
    download_url VARCHAR(255),
    preview_url VARCHAR(255),
    has_custom_preview BOOLEAN,
    filetype VARCHAR(50),
    filetype_description VARCHAR(255),
    category VARCHAR(50),
    small_preview VARCHAR(255),
    medium_preview VARCHAR(255),
    large_preview VARCHAR(255),
    has_custom_thumbnail BOOLEAN,
    md5 VARCHAR(32),
    suspected_damage BOOLEAN,
    gid VARCHAR(255),
    completed_size INT,
    percentage INT,
    custom_logo_url VARCHAR(255),
    compressed_file_url VARCHAR(255),
    compressed_file_status VARCHAR(50),
    compressed_file_format VARCHAR(10),
    torrent_status VARCHAR(50),
    torrent_url VARCHAR(255),
    fileserver VARCHAR(255),
    fileserver_url VARCHAR(255),
    fileserver_url_main VARCHAR(255),
    footer_text VARCHAR(255),
    antivirus_scan_status VARCHAR(50),
    download_percentage INT,
    file_status VARCHAR(255),
    FOREIGN KEY (transfer_id) REFERENCES transfers(uuid)
);"
 mysql -u root -e "DESCRIBE ses.files"
)

removeDB
createDB
createDB_transfers
createDB_files
