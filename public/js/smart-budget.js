/**
 * SmartSpending JARS Logic
 * Xử lý: Cấu hình tỷ lệ & Phân bổ thu nhập
 */
// Ensure global SmartSpending object exists without redeclaring
if (typeof window.SmartSpending === 'undefined') window.SmartSpending = {};
var SmartSpending = window.SmartSpending;

(function() {
    // --- PHẦN 1: CẤU HÌNH TỶ LỆ ---
    const keys = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
    const inputs = {}, displays = {};
    const saveBtn = document.getElementById('saveRatiosBtn');
    const totalEl = document.getElementById('totalPercent');

    // Init inputs
    keys.forEach(k => {
        inputs[k] = document.getElementById(k + 'Input');
        displays[k] = document.getElementById(k + 'Percent');
        if(inputs[k]) inputs[k].addEventListener('input', updateUI);
    });

    function updateUI() {
        let total = 0;
        keys.forEach(k => {
            let v = parseInt(inputs[k]?.value || 0);
            if(displays[k]) displays[k].innerText = v;
            total += v;
        });

        if(totalEl) {
            totalEl.innerText = total + '%';
            totalEl.className = total === 100 ? 'fw-bold text-success' : 'fw-bold text-danger';
            if(saveBtn) saveBtn.disabled = (total !== 100);
        }
    }

    if(saveBtn) {
        saveBtn.addEventListener('click', async () => {
            let vals = {};
            keys.forEach(k => vals[k] = parseInt(inputs[k].value));
            
            saveBtn.disabled = true; saveBtn.innerHTML = 'Đang lưu...';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const resRaw = await fetch(`${BASE_URL}/budgets/api_update_ratios`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
                    body: JSON.stringify(Object.assign({}, vals, { csrf_token: csrf }))
                });
                const res = await (async () => { try { return await resRaw.json(); } catch(e) { return { success:false, message:'Invalid JSON response' }; } })();

                if(res.success) {
                    alert('Đã lưu cấu hình!');
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + res.message);
                }
            } catch(e) { alert('Lỗi hệ thống'); }
            finally { saveBtn.disabled = false; saveBtn.innerHTML = 'Lưu Cấu Hình'; }
        });
    }

    // --- PHẦN 2: PHÂN BỔ THU NHẬP (VIP PRO) ---
    const jarNames = { nec: 'Thiết yếu', ffa: 'Tự do TC', ltss: 'Tiết kiệm', edu: 'Giáo dục', play: 'Hưởng thụ', give: 'Cho đi' };
    const jarColors = { nec: 'danger', ffa: 'warning', ltss: 'primary', edu: 'info', play: 'pink', give: 'success' };

    SmartSpending.previewIncome = function(input) {
        let rawValue = input.value.replace(/\D/g, '');
        if (!rawValue) {
            document.getElementById('incomePreviewList').innerHTML = '<div class="text-center text-muted py-3 small">Nhập số tiền để xem phân bổ</div>';
            return;
        }
        input.value = new Intl.NumberFormat('vi-VN').format(rawValue);
        
        let amount = parseInt(rawValue);
        let html = '';
        
        // Lấy settings từ biến Global PHP truyền xuống hoặc mặc định
        let currentSettings = window.JARS_SETTINGS || { nec_percent: 55, ffa_percent: 10, ltss_percent: 10, edu_percent: 10, play_percent: 10, give_percent: 5 };

        keys.forEach(key => {
            let percent = currentSettings[key + '_percent'] || 0;
            let jarAmount = amount * (percent / 100);
            let color = jarColors[key];
            
            // Style riêng cho màu hồng (Bootstrap ko có text-pink chuẩn)
            let colorClass = key === 'play' ? 'color: #d63384;' : `color: var(--bs-${color});`;
            let bgClass = key === 'play' ? 'background-color: #fce7f3;' : `background-color: var(--bs-${color}-bg-subtle);`;

            html += `
            <div class="col-6">
                <div class="p-2 rounded-3 border d-flex justify-content-between align-items-center" style="background: #fff;">
                    <div>
                        <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">${jarNames[key]} (${percent}%)</div>
                        <div class="fw-bold" style="${colorClass}">${new Intl.NumberFormat('vi-VN').format(jarAmount)} ₫</div>
                    </div>
                    <div class="rounded-circle p-1" style="${bgClass} width: 10px; height: 10px;"></div>
                </div>
            </div>`;
        });
        document.getElementById('incomePreviewList').innerHTML = html;
    };

    SmartSpending.submitIncome = async function() {
        const input = document.getElementById('incomeAmountInput');
        const amount = input.value.replace(/\D/g, '');
        const btn = document.getElementById('confirmDistributeBtn');
        
        if(!amount || amount <= 0) { alert('Vui lòng nhập số tiền!'); return; }
        
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang chia tiền...';
        
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resRaw = await fetch(`${BASE_URL}/budgets/api_distribute_income`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' 
                },
                body: JSON.stringify({ amount: amount, csrf_token: csrf })
            });
            const res = await (async () => { try { return await resRaw.json(); } catch(e) { return { success:false, message:'Invalid JSON response' }; } })();

            if(res.success) {
                alert('Đã phân bổ thành công!');
                window.location.reload();
            } else {
                alert('Lỗi: ' + res.message);
            }
        } catch(e) {
            console.error(e);
            alert('Lỗi kết nối server');
        } finally {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận Nạp';
        }
    };

    // Run once on load
    updateUI();

    // Listen for jar updates from transactions flow and refresh balances in UI
    window.addEventListener('smartbudget:updated', function(e) {
        try {
            var detail = e && e.detail ? e.detail : null;
            if (!detail || !detail.jar_updates) return;
            var jars = detail.jar_updates;
            // jars expected: { nec: number, ffa: number, ltss: number, edu: number, play: number, give: number }
            Object.keys(jars).forEach(function(code) {
                var el = document.querySelector('.jar-balance[data-jar="' + code + '"]');
                if (el) {
                    // format number as VN currency without symbol (we append symbol in markup)
                    try {
                        el.textContent = new Intl.NumberFormat('vi-VN').format(parseFloat(jars[code] || 0));
                    } catch (err) {
                        el.textContent = (jars[code] || 0);
                    }
                }
            });
        } catch (err) { console.warn('Error applying jar updates', err); }
    });
})();