// Form validation and interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Add confirm dialog for delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Calculate total on segregation forms
    const numberInputs = document.querySelectorAll('input[type="number"]');
    if(numberInputs.length > 0) {
        numberInputs.forEach(input => {
            input.addEventListener('change', calculateTotal);
            input.addEventListener('keyup', calculateTotal);
        });
    }
    
    // Add total display to forms
    const form = document.querySelector('form');
    if(form && document.querySelector('.category-card')) {
        const totalDiv = document.createElement('div');
        totalDiv.className = 'alert alert-info';
        totalDiv.style.marginTop = '20px';
        totalDiv.innerHTML = '<strong>Total People: <span id="total-display">0</span></strong>';
        form.insertBefore(totalDiv, form.querySelector('button[type="submit"]'));
        calculateTotal();
    }
    
    // Real-time search filter
    const searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('keyup', filterTable);
    }
});

function calculateTotal() {
    let total = 0;
    const inputs = document.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        const value = parseInt(input.value) || 0;
        total += value;
    });
    
    const totalDisplay = document.getElementById('total-display');
    if(totalDisplay) {
        totalDisplay.textContent = total;
    }
    return total;
}

function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('dataTable');
    
    if(!table) return;
    
    const tr = table.getElementsByTagName('tr');
    
    for(let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for(let j = 0; j < td.length; j++) {
            if(td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if(txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// Form validation
function validateForm() {
    const requiredFields = document.querySelectorAll('[required]');
    for(let field of requiredFields) {
        if(!field.value.trim()) {
            alert('Please fill in all required fields');
            field.focus();
            return false;
        }
    }
    return true;
}

// Print function for reports
function printReport() {
    window.print();
}

// Export to CSV
function exportToCSV(data, filename) {
    const csv = data.map(row => 
        row.map(cell => 
            `"${cell.toString().replace(/"/g, '""')}"`
        ).join(',')
    ).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}