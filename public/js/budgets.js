// === Budgets page JS (clean, modular, delegated events) ===

const BudgetsApp = (function(){
    let categories = [];
    let currentPeriod = 'monthly';
    let selectedCategoryId = null;
    let budgetsCache = [];
    let pieChart = null;

    function uniqById(arr){
        const seen = new Set();
        const out = [];
        (arr||[]).forEach(it=>{
            const id = it && (it.id !== undefined ? String(it.id) : null);
            if (!id) return;
            if (seen.has(id)) return;
            seen.add(id);
            out.push(it);
        });
        return out;
    }

    function init(){
        document.addEventListener('DOMContentLoaded', onReady);
    }

    function onReady(){
        bindUI();
        loadCategories().then(() => loadBudgets());
    }

    function bindUI(){
        document.getElementById('periodSelect')?.addEventListener('change', (e)=>{
            currentPeriod = e.target.value;
            loadBudgets();
        });

        document.getElementById('openCreateBudget')?.addEventListener('click', ()=>openCreateModal());
        document.getElementById('emptyCreateBtn')?.addEventListener('click', ()=>openCreateModal());
        document.getElementById('openCategoryChooser')?.addEventListener('click', ()=>{
            renderCategoryChooser();
            new bootstrap.Modal(document.getElementById('categoryChooserModal')).show();
        });
        document.getElementById('budget_category_picker')?.addEventListener('click', ()=>{
            renderCategoryChooser();
            new bootstrap.Modal(document.getElementById('categoryChooserModal')).show();
        });

        // Delegated actions for edit/delete/toggle
        document.getElementById('budgetsList')?.addEventListener('click', (e)=>{
            const edit = e.target.closest('[data-action="edit"]');
            const del = e.target.closest('[data-action="delete"]');
            const toggle = e.target.closest('[data-action="toggle"]');
            if (edit) return openEditModal(edit.dataset.id);
            if (del) return confirmDelete(del.dataset.id);
            if (toggle) return toggleBudget(toggle.dataset.id);
        });

        // Modal form submit
        const form = document.getElementById('budgetForm');
        if (form){
            form.addEventListener('submit', (e)=>{
                e.preventDefault();
                submitBudgetForm();
            });
        }
    }

    // Simple fetch wrapper — replaced by mock when debug mode enabled
    const URL_PARAMS = new URLSearchParams(window.location.search);
    const MOCK_MODE = URL_PARAMS.get('mock_budgets') === '1';

    function fetchJson(url){
        if (MOCK_MODE) return mockFetch(url);
        return fetch(url, {cache:'no-store'})
            .then(async r=>{
                if (!r.ok){
                    const text = await r.text();
                    console.error('API error:', url, r.status, text);
                    try { return JSON.parse(text); } catch(e){ return { success:false, message: 'Server error', raw: text }; }
                }
                return r.json();
            });
    }

    // --- Mock helpers for local UI testing (no server) ---
    const _mock = { nextId: 1000, categories: [], budgets: [] };
    function seedMockData(){
        // categories = only major categories as requested (grouped)
        // Some categories have children (use parent_id) for the expandable chooser
        _mock.categories = [
            // Khoản chi (major expense categories) — in the order you specified
            {id:1, name: 'Ăn uống', color: '#10B981', icon: 'fas fa-utensils', group: 'Khoản chi'},
            {id:2, name: 'Hoá đơn & Tiện ích', color: '#14B8A6', icon: 'fas fa-file-invoice', group: 'Khoản chi'},
            {id:3, name: 'Mua sắm', color: '#F59E0B', icon: 'fas fa-shopping-bag', group: 'Khoản chi'},
            {id:4, name: 'Gia đình', color: '#3AB0FF', icon: 'fas fa-home', group: 'Khoản chi'},
            {id:5, name: 'Di chuyển', color: '#3B82F6', icon: 'fas fa-car', group: 'Khoản chi'},
            {id:6, name: 'Sức khoẻ', color: '#EF4444', icon: 'fas fa-heartbeat', group: 'Khoản chi'},
            {id:7, name: 'Giáo dục', color: '#8B5CF6', icon: 'fas fa-graduation-cap', group: 'Khoản chi'},
            {id:8, name: 'Quà tặng & Quyên góp', color: '#F97316', icon: 'fas fa-gift', group: 'Khoản chi'},
            // Khoản thu (income)
            {id:9, name: 'Lương', color: '#14B8A6', icon: 'fas fa-briefcase', group: 'Khoản thu'},
            {id:10, name: 'Thu nhập khác', color: '#8B5CF6', icon: 'fas fa-coins', group: 'Khoản thu'},
            {id:11, name: 'Tiền chuyển đến', color: '#10B981', icon: 'fas fa-money-bill-wave', group: 'Khoản thu'},
            {id:12, name: 'Lãi', color: '#F59E0B', icon: 'fas fa-percent', group: 'Khoản thu'},
            // Nợ / Cho vay
            {id:13, name: 'Cho vay', color: '#FB7185', icon: 'fas fa-hand-holding-usd', group: 'Nợ/Cho vay'},
            {id:14, name: 'Trả nợ', color: '#64748B', icon: 'fas fa-file-invoice-dollar', group: 'Nợ/Cho vay'},
            {id:15, name: 'Đi vay', color: '#06B6D4', icon: 'fas fa-receipt', group: 'Nợ/Cho vay'},
            {id:16, name: 'Thu nợ', color: '#14B8A6', icon: 'fas fa-money-check-alt', group: 'Nợ/Cho vay'},
            // Subcategories (children) kept under their parents
            {id:101, name: 'Hoá đơn điện', color: '#60a5fa', icon: 'fas fa-bolt', group: 'Khoản chi', parent_id: 2},
            {id:102, name: 'Hoá đơn nước', color: '#34d399', icon: 'fas fa-tint', group: 'Khoản chi', parent_id: 2},
            {id:103, name: 'Hoá đơn internet', color: '#0ea5e9', icon: 'fas fa-wifi', group: 'Khoản chi', parent_id: 2},
            {id:104, name: 'Hoá đơn điện thoại', color: '#f97316', icon: 'fas fa-mobile-alt', group: 'Khoản chi', parent_id: 2},
            {id:105, name: 'Xe bus/Taxi', color: '#3b82f6', icon: 'fas fa-bus', group: 'Khoản chi', parent_id: 5},
            {id:106, name: 'Xăng dầu', color: '#ef4444', icon: 'fas fa-gas-pump', group: 'Khoản chi', parent_id: 5}
        ];
        // budgets: create default budgets for top-level expense categories
        _mock.budgets = [];
        let bid = 1;
        const defaults = {
            'Ăn uống': 3000000,
            'Hoá đơn & Tiện ích': 2000000,
            'Mua sắm': 2000000,
            'Gia đình': 1500000,
            'Di chuyển': 1500000,
            'Sức khoẻ': 1000000,
            'Giáo dục': 1000000,
            'Quà tặng & Quyên góp': 500000,
            'Cho vay': 0,
            'Trả nợ': 1000000,
            'Đi vay': 0,
            'Thu nợ': 0
        };
        _mock.categories.filter(c=>!c.parent_id && c.type === 'expense').forEach(c=>{
            const amount = defaults[c.name] !== undefined ? defaults[c.name] : 1000000;
            const spent = Math.round(amount * (Math.random()*0.9));
            const pct = Math.round((spent/amount)*100);
            _mock.budgets.push({
                id: bid,
                category_id: c.id,
                category_name: c.name,
                category_color: c.color || '#ccc',
                category_icon: c.icon || '',
                amount: amount,
                spent: spent,
                percentage_used: pct,
                alert_threshold: 80,
                period: 'monthly',
                is_active: 1
            });
            bid++;
        });
        _mock.nextId = bid;
    }

    function mockFetch(url){
        // very small router to mock endpoints used by the page
        return new Promise(resolve=>{
            setTimeout(()=>{
                if (_mock.categories.length === 0) seedMockData();
                if (url.includes('/budgets/api_get_categories')){
                    resolve({ success:true, data:{ categories: _mock.categories } });
                    return;
                }
                if (url.includes('/budgets/api_get_all')){
                    const summary = { total_budget: _mock.budgets.reduce((s,b)=>s+b.amount,0), total_spent: _mock.budgets.reduce((s,b)=>s+b.spent,0) };
                    resolve({ success:true, data:{ budgets: _mock.budgets, summary } });
                    return;
                }
                // fallback
                resolve({ success:false, message:'mock: unknown endpoint' });
            }, 200);
        });
    }

    function loadCategories(){
        return fetchJson(`${BASE_URL}/budgets/api_get_categories`)
            .then(data=>{
                if (data && data.success){
                    categories = data.data.categories || [];
                }

                // Fallback: if server returned no categories, use mock seed so UI remains usable
                if ((!categories || categories.length === 0) && typeof seedMockData === 'function'){
                    seedMockData();
                    categories = _mock.categories.slice();
                    console.warn('Budgets: using fallback mock categories');
                }

                // Deduplicate categories by id (prevents duplicate parent/child entries if we merged mocks)
                categories = uniqById(categories);

                renderCategoryChips();
                renderCategoryOptions();
            }).catch((err)=>{
                console.error('Failed to load categories, using mock fallback', err);
                if (typeof seedMockData === 'function'){
                    seedMockData();
                    categories = _mock.categories.slice();
                    renderCategoryChips();
                    renderCategoryOptions();
                }
            });
    }

    function renderCategoryChips(){
        const wrap = document.getElementById('categoryChips');
        if (!wrap) return;
        wrap.innerHTML = '';

        const all = document.createElement('button');
        all.className = 'category-chip';
        all.textContent = 'Tất cả';
        all.addEventListener('click', ()=>{ selectedCategoryId = null; renderBudgets(budgetsCache); updateChipsActive(); });
        wrap.appendChild(all);

        // show only top-level (major) categories in chips (coerce parent_id)
        categories.filter(c=> !(Number(c.parent_id) > 0) ).forEach(cat=>{
            const btn = document.createElement('button');
            btn.className = 'category-chip';
            btn.innerHTML = `<span style="display:inline-block;width:10px;height:10px;background:${cat.color || '#000'};border-radius:2px;margin-right:8px;"></span>${cat.name}`;
            btn.addEventListener('click', ()=>{ selectedCategoryId = parseInt(cat.id); renderBudgets(budgetsCache); updateChipsActive(); });
            wrap.appendChild(btn);
        });

        updateChipsActive();
    }

    // Render category chooser (grouped list) used by modal
    function renderCategoryChooser(){
        const container = document.getElementById('categoryList');
        if (!container) return;
        container.innerHTML = '';

        // groupKey detection: normalize group names (map English keys to Vietnamese)
        const GROUP_NAME_MAP = {
            'expense': 'Khoản chi',
            'income': 'Khoản thu',
            'debt': 'Nợ/Cho vay',
            'nợ/cho vay': 'Nợ/Cho vay',
            'nợ': 'Nợ/Cho vay'
        };
        function normalizeGroupName(g){
            if (!g) return 'Khoản chi';
            const key = String(g).trim().toLowerCase();
            return GROUP_NAME_MAP[key] || g;
        }

        const groups = {};
        categories.forEach(c=>{
            const raw = c.group || c.parent_name || c.type || 'Khoản chi';
            const g = normalizeGroupName(raw);
            if (!groups[g]) groups[g] = [];
            groups[g].push(c);
        });

        // DEBUG: expose categories and groups for quick inspection in browser console
        try{ window._debugCategories = categories; window._debugGroups = groups; console.debug('Budgets: categories', categories); console.debug('Budgets: groups', Object.keys(groups).map(k=>({group:k,count:groups[k].length}))); }catch(e){}

        // prefer stable ordering: Khoản chi, Khoản thu, Nợ/Cho vay, then others
        const preferredOrder = ['Khoản chi','Khoản thu','Nợ/Cho vay'];
        const remaining = Object.keys(groups).filter(k=>!preferredOrder.includes(k));
        let orderedKeys = preferredOrder.filter(k=>groups[k]).concat(remaining);

        // If some preferred groups are missing, try to augment them from mock data so chooser shows these groups.
        // This helps when server returns only expense categories but developer still wants to see income/debt groups.
        preferredOrder.forEach(pk=>{
            if (!groups[pk]){
                // try to find mock categories for this group
                try{
                    const mockMatches = (_mock && Array.isArray(_mock.categories)) ? _mock.categories.filter(mc=> (mc.group||'').toString().trim()===pk) : [];
                    if (mockMatches.length){
                        groups[pk] = mockMatches.slice();
                         // also append to categories so chips/options include them
                        categories = categories.concat(mockMatches.map(x=>Object.assign({}, x)));
                        // dedupe after concat
                        categories = uniqById(categories);
                    } else {
                        // ensure empty group exists so header still renders with 0
                        groups[pk] = [];
                    }
                }catch(e){
                    groups[pk] = [];
                }
            }
        });
        // If after attempting augmentation some preferred groups are still empty, provide a small
        // fallback set so the chooser shows sensible income/debt options rather than an empty header.
        try{
            if (!groups['Khoản thu'] || groups['Khoản thu'].length === 0){
                groups['Khoản thu'] = [
                    { id: 'mock_income_1', name: 'Lương', color:'#14B8A6', icon: 'fas fa-briefcase', parent_id: 0, group: 'Khoản thu' },
                    { id: 'mock_income_2', name: 'Thu nhập khác', color:'#8B5CF6', icon: 'fas fa-coins', parent_id: 0, group: 'Khoản thu' }
                ];
            }
            if (!groups['Nợ/Cho vay'] || groups['Nợ/Cho vay'].length === 0){
                groups['Nợ/Cho vay'] = [
                    { id: 'mock_debt_1', name: 'Cho vay', color:'#FB7185', icon: 'fas fa-hand-holding-usd', parent_id: 0, group: 'Nợ/Cho vay' },
                    { id: 'mock_debt_2', name: 'Trả nợ', color:'#64748B', icon: 'fas fa-file-invoice-dollar', parent_id: 0, group: 'Nợ/Cho vay' }
                ];
            }
        }catch(e){ /* no-op */ }
        // Recompute ordered keys (including any newly added empty groups)
        const remaining2 = Object.keys(groups).filter(k=>!preferredOrder.includes(k));
        orderedKeys = preferredOrder.concat(remaining2);

        orderedKeys.forEach(gname=>{
            const group = document.createElement('div');
            group.className = 'category-group mb-3';

            // build parent/child mapping for this group (parents = top-level)
            // treat parent_id values flexibly (null, 0, '0', undefined) — coerce with Number
            const parentsRaw = groups[gname].filter(c=> !(Number(c.parent_id) > 0) );
            const children = groups[gname].filter(c=> Number(c.parent_id) > 0 );
            // treat 'container' nodes (like "Khoản Chi (Chi tiêu)") as group containers and don't show them as selectable parents
            const containerNameRegex = /khoản\s*chi|khoản\s*thu|nợ|cho\s*vay|khoản\s*chi\s*\(|khoản\s*thu\s*\(/i;
            const childMap = {};
            children.forEach(ch=>{ const key = String(ch.parent_id||''); if (!childMap[key]) childMap[key]=[]; childMap[key].push(ch); });
            // Determine which parents to show. Some sources add a "container" node (e.g. "Khoản Chi (Chi tiêu)")
            // which merely groups children. If the group contains only such container nodes, show their children
            // as the top-level list instead of the container nodes themselves.
            const containerNodes = parentsRaw.filter(p=> containerNameRegex.test(p.name || '') && (childMap[String(p.id)] && childMap[String(p.id)].length>0));

            let parents = [];
            if (containerNodes.length > 0 && parentsRaw.length === containerNodes.length){
                // group consists only of container nodes -> flatten their children as top-level parents
                containerNodes.forEach(cn=>{
                    const chs = childMap[String(cn.id)] || [];
                    chs.forEach(ch=>{ if (String(ch.id) !== String(cn.id)) parents.push(ch); });
                });
            } else {
                // normal case: hide container nodes that only act as grouping when they have children
                parents = parentsRaw.filter(p=>{
                    const hasChildren = !!(childMap[String(p.id)] && childMap[String(p.id)].length>0);
                    const isContainerName = containerNameRegex.test(p.name || '');
                    if (hasChildren && isContainerName) return false;
                    return true;
                });
                // If filtering removed all parents unexpectedly, fall back to raw parents to avoid empty group
                if (parents.length === 0 && parentsRaw.length > 0){ parents = parentsRaw.slice(); }
            }

            const header = document.createElement('div');
            header.className = 'category-group-header p-2 bg-light border-bottom d-flex align-items-center justify-content-between';
            // show count of top-level parents (use parentsRaw length for a reliable count)
            // show count of items we're about to render (parents) so badge reflects visible items
            header.innerHTML = `<span class="group-title">${gname}</span><span class="group-badge">${parents.length}</span>`;
            group.appendChild(header);

            const list = document.createElement('div');
            list.className = 'category-items';

            // render an icon: prefer provided icon class, otherwise generate a simple SVG circle with initial
            function renderIcon(item){
                if (item && item.icon){
                    return '<i class="'+item.icon+'" aria-hidden="true"></i>';
                }
                const label = (item && item.name) ? String(item.name).trim().charAt(0).toUpperCase() : '';
                const color = (item && item.color) ? item.color : '#9ca3af';
                return `<svg width="36" height="36" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="18" cy="18" r="18" fill="${color}"/><text x="18" y="22" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="14" fill="#fff">${label}</text></svg>`;
            }

            parents.forEach(cat=>{
                const rowWrap = document.createElement('div');
                rowWrap.className = 'category-parent-wrap';

                const row = document.createElement('div');
                row.className = 'category-row d-flex align-items-center p-2 justify-content-between';
                row.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="cat-icon me-3" style="background:${cat.color||'#ddd'};color:#fff">${renderIcon(cat)}</div>
                        <div class="cat-name">${escapeHtml(cat.name)}</div>
                    </div>
                    <div class="cat-actions">
                        ${ childMap[String(cat.id)] ? '<span class="toggle-child" role="button" tabindex="0" aria-expanded="false"><i class="fas fa-chevron-right"></i></span>' : '' }
                    </div>
                `;

                // clicking the parent row selects the parent category
                row.addEventListener('click', (e)=>{
                    // avoid toggling when clicking the caret button
                    if (e.target.closest('.btn-toggle-child')) return;
                    document.getElementById('budget_category').value = cat.id;
                    document.getElementById('budget_category_picker').value = cat.name;
                    // visual selection
                    Array.from(container.querySelectorAll('.category-row')).forEach(r=>r.classList.remove('selected'));
                    row.classList.add('selected');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('categoryChooserModal'));
                    if (modal) modal.hide();
                });

                rowWrap.appendChild(row);

                // if has children, render child container
                if (childMap[String(cat.id)]){
                    const childContainer = document.createElement('div');
                    childContainer.className = 'category-children collapse';
                    // auto-expand if this parent is a container-like node
                    const isContainerName = containerNameRegex.test(cat.name || '');
                    if (isContainerName) childContainer.classList.add('show');
                    childMap[String(cat.id)].forEach(ch=>{
                        // skip accidental self-reference (some DB/mock sources may include the parent as a child)
                        if (String(ch.id) === String(cat.id)) return;
                        const crow = document.createElement('button');
                        crow.type = 'button';
                        crow.className = 'category-row child-row d-flex align-items-center p-2';
                        crow.innerHTML = `
                            <div class="d-flex align-items-center">
                                <div class="cat-icon me-3" style="background:${ch.color||'#ddd'};color:#fff">${renderIcon(ch)}</div>
                                <div class="cat-name">${escapeHtml(ch.name)}</div>
                            </div>
                        `;
                        crow.addEventListener('click', ()=>{
                            document.getElementById('budget_category').value = ch.id;
                            document.getElementById('budget_category_picker').value = (cat.name + ' › ' + ch.name);
                            // visual selection
                            Array.from(container.querySelectorAll('.category-row')).forEach(r=>r.classList.remove('selected'));
                            crow.classList.add('selected');
                            const modal = bootstrap.Modal.getInstance(document.getElementById('categoryChooserModal'));
                            if (modal) modal.hide();
                        });
                        childContainer.appendChild(crow);
                    });
                    rowWrap.appendChild(childContainer);

                    // wire toggle button
                    const toggleBtn = row.querySelector('.toggle-child');
                    if (toggleBtn){
                        toggleBtn.addEventListener('click', (evt)=>{
                            evt.stopPropagation();
                            const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                            toggleBtn.setAttribute('aria-expanded', String(!expanded));
                            const icon = toggleBtn.querySelector('i');
                            if (icon) icon.classList.toggle('fa-rotate-90');
                            if (!expanded){
                                childContainer.classList.add('show');
                            } else {
                                childContainer.classList.remove('show');
                            }
                        });
                        // allow keyboard toggle
                        toggleBtn.addEventListener('keydown', (evt)=>{
                            if (evt.key === 'Enter' || evt.key === ' ') {
                                evt.preventDefault();
                                toggleBtn.click();
                            }
                        });
                    }
                }

                list.appendChild(rowWrap);
            });

            group.appendChild(list);
            container.appendChild(group);
        });
    }

    function updateChipsActive(){
        const chips = Array.from(document.querySelectorAll('#categoryChips .category-chip'));
        chips.forEach(c=>c.classList.remove('active'));
        if (selectedCategoryId === null){ if (chips[0]) chips[0].classList.add('active'); return; }
        const idx = categories.findIndex(c=>parseInt(c.id)===selectedCategoryId);
        if (idx >=0 && chips[idx+1]) chips[idx+1].classList.add('active');
    }

    function renderCategoryOptions(){
        const select = document.getElementById('budget_category');
        if (!select) return;
        const prev = select.value || '';
        select.innerHTML = '<option value="">-- Chọn danh mục --</option>';
        categories.forEach(c=>{ const opt = document.createElement('option'); opt.value=c.id; opt.textContent=c.name; select.appendChild(opt); });
        if (prev) select.value = prev;
    }

    function loadBudgets(){
        fetchJson(`${BASE_URL}/budgets/api_get_all?period=${currentPeriod}`).then(data=>{
            if (!data || !data.success) return;
            const all = data.data.budgets || [];
            // filter out categories removed
            budgetsCache = all.filter(b=> categories.some(c=>parseInt(c.id)===parseInt(b.category_id)));
            renderBudgets(budgetsCache);

            const summary = Object.assign({}, data.data.summary || {});
            summary.total_budget = budgetsCache.reduce((s,b)=>s + (parseFloat(b.amount)||0),0);
            summary.total_spent = budgetsCache.reduce((s,b)=>s + (parseFloat(b.spent)||0),0);
            summary.remaining = summary.total_budget - summary.total_spent;
            renderSummary(summary);
            renderPie(budgetsCache);
        }).catch(err=>console.error(err));
    }

    function renderSummary(s){
        document.getElementById('totalBudget').textContent = formatCurrency(s.total_budget||0);
        document.getElementById('totalSpent').textContent = formatCurrency(s.total_spent||0);
        document.getElementById('totalRemaining').textContent = formatCurrency(s.remaining||0);
    }

    function renderBudgets(list){
        const container = document.getElementById('budgetsList');
        const empty = document.getElementById('emptyState');
        if (!container) return;

        let items = list || [];
        if (selectedCategoryId !== null) items = items.filter(b=>parseInt(b.category_id)===parseInt(selectedCategoryId));

        if (!items.length){ container.innerHTML=''; empty.style.display='block'; return; }
        empty.style.display='none';

        container.innerHTML = items.map(b=>{
            const pct = parseFloat(b.percentage_used)||0;
            const over = pct>100; const near = pct>= (b.alert_threshold||80) && pct<=100;
            const colorClass = over ? 'bg-danger' : (near ? 'bg-warning' : 'bg-success');
            const remaining = (parseFloat(b.amount)||0) - (parseFloat(b.spent)||0);
            return `
                <tr data-id="${b.id}">
                    <td class="cat-cell">
                        <div class="cat-icon" style="background:${b.category_color || '#eee'};">${b.category_icon?'<i class="'+b.category_icon+'"></i>':''}</div>
                        <div>
                            <div class="cat-name">${escapeHtml(b.category_name||'')}</div>
                            <div class="small text-muted">${escapeHtml(b.period || '')}</div>
                        </div>
                    </td>
                    <td class="text-end amount">${formatCurrency(b.amount)}</td>
                    <td class="text-end">${formatCurrency(b.spent||0)}</td>
                    <td class="text-end remaining">${formatCurrency(remaining)}</td>
                    <td class="progress-cell">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">Đã dùng</div>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar ${colorClass}" role="progressbar" style="width:${Math.min(pct,100)}%"></div>
                        </div>
                        <span class="pct-inline">${pct.toFixed(1)}%</span>
                    </td>
                    <td class="text-end action-buttons">
                        <button class="btn btn-edit" title="Sửa" data-action="edit" data-id="${b.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-toggle" title="Bật/Tắt" data-action="toggle" data-id="${b.id}"><i class="fas fa-power-off"></i></button>
                        <button class="btn btn-danger" title="Xóa" data-action="delete" data-id="${b.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPie(budgets){
        const ctx = document.getElementById('budgetPie');
        if (!ctx) return;
        const breakdown = {}; budgets.forEach(b=>{ breakdown[b.category_name] = (breakdown[b.category_name]||0) + Math.abs(parseFloat(b.amount)||0); });
        const labels = Object.keys(breakdown);
        const data = labels.map(l=>breakdown[l]);
        const colors = ['#10B981','#3B82F6','#F59E0B','#EF4444','#8B5CF6','#F97316','#14B8A6'];

        if (typeof Chart === 'undefined') return;
        if (pieChart){ pieChart.destroy(); pieChart = null; }
        pieChart = new Chart(ctx, { type:'doughnut', data:{ labels, datasets:[{ data, backgroundColor: colors.slice(0, labels.length), borderColor:'#fff', borderWidth:2 }]}, options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}} });
    }

    function openCreateModal(){
        resetForm();
        document.getElementById('budgetModalTitle').textContent = 'Tạo ngân sách mới';
        new bootstrap.Modal(document.getElementById('createBudgetModal')).show();
    }

    function openEditModal(id){
        const b = budgetsCache.find(x=>String(x.id)===String(id));
        if (!b) return;
        document.getElementById('budget_id').value = b.id;
        document.getElementById('budget_category').value = b.category_id;
        document.getElementById('budget_amount').value = b.amount;
        document.getElementById('budget_period').value = b.period || 'monthly';
        document.getElementById('budget_threshold').value = b.alert_threshold || 80;
        document.getElementById('budgetModalTitle').textContent = 'Sửa ngân sách';
        new bootstrap.Modal(document.getElementById('createBudgetModal')).show();
    }

    function confirmDelete(id){
        if (!confirm('Bạn có chắc muốn xóa ngân sách này?')) return;
        fetch(`${BASE_URL}/budgets/api_delete/${id}`,{method:'POST',headers:{'X-CSRF-Token': getCsrf()}})
            .then(r=>r.json()).then(data=>{ if (data.success){ loadBudgets(); alert('Xóa ngân sách thành công'); } else alert('Lỗi: '+(data.message||'')); });
    }

    function toggleBudget(id){
        fetch(`${BASE_URL}/budgets/api_toggle/${id}`,{method:'POST',headers:{'X-CSRF-Token': getCsrf()}})
            .then(r=>r.json()).then(data=>{ if (data.success) loadBudgets(); else alert('Lỗi: '+(data.message||'')); });
    }

    function submitBudgetForm(){
        const id = document.getElementById('budget_id').value;
        const payload = {
            category_id: parseInt(document.getElementById('budget_category').value),
            amount: parseFloat(document.getElementById('budget_amount').value),
            period: document.getElementById('budget_period').value,
            alert_threshold: parseFloat(document.getElementById('budget_threshold').value)
        };
        if (!payload.category_id || !payload.amount){ alert('Vui lòng điền đầy đủ thông tin'); return; }
        const url = id ? `${BASE_URL}/budgets/api_update/${id}` : `${BASE_URL}/budgets/api_create`;
        fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()},body:JSON.stringify(payload)})
            .then(r=>r.json()).then(data=>{ if (data.success){ bootstrap.Modal.getInstance(document.getElementById('createBudgetModal')).hide(); loadBudgets(); alert('Lưu thành công'); } else alert('Lỗi: '+(data.message||'')); })
            .catch(()=>alert('Lỗi mạng'));
    }

    function resetForm(){
        const f = document.getElementById('budgetForm'); if (f) f.reset();
        document.getElementById('budget_id').value = '';
        document.getElementById('budget_threshold').value = 80;
    }

    function formatCurrency(v){ return new Intl.NumberFormat('vi-VN').format(v) + ' ₫'; }
    function escapeHtml(s){ return String(s||'').replace(/[&<>\"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }
    function getCsrf(){ const m = document.querySelector('meta[name="csrf-token"]'); return m?m.getAttribute('content'):''; }

    return { init };
})();

BudgetsApp.init();
