// === BUDGETS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Initialize budget pie chart if data exists
    const budgetPieCanvas = document.getElementById('budgetPie');
    if (budgetPieCanvas && typeof Chart !== 'undefined') {
        // Sample data - replace with actual data from PHP
        const budgetData = {
            labels: ['Ăn uống', 'Di chuyển', 'Mua sắm', 'Giải trí', 'Khác'],
            data: [3000000, 1500000, 2000000, 1000000, 500000]
        };

        const colors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'];

        new Chart(budgetPieCanvas, {
            type: 'doughnut',
            data: {
                labels: budgetData.labels,
                datasets: [{
                    data: budgetData.data,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return `${label}: ${new Intl.NumberFormat('vi-VN').format(value)} ₫`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Handle budget form submission
    const budgetForm = document.getElementById('budgetForm');
    if (budgetForm) {
        budgetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    SmartSpending.showToast('Cập nhật ngân sách thành công!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                SmartSpending.showToast('Có lỗi xảy ra', 'error');
            });
        });
    }
});
