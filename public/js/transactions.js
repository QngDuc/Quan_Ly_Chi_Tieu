// === TRANSACTIONS PAGE JS ===

document.addEventListener('DOMContentLoaded', function () {
    // Current filter state
    let currentFilters = {
        range: document.getElementById('rangeFilter')?.value || new Date().toISOString().slice(0, 7),
        category: document.getElementById('categoryFilter')?.value || 'all',
        sort: document.getElementById('sortFilter')?.value || 'newest',
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
                sort: currentFilters.sort,
                page: currentFilters.page
            });

            const response = await fetch(`${BASE_URL}/transactions/api_get_transactions?${params}`);
            const text = await response.text();
            let data = null;
            try { data = text ? JSON.parse(text) : null; } catch (e) { data = null; }

            const respData = data || { success: response.ok, message: text };

            if (respData.success) {
                renderTransactions(respData.data.transactions);
                renderPagination(respData.data.pagination);

                // Update URL without reloading page (append sort as query)
                const newUrl = `${BASE_URL}/transactions/index/${currentFilters.range}/${currentFilters.category}/${currentFilters.page}?sort=${encodeURIComponent(currentFilters.sort)}`;
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

    // Notify other parts of the app (and other tabs) that transactions changed
    function triggerTransactionChange() {
        try {
            // dispatch an in-page event
            window.dispatchEvent(new CustomEvent('transaction:created'));
            // ping localStorage to notify other tabs (storage event)
            const key = 'smartspending:transactions_updated';
            localStorage.setItem(key, Date.now().toString());
            // cleanup immediately
            localStorage.removeItem(key);
        } catch (e) {
            // ignore errors (e.g., private mode blocking localStorage)
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
                <td class="transactions-amount">${t.type === 'income' ? `<span class="amount amount-income">+ ${t.formatted_amount}</span>` : `<span class="amount amount-expense">- ${t.formatted_amount}</span>`}</td>
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
                <a class="page-link" href="javascript:void(0)" data-page="${pagination.current_page - 1}" role="button" tabindex="0">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers - show up to maxButtons buttons (centered on current page)
        const maxButtons = 6;
        const total = pagination.total_pages;
        let startPage = 1;
        let endPage = total;

        if (total > maxButtons) {
            const half = Math.floor(maxButtons / 2);
            startPage = pagination.current_page - half;
            endPage = pagination.current_page + (maxButtons - half - 1);

            if (startPage < 1) {
                startPage = 1;
                endPage = maxButtons;
            }
            if (endPage > total) {
                endPage = total;
                startPage = total - maxButtons + 1;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" data-page="${i}" role="button" tabindex="0">${i}</a>
                </li>
            `;
        }

        // If there are hidden pages before startPage, show first page and ellipsis
        if (startPage > 1) {
            paginationHTML = paginationHTML.replace('<ul', `<ul`);
            // prepend first page + ellipsis
                const prefix = `
                <li class="page-item ${1 === pagination.current_page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" data-page="1" role="button" tabindex="0">1</a></li>
                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
            `;
            // insert after previous button (which is at beginning)
            const insertionPoint = paginationHTML.indexOf('<li class="page-item');
            paginationHTML = paginationHTML.slice(0, paginationHTML.indexOf('</li>') + 5) + prefix + paginationHTML.slice(paginationHTML.indexOf('</li>') + 5);
        }

        // If hidden pages after endPage, append ellipsis and last page
        if (endPage < total) {
            const suffix = `
                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <li class="page-item ${total === pagination.current_page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" data-page="${total}" role="button" tabindex="0">${total}</a></li>
            `;
            // insert before the next button (which will be appended later)
            // we'll append suffix now and then next button will follow
            paginationHTML += suffix;
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" data-page="${pagination.current_page + 1}" role="button" tabindex="0">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        paginationHTML += '</ul>';
        paginationContainer.innerHTML = paginationHTML;

        // Attach pagination event listeners
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                // Prevent default navigation and stop propagation immediately
                e.preventDefault();
                e.stopPropagation();
                if (this.parentElement.classList.contains('disabled')) return;

                // Remove focus from the clicked link to avoid browser auto-scrolling it into view
                try { this.blur(); } catch (err) {}

                const page = parseInt(this.dataset.page);
                if (page > 0 && page <= pagination.total_pages) {
                    // Preserve current scroll position explicitly (defensive)
                    const scrollX = window.scrollX || window.pageXOffset;
                    const scrollY = window.scrollY || window.pageYOffset;

                    currentFilters.page = page;
                    loadTransactions();

                    // restore scroll position after a short delay to avoid layout jank
                    setTimeout(() => window.scrollTo(scrollX, scrollY), 40);
                }
            }, { passive: false });
        });
    }

    /**
     * Apply filters and reload transactions
     */
    function applyFilters() {
        currentFilters.range = rangeFilter?.value || currentFilters.range;
        currentFilters.category = categoryFilter?.value || currentFilters.category;
        currentFilters.sort = document.getElementById('sortFilter')?.value || currentFilters.sort;
        currentFilters.page = 1; // Reset to first page
        loadTransactions();
    }

    if (rangeFilter) rangeFilter.addEventListener('change', applyFilters);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
    const sortFilter = document.getElementById('sortFilter');
    if (sortFilter) sortFilter.addEventListener('change', applyFilters);

    // Filter categories based on type for Add modal
    const addTypeSelect = document.getElementById('add_type');
    const addCategorySelect = document.getElementById('add_category_id');

    if (addTypeSelect && addCategorySelect) {
        console.log('Add modal filter initialized');

        addTypeSelect.addEventListener('change', function () {
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
        console.error('Add modal elements not found:', { addTypeSelect, addCategorySelect });
    }

    // Filter categories based on type for Edit modal
    const editTypeSelect = document.getElementById('edit_type');
    const editCategorySelect = document.getElementById('edit_category_id');

    if (editTypeSelect && editCategorySelect) {
        editTypeSelect.addEventListener('change', function () {
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
        const tbody = document.querySelector('.transactions-table tbody');
        if (!tbody) return;

        // Avoid attaching multiple delegation handlers
        if (tbody.dataset.listenerAttached === '1') return;
        tbody.dataset.listenerAttached = '1';

        tbody.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.btn-delete-transaction');
            if (deleteBtn) {
                e.preventDefault();
                const transactionId = deleteBtn.dataset.id;

                SmartSpending.showConfirm(
                    'Xóa Giao Dịch?',
                    'Bạn có chắc chắn muốn xóa giao dịch này? Hành động này không thể hoàn tác.',
                    () => {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                        (async () => {
                            try {
                                const response = await fetch(`${BASE_URL}/transactions/api_delete/${transactionId}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-Token': csrfToken
                                    }
                                });

                                const text = await response.text();
                                let json = null;
                                try { json = text ? JSON.parse(text) : null; } catch (err) { json = null; }
                                const respData = json || { success: response.ok, message: text };

                                if (respData.success === true || respData.status === 'success' || response.ok) {
                                    SmartSpending.showToast(respData.message || 'Xóa giao dịch thành công!', 'success');
                                    if (respData.data && respData.data.jar_updates) {
                                        window.dispatchEvent(new CustomEvent('smartbudget:updated', { detail: { jar_updates: respData.data.jar_updates } }));
                                    }
                                                    loadTransactions(false);
                                                    triggerTransactionChange();
                                } else {
                                    SmartSpending.showToast(respData.message || 'Không thể xóa giao dịch', 'error');
                                }
                            } catch (error) {
                                console.error('Error deleting transaction:', error);
                                SmartSpending.showToast('Lỗi khi xóa giao dịch', 'error');
                            }
                        })();
                    }
                );

                return;
            }

            const editBtn = e.target.closest('.btn-edit-transaction');
            if (editBtn) {
                const editForm = document.getElementById('editTransactionForm');
                if (!editForm) return;

                const amountInput = document.getElementById('edit_amount');
                const rawAmount = editBtn.dataset.amount;

                document.getElementById('edit_transaction_id').value = editBtn.dataset.id;

                // Set amount and trigger input event to format it
                if (amountInput) {
                    amountInput.value = rawAmount;
                    amountInput.dataset.numericValue = rawAmount;
                    amountInput.dispatchEvent(new Event('input'));
                }

                const editTypeEl = document.getElementById('edit_type');
                const editDateEl = document.getElementById('edit_date');
                const editDescEl = document.getElementById('edit_description');
                const editCategoryEl = document.getElementById('edit_category_id');

                if (editTypeEl) editTypeEl.value = editBtn.dataset.type;
                if (editDateEl) editDateEl.value = editBtn.dataset.date;
                if (editDescEl) editDescEl.value = editBtn.dataset.description;

                // Set category and trigger type change to filter categories
                if (editTypeEl) editTypeEl.dispatchEvent(new Event('change'));
                if (editCategoryEl) editCategoryEl.value = editBtn.dataset.category;
            }
        });
    }

    // Handle add transaction form
    // File: public/js/transactions.js

    // Biến tạm để lưu dữ liệu đang nhập dở khi gặp cảnh báo
    let pendingTransactionData = null;

    // Ensure budget warning modal exists on page (create if missing)
    function ensureBudgetWarningModalExists() {
        if (document.getElementById('budgetWarningModal')) return;
        const tpl = `
        <div class="modal fade" id="budgetWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning bg-opacity-10 border-bottom-0">
                        <h5 class="modal-title text-warning fw-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo vượt ngân sách
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p id="budgetWarningMessage" class="mb-0 text-dark"></p>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-warning text-dark fw-bold px-4" id="confirmOverBudgetBtn">Tiếp tục thanh toán</button>
                    </div>
                </div>
            </div>
        </div>`;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = tpl;
        document.body.appendChild(wrapper.firstElementChild);

        // Attach click handler for confirm button (ensure it submits pending data)
        const confirmBtnNew = document.getElementById('confirmOverBudgetBtn');
        if (confirmBtnNew) {
            confirmBtnNew.addEventListener('click', function () {
                if (pendingTransactionData) {
                    submitTransaction(pendingTransactionData, true);
                }
            });
        }
    }

    // Xử lý sự kiện Submit Form
    const addTransactionForm = document.getElementById('addTransactionForm');
    if (addTransactionForm) {
        addTransactionForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Prevent duplicate submits
            if (this.dataset.sending === '1') {
                console.warn('Submit prevented: already sending');
                return;
            }
            this.dataset.sending = '1';

            // Disable submit button immediately
            const immediateSubmitBtn = this.querySelector('button[type=submit]');
            if (immediateSubmitBtn) immediateSubmitBtn.disabled = true;

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // Xử lý số tiền (giữ nguyên logic cũ của bạn)
            const amountInput = this.querySelector('input[name="amount"]');
            const numericAmount = amountInput.dataset.numericValue
                ? parseInt(amountInput.dataset.numericValue, 10)
                : parseFloat(data.amount.replace(/[^\d]/g, ''));

            if (!numericAmount || numericAmount <= 0) {
                SmartSpending.showToast('Vui lòng nhập số tiền hợp lệ', 'error');
                return;
            }
            data.amount = numericAmount;

            // Gọi hàm gửi dữ liệu (lần 1 - chưa confirm)
            submitTransaction(data, false).finally(() => {
                // clear sending flag in case submitTransaction didn't (safety)
                try { delete addTransactionForm.dataset.sending; } catch(e){}
                if (immediateSubmitBtn) immediateSubmitBtn.disabled = false;
            });
        });
    }

    // Hàm gửi request API
    async function submitTransaction(data, isConfirmed = false) {
        // Nếu là confirm, thêm cờ vào data
        if (isConfirmed) {
            data.confirmed = true;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch(BASE_URL + '/transactions/api_add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(data)
            });

                // Disable submit button to prevent duplicate requests (redundant safety)
                const submitBtn = document.querySelector('#addTransactionForm button[type=submit]');
                if (submitBtn) submitBtn.disabled = true;

                const text = await response.text();
                console.log('transactions/api_add status:', response.status, 'body:', text);
                let respData = null;
                try { respData = text ? JSON.parse(text) : null; } catch (e) { respData = null; }

            // --- XỬ LÝ LOGIC MỚI ---

            if (respData && respData.success) {
                // Trường hợp 1: Server trả về yêu cầu xác nhận (Warning)
                if (respData.data && respData.data.requires_confirmation) {
                    // Lưu lại data để dùng khi bấm nút "Tiếp tục"
                    pendingTransactionData = data;

                    // Ensure modal exists (budgets page creates it, other pages may not)
                    ensureBudgetWarningModalExists();

                    // Hiển thị Modal Cảnh báo
                    const msgEl = document.getElementById('budgetWarningMessage');
                    if (msgEl) msgEl.innerText = respData.data.message;
                    const warningModal = new bootstrap.Modal(document.getElementById('budgetWarningModal'));
                    warningModal.show();

                } else {
                    // Trường hợp 2: Thành công hoàn toàn
                    // Clear any pending data (user confirmed)
                    pendingTransactionData = null;

                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTransactionModal'));
                    if (modal) modal.hide();

                    // Đóng luôn modal cảnh báo nếu đang mở
                    const warningModalEl = document.getElementById('budgetWarningModal');
                    const warningModal = bootstrap.Modal.getInstance(warningModalEl);
                    if (warningModal) warningModal.hide();

                    SmartSpending.showToast('Thêm giao dịch thành công!', 'success');
                    addTransactionForm.reset();
                    if (respData.data && respData.data.jar_updates) {
                        window.dispatchEvent(new CustomEvent('smartbudget:updated', { detail: { jar_updates: respData.data.jar_updates } }));
                    }
                    setTimeout(() => loadTransactions(false), 500);
                    triggerTransactionChange();
                }
            } else {
                // Trường hợp 3: Lỗi (Ví dụ: Không đủ số dư tổng hoặc validation)
                let errMsg = respData?.message || 'Có lỗi xảy ra';
                // If server returned validation errors in data, format them
                if (respData && respData.data && typeof respData.data === 'object') {
                    const details = respData.data;
                    if (!Array.isArray(details)) {
                        const parts = [];
                        for (const k in details) {
                            if (details.hasOwnProperty(k)) parts.push(k + ': ' + details[k]);
                        }
                        if (parts.length) errMsg += ' - ' + parts.join('; ');
                    }
                }
                console.warn('Transaction add failed:', response.status, respData);
                SmartSpending.showToast(errMsg, 'error');
            }

            // Re-enable submit button
            if (submitBtn) submitBtn.disabled = false;
            try { delete addTransactionForm.dataset.sending; } catch(e){}

        } catch (error) {
            console.error('Error:', error);
            SmartSpending.showToast('Lỗi kết nối', 'error');
        }
    }

    // Sự kiện nút "Tiếp tục thanh toán" trong Modal Cảnh báo
    const confirmBtn = document.getElementById('confirmOverBudgetBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (pendingTransactionData) {
                // Gửi lại request với cờ confirmed = true
                submitTransaction(pendingTransactionData, true);
            }
        });
    }

    // Handle edit transaction form
    const editTransactionForm = document.getElementById('editTransactionForm');
    if (editTransactionForm) {
        editTransactionForm.addEventListener('submit', function (e) {
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

            (async () => {
                try {
                    const response = await fetch(`${BASE_URL}/transactions/api_update/${transactionId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify(data)
                    });

                    console.log('Update response status:', response.status);

                    const text = await response.text();
                    let json = null;
                    try { json = text ? JSON.parse(text) : null; } catch (e) { json = null; }
                    const respData = json || { success: response.ok, message: text };

                    if (respData.success === true || respData.status === 'success' || response.ok) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editTransactionModal'));
                        if (modal) modal.hide();

                        setTimeout(() => {
                            SmartSpending.showToast(respData.message || 'Cập nhật giao dịch thành công!', 'success');
                        }, 300);

                        if (respData.data && respData.data.jar_updates) {
                            window.dispatchEvent(new CustomEvent('smartbudget:updated', { detail: { jar_updates: respData.data.jar_updates } }));
                        }

                        setTimeout(() => loadTransactions(false), 500);
                        triggerTransactionChange();
                    } else {
                        SmartSpending.showToast(respData.message || 'Không thể cập nhật giao dịch', 'error');
                    }
                } catch (error) {
                    console.error('Error updating transaction:', error);
                    SmartSpending.showToast('Lỗi khi cập nhật giao dịch', 'error');
                }
            })();
        });
    }

    // Initial attachment of event listeners for server-rendered transactions
    attachTransactionListeners();

    // Set today's date when opening add transaction modal
    const addModal = document.getElementById('addTransactionModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function () {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.querySelector('#addTransactionForm input[name="transaction_date"]');
            if (dateInput && !dateInput.value) {
                dateInput.value = today;
            }
        });
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function (event) {
        if (event.state && event.state.filters) {
            currentFilters = event.state.filters;
            loadTransactions(false);
        }
    });
});
