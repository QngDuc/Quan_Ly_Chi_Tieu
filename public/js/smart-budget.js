// Smart Budget 50/30/20 - frontend logic
(function(){
    const needsInput = document.getElementById('needsInput');
    const wantsInput = document.getElementById('wantsInput');
    const savingsInput = document.getElementById('savingsInput');
    const needsAmt = document.getElementById('needsAmount');
    const wantsAmt = document.getElementById('wantsAmount');
    const savingsAmt = document.getElementById('savingsAmount');
    const incomeInput = document.getElementById('smartIncome');
    const calcIncomeBtn = document.getElementById('calcIncomeBtn');
    const saveBtn = document.getElementById('saveRatiosBtn');
    const resetBtn = document.getElementById('resetRatiosBtn');
    const canvasEl = document.getElementById('smartBudgetChart');
    const ctx = canvasEl ? canvasEl.getContext && canvasEl.getContext('2d') : null;
    let chart = null;

    function fixed(n){ return Math.round(n); }

    function readIncome(){
        if (!incomeInput) return 0;
        const v = parseFloat(incomeInput.value);
        return isNaN(v) || v <= 0 ? 0 : v;
    }

    function syncFrom(changedKey){
        if (!needsInput || !wantsInput || !savingsInput) return;
        // keep sum 100: adjust other inputs
        let a = parseInt(needsInput.value,10) || 0;
        let b = parseInt(wantsInput.value,10) || 0;
        let c = parseInt(savingsInput.value,10) || 0;
        const total = a+b+c;
        if (total === 0) { a = 50; b = 30; c = 20; needsInput.value = a; wantsInput.value = b; savingsInput.value = c; }

        if (total !== 100) {
            // Try to adjust the other fields to keep the changed value stable
            let rem = 100 - (a + b + c);
            if (changedKey === 'needs') {
                // adjust savings first
                c = Math.max(0, Math.min(100, c + rem));
                savingsInput.value = c;
                rem = 100 - (a + b + c);
                if (rem !== 0) { b = Math.max(0, Math.min(100, b + rem)); wantsInput.value = b; }
            } else if (changedKey === 'wants') {
                c = Math.max(0, Math.min(100, c + rem));
                savingsInput.value = c;
                rem = 100 - (a + b + c);
                if (rem !== 0) { a = Math.max(0, Math.min(100, a + rem)); needsInput.value = a; }
            } else if (changedKey === 'savings') {
                b = Math.max(0, Math.min(100, b + rem));
                wantsInput.value = b;
                rem = 100 - (a + b + c);
                if (rem !== 0) { a = Math.max(0, Math.min(100, a + rem)); needsInput.value = a; }
            } else {
                // default normalize proportionally
                const tot = a + b + c || 1;
                const na = Math.floor(a / tot * 100);
                const nb = Math.floor(b / tot * 100);
                const nc = Math.floor(c / tot * 100);
                let r = 100 - (na + nb + nc);
                if (r > 0) na += r;
                needsInput.value = na; wantsInput.value = nb; savingsInput.value = nc;
                a = na; b = nb; c = nc;
            }
        }
        updateAmounts();
    }

    function updateAmounts(){
        const income = readIncome();
        const a = parseInt(needsInput.value,10) || 0;
        const b = parseInt(wantsInput.value,10) || 0;
        const c = parseInt(savingsInput.value,10) || 0;
        const needAmt = fixed(income * a / 100);
        const wantAmt = fixed(income * b / 100);
        const saveAmt = fixed(income * c / 100);
        if (needsAmt) needsAmt.innerText = needAmt.toLocaleString('vi-VN') + '₫';
        if (wantsAmt) wantsAmt.innerText = wantAmt.toLocaleString('vi-VN') + '₫';
        if (savingsAmt) savingsAmt.innerText = saveAmt.toLocaleString('vi-VN') + '₫';
        updateChart([needAmt,wantAmt,saveAmt]);
        updateBars([needAmt,wantAmt,saveAmt]);
    }

    function updateChart(values){
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = ['Needs','Wants','Savings'];
        if(!chart){
            chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: values, backgroundColor: ['#28a745','#ffc107','#0dcaf0'] }]
                },
                options: { responsive: true }
            });
        } else {
            chart.data.datasets[0].data = values;
            chart.update();
        }
    }

    function updateBars(values){
        const total = values.reduce((s,x)=>s+x,0) || 1;
        const na = Math.round(values[0]/total*100);
        const nb = Math.round(values[1]/total*100);
        const nc = Math.round(values[2]/total*100);
        const nBar = document.getElementById('needsBar');
        const wBar = document.getElementById('wantsBar');
        const sBar = document.getElementById('savingsBar');
        if (nBar) nBar.style.width = na + '%';
        if (wBar) wBar.style.width = nb + '%';
        if (sBar) sBar.style.width = nc + '%';
    }

    // Listen to number input changes
    if (needsInput) needsInput.addEventListener('input', function(){ syncFrom('needs'); });
    if (wantsInput) wantsInput.addEventListener('input', function(){ syncFrom('wants'); });
    if (savingsInput) savingsInput.addEventListener('input', function(){ syncFrom('savings'); });

    if (calcIncomeBtn) calcIncomeBtn.addEventListener('click', async ()=>{
        // fetch total income for current month
        try{
            const resp = await fetch(BASE_URL + '/budgets/api_get_smart_budget');
            const text = await resp.text(); let data = null; try{data = text?JSON.parse(text):null;}catch(e){data=null}
            const payload = data && data.data ? data.data : {};
            const income = payload.total_income || 0;
            if(income>0){ incomeInput.value = income; updateAmounts(); }
            else SmartSpending.showToast('Không tìm thấy thu nhập trong tháng', 'info');
        }catch(e){ console.error(e); SmartSpending.showToast('Lỗi khi lấy thu nhập', 'error'); }
    });

    if (saveBtn) saveBtn.addEventListener('click', async ()=>{
        const a = parseInt(needsInput.value,10) || 0;
        const b = parseInt(wantsInput.value,10) || 0;
        const c = parseInt(savingsInput.value,10) || 0;
        if (a + b + c !== 100) { SmartSpending.showToast('Tổng phải bằng 100%', 'error'); return; }
        try{
            const resp = await fetch(BASE_URL + '/budgets/api_update_ratios',{
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||''},
                body: JSON.stringify({needs: a, wants: b, savings: c})
            });
            const text = await resp.text(); let data=null; try{data=text?JSON.parse(text):null}catch(e){data=null}
            if(data && data.success) {
                SmartSpending.showToast('Lưu thành công','success');
                // close modal if open
                const modalEl = document.getElementById('smartBudgetModal');
                try{ const m = bootstrap.Modal.getInstance(modalEl); if (m) m.hide(); }catch(e){}
                // notify other parts of app
                window.dispatchEvent(new CustomEvent('smartBudget:updated'));
            } else SmartSpending.showToast(data?.message||'Lưu thất bại','error');
        }catch(e){ console.error(e); SmartSpending.showToast('Lỗi khi lưu', 'error'); }
    });

    if (resetBtn) resetBtn.addEventListener('click', ()=>{
        if (needsInput) needsInput.value = 50; if (wantsInput) wantsInput.value = 30; if (savingsInput) savingsInput.value = 20; syncFrom();
    });

    // initialize: try fetch user settings
    (async function(){
        try{
            const resp = await fetch(BASE_URL + '/budgets/api_get_smart_budget');
            const text = await resp.text(); let data=null; try{data=text?JSON.parse(text):null}catch(e){data=null}
            const payload = data && data.data ? data.data : {};
            const settings = payload.settings || {needs_percent:50,wants_percent:30,savings_percent:20};
            if (needsInput) needsInput.value = settings.needs_percent;
            if (wantsInput) wantsInput.value = settings.wants_percent;
            if (savingsInput) savingsInput.value = settings.savings_percent;
            if (incomeInput) incomeInput.value = payload.total_income || 0;
            syncFrom();
        }catch(e){ console.error(e); syncFrom(); }
    })();
})();
