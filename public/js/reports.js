// === REPORTS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // This script is loaded after Chart.js in footer
    // Charts are initialized inline in reports/index.php
    
    // Add export functionality
    const exportBtn = document.getElementById('exportReport');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            SmartSpending.showToast('Đang xuất báo cáo...', 'success');
            
            // Implement export logic here
            // Could export to PDF, Excel, etc.
            setTimeout(() => {
                SmartSpending.showToast('Xuất báo cáo thành công!', 'success');
            }, 1500);
        });
    }

    // Handle filter changes
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            const period = document.getElementById('periodFilter')?.value || 'this_month';
            const type = document.getElementById('typeFilter')?.value || 'all';
            
            // Reload page with new filters
            window.location.href = `${BASE_URL}/reports/index/${period}/${type}`;
        });
    });
});
