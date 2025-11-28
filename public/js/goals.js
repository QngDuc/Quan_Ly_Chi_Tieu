// === GOALS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Initialize goals bar chart if data exists
    const goalsBarCanvas = document.getElementById('goalsBar');
    if (goalsBarCanvas && typeof Chart !== 'undefined') {
        // Sample data - replace with actual data from PHP
        const goalsData = {
            labels: ['Mua nhà', 'Du lịch', 'Xe hơi', 'Học tập'],
            targets: [500000000, 50000000, 300000000, 20000000],
            saved: [250000000, 35000000, 150000000, 18000000]
        };

        new Chart(goalsBarCanvas, {
            type: 'bar',
            data: {
                labels: goalsData.labels,
                datasets: [
                    {
                        label: 'Mục tiêu',
                        data: goalsData.targets,
                        backgroundColor: '#E5E7EB',
                        borderRadius: 8
                    },
                    {
                        label: 'Đã tiết kiệm',
                        data: goalsData.saved,
                        backgroundColor: '#10B981',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' tr';
                                if (value >= 1000) return (value / 1000) + 'k';
                                return value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y || 0;
                                return `${label}: ${new Intl.NumberFormat('vi-VN').format(value)} ₫`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Handle goal form submission
    const goalForm = document.getElementById('goalForm');
    if (goalForm) {
        goalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    SmartSpending.showToast('Thêm mục tiêu thành công!', 'success');
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

    // Update goal progress
    document.querySelectorAll('.btn-update-goal').forEach(btn => {
        btn.addEventListener('click', function() {
            const goalId = this.dataset.id;
            const currentSaved = this.dataset.saved;
            
            const newAmount = prompt('Nhập số tiền đã tiết kiệm:', currentSaved);
            if (newAmount && !isNaN(newAmount)) {
                fetch(`${BASE_URL}/goals/update/${goalId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ saved: newAmount })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        SmartSpending.showToast('Cập nhật thành công!', 'success');
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
            }
        });
    });
});
