// src/components/TransferItem.jsx

import React from 'react';
import axios from 'axios';

const TransferItem = ({ transfer, onRefresh }) => {
    const startDownload = async () => {
        try {
            await axios.post('http://192.168.1.17/test.php', { uuid: transfer.uuid });
            onRefresh();
        } catch (error) {
            console.error('Error starting download:', error);
        }
    };

    return (
        <li>
            <h3>{transfer.subject}</h3>
            <p>Status: {transfer.transfer_status}</p>
            <button onClick={startDownload} disabled={transfer.transfer_status === 'complete'}>
                Start Download
            </button>
        </li>
    );
};

export default TransferItem;

