<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

    :root {
        --primary: #ff6b00;
        --bg: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background: var(--bg); 
        margin: 0; 
        overflow-x: hidden; 
    }

    .navbar { 
        background: #ff9800; 
        padding: 0.5rem 2rem; 
        display: flex; 
        align-items: center; 
        height: 60px; 
    }

    .btn-back-main { 
        text-decoration: none; 
        color: #fff; 
        font-weight: 700; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }

    .container { 
        max-width: 1600px; 
        margin: 0 auto; 
        padding: 2rem; 
    }

    /* Grid System */
    .map-grid-container { 
        background: #fff; 
        padding: 2rem; 
        border-radius: 24px; 
        border: 1px solid var(--border); 
        box-shadow: var(--shadow-md); 
    }

    .map-grid { 
        display: grid; 
        grid-template-columns: repeat(7, 1fr); 
        gap: 1.5rem; 
    }

    .seat-box {
        padding: 1.2rem; 
        border-radius: 14px; 
        text-align: center; 
        border: 1px solid var(--border); 
        background: #f8fafc; 
        cursor: pointer;
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        transition: all 0.25s;
    }

    .seat-box:hover { transform: translateY(-5px); border-color: var(--primary); background: #fff; }

    /* Status Colors */
    .Occupied { background: #ecfdf5; border-bottom: 5px solid #10b981; color: #065f46; }
    .Vacant { background: #ffffff; border: 1px dashed #cbd5e1; color: #94a3b8; }
    .Repair { background: #fff1f2; border-bottom: 5px solid #ef4444; color: #9f1239; }

    /* Modal Design */
    .modal-overlay { 
        display: none; 
        position: fixed; 
        inset: 0; 
        background: rgba(15, 23, 42, 0.65); 
        backdrop-filter: blur(8px); 
        z-index: 1000; 
    }
    .modal-content { 
        background: #fff; 
        width: 400px; 
        padding: 2rem; 
        border-radius: 20px; 
        position: absolute; 
        top: 50%; left: 50%; transform: translate(-50%, -50%); 
    }
    
    .modal-content input, .modal-content textarea {
        width: 100%; padding: 0.8rem; margin-bottom: 1rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box;
    }

    .btn-save { background: var(--primary); color: white; border: none; padding: 1rem; width: 100%; border-radius: 10px; font-weight: 800; cursor: pointer; }
</style>

<script>
    function openEdit(data) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = data.id;
        document.getElementById('seatTitle').innerText = "Edit: " + data.cubicle_no;
        document.getElementById('seatDept').value = data.department;
        document.getElementById('seatCubicle').value = data.cubicle_no;
        document.getElementById('seatHost').value = data.hostname || '';
        document.getElementById('seatCamp').value = data.campaign || '';
    }

    function closeModal() { 
        document.getElementById('modalOverlay').style.display = 'none'; 
    }

    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            if(host.includes(input)) {
                seats[i].style.opacity = "1";
                seats[i].style.pointerEvents = "auto";
            } else {
                seats[i].style.opacity = "0.1";
                seats[i].style.pointerEvents = "none";
            }
        }
    }
</script>