/**
 * OJTBox | INVENTORY MODULE LOGIC
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initial Load
    loadInventory();

    // Event Listener for Search
    const searchInput = document.getElementById('inventorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterTable(e.target.value.toLowerCase());
        });
    }
});

/**
 * Fetches data from PHP and renders the table
 */
async function loadInventory() {
    const container = document.getElementById('inventory-data-wrapper');
    
    try {
        // Ensure this PHP file exists in your directory
        const response = await fetch('fetch_inventory.php');
        
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        if (data.error) throw new Error(data.error);

        if (data.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding: 40px; color: #666;">
                    <i class="fa-solid fa-box-open fa-3x"></i>
                    <p>No inventory items found in the database.</p>
                </div>`;
            return;
        }

        renderTable(data);

    } catch (err) {
        console.error("Inventory Error:", err);
        container.innerHTML = `
            <div style="color:#d9534f; text-align:center; padding: 30px; border: 1px solid #ebccd1; border-radius: 8px; background: #f2dede;">
                <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                <p><strong>System Error:</strong> Could not connect to inventory database.</p>
                <small>${err.message}</small>
            </div>`;
    }
}

/**
 * Builds the HTML Table structure
 */
function renderTable(data) {
    const container = document.getElementById('inventory-data-wrapper');
    
    let tableHTML = `
        <table class="data-table" id="mainInventoryTable">
            <thead>
                <tr>
                    <th>Asset Name</th>
                    <th>Host Name</th>
                    <th>Serial Number</th>
                    <th>Device Type</th>
                    <th>Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${data.map(item => `
                    <tr>
                        <td class="asset-cell"><strong>${item.asset_name}</strong></td>
                        <td class="host-cell">${item.host_name}</td>
                        <td class="serial-cell"><code>${item.serial_num}</code></td>
                        <td>${item.device_type}</td>
                        <td><span class="status-badge ${item.status.toLowerCase()}">${item.status}</span></td>
                        <td style="text-align:center;">
                            <button class="edit-btn" onclick="editItem(${item.id})" title="Edit Asset">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="delete-btn" onclick="deleteItem(${item.id})" title="Delete Asset" style="color: #ff4d4d; margin-left:10px; border:none; background:none; cursor:pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHTML;
}

/**
 * Simple Front-end Search Filter
 */
function filterTable(query) {
    const rows = document.querySelectorAll('#mainInventoryTable tbody tr');
    
    rows.forEach(row => {
        const hostName = row.querySelector('.host-cell').textContent.toLowerCase();
        const serialNum = row.querySelector('.serial-cell').textContent.toLowerCase();
        const assetName = row.querySelector('.asset-cell').textContent.toLowerCase();

        if (hostName.includes(query) || serialNum.includes(query) || assetName.includes(query)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

// Placeholder functions for future Expandability
function editItem(id) {
    console.log("Editing item ID:", id);
    alert("Edit feature coming soon for ID: " + id);
}

function deleteItem(id) {
    if(confirm("Are you sure you want to delete this asset?")) {
        console.log("Deleting item ID:", id);
    }
}

// Add dispose remarks and time status
function toggleDisposeFields() {
    const status = document.getElementById('status').value;
    const disposeDiv = document.getElementById('disposeFields');
    disposeDiv.style.display = (status === 'Dispose') ? 'block' : 'none';
}

// Update the Edit Function to populate new fields
function editAsset(item) {
    document.getElementById('assetId').value = item.id;
    document.getElementById('assetName').value = item.asset_name;
    document.getElementById('hostName').value = item.host_name;
    document.getElementById('serialNum').value = item.serial_num;
    document.getElementById('deviceType').value = item.device_type;
    document.getElementById('status').value = item.status;
    
    // Set Dispose specific fields
    document.getElementById('disposeDate').value = item.dispose_date || '';
    document.getElementById('disposeTime').value = item.dispose_time || '';
    document.getElementById('remarks').value = item.remarks || '';
    
    toggleDisposeFields(); // Show fields if status is Dispose
    document.getElementById('modalTitle').innerText = "Edit Asset";
    document.getElementById('assetModal').style.display = 'block';
}

// Update the Save Function
assetForm.onsubmit = async (e) => {
    e.preventDefault();
    const payload = {
        action: document.getElementById('assetId').value ? 'update' : 'create',
        id: document.getElementById('assetId').value,
        asset_name: document.getElementById('assetName').value,
        host_name: document.getElementById('hostName').value,
        serial_num: document.getElementById('serialNum').value,
        device_type: document.getElementById('deviceType').value,
        status: document.getElementById('status').value,
        // New Fields
        dispose_date: document.getElementById('disposeDate').value,
        dispose_time: document.getElementById('disposeTime').value,
        remarks: document.getElementById('remarks').value
    };

    const response = await fetch('inventory_CRUD.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.success) location.reload();
    else alert(result.message);
};