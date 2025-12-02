<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light p-4">

<div class="container">
    <h3 class="mb-4">Transactions</h3>

    <div class="card p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">From Date:</label>
                <input type="date" id="fromDate" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">To Date:</label>
                <input type="date" id="toDate" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="fetchTransactions()">Filter</button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-success" onclick="exportPDF()">Export to PDF</button>
    </div>

    <div class="card p-3">
        <table class="table table-bordered table-striped" id="transactionsTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Admin ID</th>
                    <th>Customer ID</th>
                    <th>Product List</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody id="transactionsBody">
                <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function fetchTransactions() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;

    const response = await fetch('../Misc/handleforms.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'get_transactions', fromDate, toDate })
    });


    const data = await response.json();
    const tbody = document.getElementById('transactionsBody');
    tbody.innerHTML = '';

    if (data.success && data.transactions.length > 0) {
        data.transactions.forEach(t => {
            const row = `<tr>
                <td>${t.id}</td>
                <td>${t.adminId}</td>
                <td>${t.customerId}</td>
                <td>${t.product_list}</td>
                <td>${t.date_added}</td>
            </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No transactions found</td></tr>`;
    }
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.text("Transactions Report", 14, 15);
    doc.autoTable({
        startY: 20,
        html: '#transactionsTable',
        theme: 'grid'
    });

    doc.save('transactions.pdf');
}

fetchTransactions();
</script>

</body>
</html>
