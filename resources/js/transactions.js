// === TRANSACTIONS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Current filter state
    let currentFilters = {
        range: document.getElementById('rangeFilter')?.value || new Date().toISOString().slice(0, 7),
        category: document.getElementById('categoryFilter')?.value || 'all',
        page: 1
    };
    
    // Range and Category Filters - AJAX implementation
    const rangeFilter = document.getElementById('rangeFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const transactionsTableBody = document.querySelector('.transactions-table tbody');

    /**
     * Fetch transactions via AJAX
     */
    async function loadTransactions(showLoader = true) {
        if (showLoader && typeof SmartSpending !== 'undefined' && SmartSpending.showLoader) {
            SmartSpending.showLoader();
        }

        try {
            const params = new URLSearchParams({
                range: currentFilters.range,
                category: currentFilters.category,
                page: currentFilters.page
            });

            const response = await fetch(`${BASE_URL}/transactions/api_get_transactions?${params}`);
            const data = await response.json();

            if (data.success) {
                renderTransactions(data.data.transactions);
                renderPagination(data.data.pagination);
                
                // Update URL without reloading page
                const newUrl = `${BASE_URL}/transactions/index/${currentFilters.range}/${currentFilters.category}/${currentFilters.page}`;
                window.history.pushState({ filters: currentFilters }, '', newUrl);
            } else {
                SmartSpending.showToast(data.message || 'Không thể tải giao dịch', 'error');
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
            SmartSpending.showToast('Lỗi khi tải giao dịch', 'error');
        } finally {
            if (showLoader && typeof SmartSpending !== 'undefined' && SmartSpending.hideLoader) {
                SmartSpending.hideLoader();
            }
        }
    }

    /**
     * Render transactions in table
     */
    function renderTransactions(transactions) {
        if (!transactionsTableBody) return;

        if (!transactions || transactions.length === 0) {
            transactionsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                        Không có giao dịch nào
                    </td>
                </tr>
            `;
            return;
        }

        transactionsTableBody.innerHTML = transactions.map(t => `
            <tr data-transaction-id="${t.id}">
                <td class="transactions-date">${t.formatted_date}</td>
                <td>
                    <div class="transactions-category-wrapper">
                        <div class="transactions-category-icon ${t.type}">
                            <i class="fas ${t.type === 'income' ? 'fa-wallet' : 'fa-shopping-cart'}"></i>
                        </div>
                        <span class="transactions-category-name">${t.category_name}</span>
                    </div>
                </td>
                <td class="transactions-description">${t.description}</td>
                <td class="transactions-amount">${t.formatted_amount}</td>
                <td class="transactions-type-badge">
                    <span class="transactions-badge-${t.type}">
                        ${t.type === 'income' ? 'Thu nhập' : 'Chi tiêu'}
                    </span>
                </td>
                <td class="transactions-actions">
                    <button type="button" 
                            class="btn btn-sm btn-edit-transaction transactions-btn-edit" 
                            data-id="${t.id}"
                            data-amount="${Math.abs(t.amount)}"
                            data-type="${t.type}"
                            data-category="${t.category_id}"
                            data-date="${t.transaction_date}"
                            data-description="${t.description}"
                            data-bs-toggle="modal"
                            data-bs-target="#editTransactionModal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-delete-transaction transactions-btn-delete" 
                            data-id="${t.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        // Re-attach event listeners
        attachTransactionListeners();
    }

    /**
     * Render pagination controls
     */
    function renderPagination(pagination) {
        const paginationContainer = document.querySelector('.transactions-pagination');
        if (!paginationContainer) return;

        if (pagination.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = '<ul class="pagination justify-content-center mb-0">';
        
        // Previous button
        paginationHTML += `
            <li class="page-item ${!pagination.has_prev ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            paginationHTML += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        paginationHTML += '</ul>';
        paginationContainer.innerHTML = paginationHTML;

        // Attach pagination event listeners
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.parentElement.classList.contains('disabled')) return;
                
                const page = parseInt(this.dataset.page);
                if (page > 0 && page <= pagination.total_pages) {
                    currentFilters.page = page;
                    loadTransactions();
                    // Scroll to top of table
                    document.querySelector('.transactions-table-card')?.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    /**
     * Apply filters and reload transactions
     */
    function applyFilters() {
        currentFilters.range = rangeFilter?.value || currentFilters.range;
        currentFilters.category = categoryFilter?.value || currentFilters.category;
        currentFilters.page = 1; // Reset to first page
        loadTransactions();
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
    /**
     * Attach event listeners to transaction buttons
     */
    function attachTransactionListeners() {
        // Handle delete transaction
        document.querySelectorAll('.btn-delete-transaction').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const transactionId = this.dataset.id;
                
                SmartSpending.showConfirm(
                    'Xóa Giao Dịch?',
                    'Bạn có chắc chắn muốn xóa giao dịch này? Hành động này không thể hoàn tác.',
                    () => {
                        // Get CSRF token
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        fetch(`${BASE_URL}/transactions/api_delete/${transactionId}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-Token': csrfToken
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                SmartSpending.showToast('Xóa giao dịch thành công!', 'success');
                                // Reload current page
                                loadTransactions(false);
                            } else {
                                SmartSpending.showToast(data.message || 'Không thể xóa giao dịch', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            SmartSpending.showToast('Lỗi khi xóa giao dịch', 'error');
                        });
                    }
                );
            });
        });

        // Handle edit transaction button click
        document.querySelectorAll('.btn-edit-transaction').forEach(btn => {
            btn.addEventListener('click', function() {
                const editForm = document.getElementById('editTransactionForm');
                if (!editForm) return;

                const amountInput = document.getElementById('edit_amount');
                const rawAmount = this.dataset.amount;
                
                document.getElementById('edit_transaction_id').value = this.dataset.id;
                
                // Set amount and trigger input event to format it
                amountInput.value = rawAmount;
                amountInput.dataset.numericValue = rawAmount;
                amountInput.dispatchEvent(new Event('input'));
                
                document.getElementById('edit_type').value = this.dataset.type;
                document.getElementById('edit_date').value = this.dataset.date;
                document.getElementById('edit_description').value = this.dataset.description;
                
                // Set category and trigger type change to filter categories
                document.getElementById('edit_type').dispatchEvent(new Event('change'));
                document.getElementById('edit_category_id').value = this.dataset.category;
            });
        });
    }

    // Handle add transaction form
    const addTransactionForm = document.getElementById('addTransactionForm');
    if (addTransactionForm) {
        addTransactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Get numeric value from masked input
            const amountInput = this.querySelector('input[name="amount"]');
            const numericAmount = amountInput.dataset.numericValue ? parseInt(amountInput.dataset.numericValue, 10) : parseFloat(data.amount.replace(/[^\d]/g, ''));
            
            // Validate amount
            if (!numericAmount || numericAmount <= 0) {
                SmartSpending.showToast('Vui lòng nhập số tiền hợp lệ', 'error');
                return;
            }
            
            // Set the numeric amount
            data.amount = numericAmount;
            
            console.log('Sending data:', data); // Debug
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(BASE_URL + '/transactions/api_add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status); // Debug
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data); // Debug
                if (data.success) {
                    // Close modal first
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTransactionModal'));
                    if (modal) modal.hide();
                    
                    // Show toast
                    setTimeout(() => {
                        SmartSpending.showToast('Thêm giao dịch thành công!', 'success');
                    }, 300);
                    
                    addTransactionForm.reset();
                    // Reload transactions without page refresh
                    setTimeout(() => loadTransactions(false), 500);
                } else {
                    SmartSpending.showToast(data.message || 'Không thể thêm giao dịch', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                SmartSpending.showToast('Lỗi khi thêm giao dịch', 'error');
            });
        });
    }

    // Handle edit transaction form
    const editTransactionForm = document.getElementById('editTransactionForm');
    if (editTransactionForm) {
        editTransactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const transactionId = document.getElementById('edit_transaction_id').value;
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Get numeric value from masked input
            const amountInput = this.querySelector('input[name="amount"]');
            const numericAmount = amountInput.dataset.numericValue ? parseInt(amountInput.dataset.numericValue, 10) : parseFloat(data.amount.replace(/[^\d]/g, ''));
            
            // Validate amount
            if (!numericAmount || numericAmount <= 0) {
                SmartSpending.showToast('Vui lòng nhập số tiền hợp lệ', 'error');
                return;
            }
            
            // Set the numeric amount
            data.amount = numericAmount;
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(`${BASE_URL}/transactions/api_update/${transactionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editTransactionModal'));
                    if (modal) modal.hide();
                    
                    setTimeout(() => {
                        SmartSpending.showToast('Cập nhật giao dịch thành công!', 'success');
                    }, 300);
                    
                    // Reload transactions without page refresh
                    setTimeout(() => loadTransactions(false), 500);
                } else {
                    SmartSpending.showToast(data.message || 'Không thể cập nhật giao dịch', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                SmartSpending.showToast('Lỗi khi cập nhật giao dịch', 'error');
            });
        });
    }

    // Initial attachment of event listeners for server-rendered transactions
    attachTransactionListeners();

    // Set today's date when opening add transaction modal
    const addModal = document.getElementById('addTransactionModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.querySelector('#addTransactionForm input[name="transaction_date"]');
            if (dateInput && !dateInput.value) {
                dateInput.value = today;
            }
        });
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.filters) {
            currentFilters = event.state.filters;
            loadTransactions(false);
        }
    });
});
