</div> <div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="seatTitle">Edit Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label>Hostname</label>
            <input type="text" name="hostname" id="seatHost" style="width:100%; padding:10px; margin-bottom:15px;">
            <button type="submit" name="update_seat" style="background:var(--primary); color:#fff; width:100%; padding:10px; border:none; border-radius:10px;">SAVE</button>
            <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; margin-top:10px;">Cancel</button>
        </form>
    </div>
</div>

<script>
    function openEdit(data) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = data.id;
        document.getElementById('seatHost').value = data.hostname || '';
    }
    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let seat of seats) {
            let host = seat.getAttribute('data-hostname');
            seat.style.opacity = host.includes(input) ? "1" : "0.1";
        }
    }
</script>
</body>
</html>