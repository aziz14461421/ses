// Function to fetch all transfers and populate the table
async function fetchAllTransfers() {
    try {
        const response = await fetch('http://192.168.1.28:7000/download_manager.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Error fetching transfers');
        }

        const transfers = await response.json();
        populateTable(transfers);
    } catch (error) {
        console.error('Error:', error);
    }
}

function populateTable(transfers) {
    const tableBody = document.querySelector('.table-custom tbody');
    tableBody.innerHTML = ''; // Clear existing rows

    transfers.forEach((transfer) => {
        const progressPercentage = parseFloat(transfer.percentage || 0).toFixed(2);

        const row = `
            <tr>
                <td>${transfer.custom_field_value}</td>
                <td>${transfer.subject}</td>
                <td>${transfer.number_of_files}</td>
                <td>${transfer.source}</td>
                <td>${transfer.number_of_downloads}</td>
                <td id="status-${transfer.uuid}">${transfer.transfer_status || 'Pending'}</td>
                <td>
                    <div class="progress-bar">
                        <div id="progress-${transfer.uuid}" class="progress" style="width: ${progressPercentage}%;"
                            >${progressPercentage == 0 ? "" : progressPercentage + "%"}</div>
                    </div>
                </td>
                <td>
                    <button id="download-btn-${transfer.uuid}"
                        onclick="initiateTransferDownload('${transfer.uuid}')">
                        Download
                    </button>
                    <button id="pause-btn"
                        onclick="pauseTransfer('${transfer.uuid}')">
                        Pause
                    </button>
                    <button id="resume-btn"
                        onclick="resumeTransfer('${transfer.uuid}')">
                        Resume
                    </button>
                </td>
            </tr>`;
        tableBody.insertAdjacentHTML('beforeend', row);

        // Update the button states based on the current status
        updateButtonState(transfer.uuid);

        if (localStorage.getItem(`downloading-${transfer.uuid}`) === 'true') {
            startPeriodicStatusUpdate(transfer.uuid);
        }
    });
}


// Function to initiate the download for a specific transfer
async function initiateTransferDownload(uuid) {
    try {
        const button = document.getElementById(`download-btn-${uuid}`);
        button.disabled = true;
        button.textContent = 'Downloading...'; // Update button text

        // Store the download status in localStorage
        localStorage.setItem(`downloading-${uuid}`, 'true');

        const response = await fetch('http://192.168.1.28:7000/download_manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({action:"start", uuid: uuid })
        });

        if (!response.ok) {
            throw new Error('Error initiating download');
        }

        const result = await response.json();
        console.log('Download initiated:', result);

        // Start periodic status update after initiating download
        startPeriodicStatusUpdate(uuid);
    } catch (error) {
        console.error('Error:', error);
    }
}

// Function to pause the transfer
async function pauseTransfer(uuid) {
    try {
        const response = await fetch('http://192.168.1.28:7000/download_manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'pause', uuid: uuid })
        });

        if (!response.ok) {
            throw new Error('Error pausing transfer');
        }

        localStorage.setItem(`downloading-${uuid}`, 'paused');
        console.log('Transfer paused:', uuid);
        updateButtonState(uuid);
    } catch (error) {
        console.error('Error:', error);
    }
}

// Function to resume the transfer
async function resumeTransfer(uuid) {
    try {
        const response = await fetch('http://192.168.1.28:7000/download_manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'resume', uuid: uuid })
        });

        if (!response.ok) {
            throw new Error('Error resuming transfer');
        }

        localStorage.setItem(`downloading-${uuid}`, 'true');
        console.log('Transfer resumed:', uuid);
        updateButtonState(uuid);
        startPeriodicStatusUpdate(uuid); // Restart the periodic status update
    } catch (error) {
        console.error('Error:', error);
    }
}

// Function to update button states based on transfer status
function updateButtonState(uuid) {
    const pauseButton = document.getElementById(`pause-btn`);
    const resumeButton = document.getElementById(`resume-btn`);
    const downloadButton = document.getElementById(`download-btn-${uuid}`);

    if (localStorage.getItem(`downloading-${uuid}`) === 'paused') {
        pauseButton.disabled = true;
        resumeButton.disabled = false;
        downloadButton.disabled = true; // Disable download button while paused
    } else {
        pauseButton.disabled = false;
        resumeButton.disabled = true;
        downloadButton.disabled = localStorage.getItem(`downloading-${uuid}`) === 'true'; // Disable if downloading
    }
}

// Function to periodically check the transfer status and update the progress bar
function startPeriodicStatusUpdate(uuid) {
    const intervalId = setInterval(async () => {
        try {
            const response = await fetch('http://192.168.1.28:7000/download_manager.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ uuid: uuid })
            });

            if (!response.ok) {
                throw new Error('Error updating transfer status');
            }

            const result = await response.json();

            // Update the status and progress in the table
            const statusElement = document.getElementById(`status-${uuid}`);
            const progressElement = document.getElementById(`progress-${uuid}`);
            statusElement.textContent = 'In Progress';
            const progressValue = parseFloat(result.progress || 0).toFixed(0);
            progressElement.style.width = `${progressValue}%`;
            progressElement.textContent = `${progressValue}%`;

            // If the transfer is complete, stop the periodic updates
            if (result.progress == 100 || result.status === 'completed') {
                clearInterval(intervalId);
                const statusElement = document.getElementById(`status-${uuid}`);
                statusElement.textContent = 'Complete';
                localStorage.removeItem(`downloading-${uuid}`); // Remove from localStorage when done
                const button = document.getElementById(`download-btn-${uuid}`);
                button.textContent = 'Download'; // Reset button text
                console.log('Transfer complete. Stopping periodic updates.');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }, 1000);
}

// Fetch all transfers when the page loads
window.onload = fetchAllTransfers;

