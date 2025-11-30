// === TRANSACTIONS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Store current URL to preserve month filter
    const currentUrl = window.location.pathname + window.location.search;
    
    // Range and Category Filters
    const rangeFilter = document.getElementById('rangeFilter');
    const categoryFilter = document.getElementById('categoryFilter');

    function applyFilters() {
        const range = rangeFilter.value;
        const category = categoryFilter.value;
        window.location.href = `${window.BASE_URL}/transactions/index/${range}/${category}`;
    }

    if (rangeFilter) rangeFilter.addEventListener('change', applyFilters);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);

    // Filter categories based on type for Add modal
    const addTypeSelect = document.getElementById('add_type');
    const addCategorySelect = document.getElementById('add_category_id');
    
    if (addTypeSelect && addCategorySelect) {
        console.log('Add modal filter initialized');
        
        addTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            console.log('Type changed to:', selectedType);
            const options = addCategorySelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                const optionType = option.getAttribute('data-type');
                console.log('Option:', option.text, 'Type:', optionType, 'Match:', optionType === selectedType);
                
                if (selectedType === '' || optionType === selectedType) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset category selection if current selection doesn't match type
            if (addCategorySelect.value) {
                const selectedOption = addCategorySelect.querySelector(`option[value="${addCategorySelect.value}"]`);
                if (selectedOption && selectedOption.getAttribute('data-type') !== selectedType) {
                    addCategorySelect.value = '';
                }
            }
        });
    } else {
        console.error('Add modal elements not found:', {addTypeSelect, addCategorySelect});
    }

    // Filter categories based on type for Edit modal
    const editTypeSelect = document.getElementById('edit_type');
    const editCategorySelect = document.getElementById('edit_category_id');
    
    if (editTypeSelect && editCategorySelect) {
        editTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            const options = editCategorySelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                const optionType = option.getAttribute('data-type');
                if (selectedType === '' || optionType === selectedType) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset category selection if current selection doesn't match type
            if (editCategorySelect.value) {
                const selectedOption = editCategorySelect.querySelector(`option[value="${editCategorySelect.value}"]`);
                if (selectedOption && selectedOption.getAttribute('data-type') !== selectedType) {
                    editCategorySelect.value = '';
                }
            }
        });
    }
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
                    // Close modal first
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTransactionModal'));
                    if (modal) modal.hide();
                    
                    // Show toast
                    setTimeout(() => {
                        SmartSpending.showToast('Transaction added successfully!', 'success');
                    }, 300);
                    
                    addTransactionForm.reset();
                    // Reload with current URL to preserve filters
                    setTimeout(() => window.location.href = currentUrl, 1500);
                } else {
                    SmartSpending.showToast(data.message || 'Failed to add transaction. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                SmartSpending.showToast('Failed to add transaction. Please try again.', 'error');
            });
        });
    }

    // Handle delete transaction
    document.querySelectorAll('.btn-delete-transaction').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const transactionId = this.dataset.id;
            
            SmartSpending.showConfirm(
                'Xóa Giao Dịch?',
                'Bạn có chắc chắn muốn xóa giao dịch này? Hành động này không thể hoàn tác.',
                () => {
                    fetch(`${BASE_URL}/transactions/api_delete/${transactionId}`, {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            SmartSpending.showToast('Transaction deleted successfully!', 'success');
                            // Reload with current URL to preserve filters
                            setTimeout(() => window.location.href = currentUrl, 1000);
                        } else {
                            SmartSpending.showToast(data.message || 'Failed to delete transaction.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        SmartSpending.showToast('Failed to delete transaction.', 'error');
                    });
                }
            );
        });
    });

    // Handle edit transaction - populate modal with data
    document.querySelectorAll('.btn-edit-transaction').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_transaction_id').value = this.dataset.id;
            document.getElementById('edit_category_id').value = this.dataset.category;
            document.getElementById('edit_amount').value = this.dataset.amount;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_date').value = this.dataset.date;
        });
    });

    // Handle edit transaction form submit
    const editTransactionForm = document.getElementById('editTransactionForm');
    if (editTransactionForm) {
        editTransactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const transactionId = data.id;
            
            if (!transactionId) {
                SmartSpending.showToast('Transaction ID is missing!', 'error');
                return;
            }
            
            // Debug: Log data being sent
            console.log('Sending edit data:', data);
            
            fetch(BASE_URL + '/transactions/api_update/' + transactionId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal first
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editTransactionModal'));
                    if (modal) modal.hide();
                    
                    // Show toast
                    setTimeout(() => {
                        SmartSpending.showToast('Transaction updated successfully!', 'success');
                    }, 300);
                    
                    // Reload with current URL to preserve filters
                    setTimeout(() => window.location.href = currentUrl, 1500);
                } else {
                    SmartSpending.showToast(data.message || 'Failed to update transaction.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                SmartSpending.showToast('Failed to update transaction.', 'error');
            });
        });
    }
});
