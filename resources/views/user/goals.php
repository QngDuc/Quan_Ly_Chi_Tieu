<?php $this->partial('header'); ?>

<!-- Goals Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/goals.css">

<section class="goals-page container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="mb-1">Mục Tiêu Tài Chính</h2>
            <p class="text-muted mb-0">Thiết lập, theo dõi và đạt được các mục tiêu tài chính của bạn</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div>
                <select class="form-select form-select-sm">
                    <option>Trạng thái</option>
                    <option value="all">Tất cả</option>
                </select>
            </div>
            <div>
                <select class="form-select form-select-sm">
                    <option>Khoảng thời gian</option>
                    <option value="monthly">Hàng tháng</option>
                </select>
            </div>
            <div class="d-flex align-items-center">
                <button class="btn btn-success" id="btnAddGoal" data-bs-toggle="modal" data-bs-target="#goalModal">
                    <i class="bi bi-plus-lg"></i> Thêm Mục Tiêu
                </button>
                <button class="btn btn-outline-secondary ms-2" id="btnSyncPending" style="display:none;">Đồng bộ (<span id="pendingCount">0</span>)</button>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="row g-3 mb-3" id="cardsRow">
                <?php
                    $top = array_slice($goals ?? [], 0, 3);
                    if (empty($top)) {
                        for ($i=0;$i<3;$i++) echo '<div class="col-md-4"><div class="card"><div class="card-body text-center text-muted">Chưa có mục tiêu</div></div></div>';
                    } else {
                        foreach ($top as $g) {
                            $saved = number_format($g['current_amount'] ?? 0, 0, ',', '.');
                            $target = number_format($g['target_amount'] ?? 0, 0, ',', '.');
                            $pct = min(100, round($g['progress_percentage'] ?? 0,1));
                            $statusLabel = ($g['status'] === 'completed') ? 'Hoàn thành' : (($g['status'] === 'active') ? 'Đang' : 'Hủy');
                            echo "<div class=\"col-md-4\"><div class=\"card\"><div class=\"card-body d-flex flex-column justify-content-between\">";
                            echo "<div class=\"d-flex justify-content-between align-items-start\"><div><h6 class=\"mb-1\">{$this->escape($g['name'])}</h6><small class=\"text-muted\">{$target} Mục tiêu • {$saved} Đã tiết kiệm</small></div><span class=\"badge badge-status bg-light text-success\">{$statusLabel}</span></div>";
                            echo "<div><div class=\"progress mt-2\"><div class=\"progress-bar bg-success\" role=\"progressbar\" style=\"width: {$pct}%\" aria-valuenow=\"{$pct}\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div></div><div class=\"small text-muted mt-1\">{$pct}%</div></div>";
                            echo "</div></div></div>";
                        }
                    }
                ?>
            </div>

            <div class="card mb-3 p-3">
                <div class="card-body">
                    <h6>Tổng Quan Tiến Độ Mục Tiêu</h6>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="goalProgressChart" style="height:220px"></canvas>
                        </div>
                        <div class="col-md-4">
                            <canvas id="goalPieChart" style="height:220px"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-3 mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Hành động nhanh</h6>
                    <button class="btn btn-outline-success w-100 mb-2" id="btnQuickAdd">+ Thêm Mục Tiêu</button>
                    <div class="small text-muted">Sử dụng biểu mẫu để thêm mục tiêu với danh mục và ngày tháng.</div>
                </div>
            </div>
            <div class="card p-3">
                <div class="card-body">
                    <h6 class="mb-2">Tóm tắt</h6>
                    <div class="small text-muted">Tổng mục tiêu: <?php echo $statistics['total_goals'] ?? 0; ?></div>
                    <div class="small text-muted">Đang hoạt động: <?php echo $statistics['active_goals'] ?? 0; ?></div>
                    <div class="small text-muted">Đã hoàn thành: <?php echo $statistics['completed_goals'] ?? 0; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- List of goals (compact) -->
    <div class="row" id="goalsContainer">
        <?php if (!empty($goals)): ?>
            <?php foreach ($goals as $goal): ?>
                <div class="col-md-6 mb-3">
                    <div class="card px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?php echo $this->escape($goal['name']); ?></div>
                                <div class="small text-muted"><?php echo number_format($goal['current_amount'],0,',','.'); ?>₫ đã tiết kiệm • <?php echo number_format($goal['target_amount'],0,',','.'); ?>₫ mục tiêu</div>
                            </div>
                            <div style="min-width:140px; text-align:right;">
                                <div class="fw-bold"><?php echo number_format($goal['progress_percentage'],1); ?>%</div>
                                <div class="small text-muted"><?php echo $goal['status'] === 'completed' ? 'Hoàn thành' : ($goal['status'] === 'active' ? 'Đang hoạt động' : 'Đã hủy'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card p-4 text-center text-muted">Chưa có mục tiêu nào. Nhấn Thêm Mục Tiêu để tạo mục tiêu đầu tiên.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Goal Modal (Add/Edit) -->
    <div class="modal fade" id="goalModal" tabindex="-1" aria-labelledby="goalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="goalModalLabel">Thêm Mục Tiêu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="goalForm">
                    <div class="modal-body">
                        <input type="hidden" id="goalId" name="goal_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-3">
                            <label for="goalName" class="form-label">Tên Mục Tiêu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="goalName" name="name" required placeholder="VD: Mua xe máy">
                        </div>

                        <div class="mb-3">
                            <label for="goalDescription" class="form-label">Mô Tả</label>
                            <textarea class="form-control" id="goalDescription" name="description" rows="3" placeholder="Mô tả chi tiết về mục tiêu..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="goalTargetAmount" class="form-label">Số Tiền Mục Tiêu (₫) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control amount-input" id="goalTargetAmount" name="target_amount" required placeholder="0">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="goalStartDate" class="form-label">Ngày Bắt Đầu</label>
                                <input type="date" class="form-control" id="goalStartDate" name="start_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="goalDeadline" class="form-label">Ngày Đến Hạn <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="goalDeadline" name="deadline" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="goalCategory" class="form-label">Danh Mục</label>
                                <select class="form-select" id="goalCategory" name="category_id">
                                    <option value="">-- Chọn danh mục (tùy chọn) --</option>
                                    <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                                        <option value="<?php echo $this->escape($cat['id']); ?>"><?php echo $this->escape($cat['name']); ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="goalStatus" class="form-label">Trạng Thái</label>
                                <select class="form-select" id="goalStatus" name="status">
                                    <option value="active">Đang hoạt động</option>
                                    <option value="completed">Hoàn thành</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveGoal">Lưu Mục Tiêu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php $this->partial('footer'); ?>
