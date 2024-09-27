// src/components/TransferList.jsx

import React, { useEffect, useState } from 'react';
import axios from 'axios';
import TransferItem from './TransferItem';

const TransferList = () => {
    const [transfers, setTransfers] = useState([]);

    const fetchTransfers = async () => {
        try {
            const response = await axios.get('http://192.168.1.17/test.php');
            setTransfers(response.data);
        } catch (error) {
            console.error('Error fetching transfers:', error);
        }
    };

    useEffect(() => {
        fetchTransfers();
    }, []);

    return (
        <div>
            <h2>Transfers</h2>
            <ul>
                {transfers.map((transfer) => (
                    <TransferItem key={transfer.uuid} transfer={transfer} onRefresh={fetchTransfers} />
                ))}
            </ul>
        </div>
    );
};

export default TransferList;

