/**
 * OJTBox | MAIN LOGIC CONTROLLER
 */

// --- ELEMENTS ---
const mainGrid = document.getElementById('main-grid');
const backBtn = document.getElementById('back-btn');
const allViews = document.querySelectorAll('.sub-view'); 

// --- VIEW NAVIGATION HELPERS ---

function showView(viewName) {
    mainGrid.style.display = 'none';
    allViews.forEach(view => {
        view.style.display = 'none';
    });
    
    if (viewName === 'main') {
        mainGrid.style.display = 'grid';
        backBtn.style.display = 'none';
    } else {
        // Targets IDs like "machine movement-view"
        const targetView = document.getElementById(`${viewName}-view`);
        if (targetView) {
            targetView.style.display = 'block';
            backBtn.style.display = 'inline-block';
        }
    }
}

// Back button event
backBtn.addEventListener('click', () => showView('main'));

// ==========================================
// SECTION: DATA FETCHING (Machine Movement)
// ==========================================

async function renderMachineMoveTable() {
    // Note: We escape the space in the ID selector for the specific view
    const container = document.querySelector('#machine\\ movement-view .view-content');
    
    if (!container) return;

    // Show a loading state
    container.innerHTML = `
        <div style="text-align:center; padding: 20px;">
            <i class="fa-solid fa-spinner fa-spin"></i> Fetching movement logs...
        </div>`;

    try {
        // Calling your updated PHP file
        const response = await fetch('fetch_machine_movement.php'); 
        const dbData = await response.json();

        if (dbData.error) {
            throw new Error(dbData.error);
        }

        if (dbData.length === 0) {
            container.innerHTML = "<p>No machine movement records found in database.</p>";
            return;
        }

        // Build the table dynamically using the new PHP keys: moved_by and date_moved
        let tableHTML = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Machine/Item</th>
                        <th>Moved By</th>
                        <th>Date of Movement</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${dbData.map(row => `
                        <tr>
                            <td>${row.id}</td>
                            <td>${row.item_name}</td>
                            <td>${row.moved_by}</td>
                            <td>${row.date_moved}</td>
                            <td><span class="status-badge ${row.status.toLowerCase()}">${row.status}</span></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        container.innerHTML = tableHTML;

    } catch (error) {
        console.error("Database Error:", error);
        container.innerHTML = `
            <p style="color:red; text-align:center;">
                <i class="fa-solid fa-circle-exclamation"></i> Error: Could not connect to database.
            </p>`;
    }
}

// ==========================================
// SECTION: NAVIGATION LISTENERS
// ==========================================

// 1. MACHINE MOVEMENT - Matches id="btn-machine movement"
document.getElementById('btn-machine movement').addEventListener('click', () => {
    showView('machine movement');
    renderMachineMoveTable(); 
});

// 2. PROD MAP
document.getElementById('btn-prodmap').addEventListener('click', () => showView('prodmap'));

// 3. WEB BASE LINE
document.getElementById('btn-baseline').addEventListener('click', () => showView('baseline'));

// 4. HDN
document.getElementById('btn-hdn').addEventListener('click', () => showView('hdn'));

// 5. INVENTORY
document.getElementById('btn-inventory').addEventListener('click', () => showView('inventory'));

// 6. TASK BOX
document.getElementById('btn-taskbox').addEventListener('click', () => showView('taskbox'));



// ==========================================
// SECTION: THEME TOGGLE LOGIC
// ==========================================
const themeToggle = document.getElementById('theme-toggle');
const themeIcon = themeToggle.querySelector('.theme-icon');
const body = document.body;

// 1. Check for saved theme on page load
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark') {
    body.classList.add('dark-mode');
    themeIcon.classList.replace('fa-moon', 'fa-sun');
}

// 2. Toggle theme on click
themeToggle.addEventListener('click', () => {
    // 1. Remove the class first (in case it's already there)
    themeIcon.classList.remove('rotating');
    
    // 2. Trigger a "reflow" (this is the trick to restart CSS animations)
    void themeIcon.offsetWidth; 
    
    // 3. Add the class back
    themeIcon.classList.add('rotating');

    // 4. Handle the theme swap
    body.classList.toggle('dark-mode');
    
    if (body.classList.contains('dark-mode')) {
        themeIcon.classList.replace('fa-moon', 'fa-sun');
        localStorage.setItem('theme', 'dark');
    } else {
        themeIcon.classList.replace('fa-sun', 'fa-moon');
        localStorage.setItem('theme', 'light');
    }
});