// === TRANSACTIONS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Handle add transaction form
    const addTransactionForm = document.getElementById('addTransactionForm');
    if (addTransactionForm) {
        addTransactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            fetch(BASE_URL + '/transactions/api_add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thêm giao dịch thành công!');
                    addTransactionForm.reset();
                    window.location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi kết nối server');
            });
        });
    }

    // Handle delete transaction
    document.querySelectorAll('.btn-delete-transaction').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const transactionId = this.dataset.id;
            
            if (confirm('Bạn có chắc muốn xóa giao dịch này?')) {
                fetch(`${BASE_URL}/transactions/api_delete/${transactionId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Xóa giao dịch thành công!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra');
                });
            }
        });
    });

    // Update category filter based on transaction type
    const typeSelect = document.getElementById('transactionType');
    const categorySelect = document.getElementById('transactionCategory');
    
    if (typeSelect && categorySelect) {
        typeSelect.addEventListener('change', function() {
            const type = this.value;
            // Filter categories based on type
            // This would need to be implemented based on your data structure
        });
    }
});
