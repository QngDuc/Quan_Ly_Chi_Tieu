<?php $this->partial('header'); ?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/goals.css">

<div class="container-fluid goals-wrapper py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Mục Tiêu Tài Chính</h2>
                <p class="text-muted mb-0">Thiết lập, theo dõi và hiện thực hóa ước mơ của bạn.</p>
            </div>
            <button class="btn btn-primary btn-add-goal shadow-sm" id="btnAddGoal" data-bs-toggle="modal" data-bs-target="#goalModal">
                <i class="fas fa-plus me-2"></i>Thêm Mục Tiêu
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-wrapper bg-success-subtle text-success me-3">
                            <i class="fas fa-piggy-bank fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">Đã tiết kiệm</p>
                            <h4 class="mb-0 fw-bold text-dark">
                                <?php echo number_format($statistics['total_saved'] ?? 0, 0, ',', '.'); ?> ₫
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-wrapper bg-primary-subtle text-primary me-3">
                            <i class="fas fa-bullseye fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">Tổng mục tiêu</p>
                            <h4 class="mb-0 fw-bold text-dark">
                                <?php echo number_format($statistics['total_target'] ?? 0, 0, ',', '.'); ?> ₫
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-wrapper bg-warning-subtle text-warning me-3">
                            <i class="fas fa-hourglass-half fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">Đang thực hiện</p>
                            <h4 class="mb-0 fw-bold text-dark">
                                <?php echo $statistics['active_goals'] ?? 0; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-wrapper bg-info-subtle text-info me-3">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">Đã hoàn thành</p>
                            <h4 class="mb-0 fw-bold text-dark">
                                <?php echo $statistics['completed_goals'] ?? 0; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Danh sách mục tiêu</h5>
                </div>

                <div class="row g-3" id="goalsContainer">
                    <?php if (!empty($goals)): ?>
                        <?php foreach ($goals as $goal):
                            $percent = min(100, max(0, $goal['progress_percentage']));
                            // Consider goal completed when status is 'completed' or progress reached 100%
                            $isCompleted = ($goal['status'] === 'completed' || $percent >= 100);
                            $statusBadge = '';
                            if ($isCompleted) $statusBadge = '<span class="badge bg-success rounded-pill">Hoàn thành</span>';
                            elseif ($goal['status'] === 'active') $statusBadge = '<span class="badge badge-running rounded-pill">Đang thực hiện</span>';
                            else $statusBadge = '<span class="badge bg-secondary rounded-pill">Đã hủy</span>';
                        ?>
                            <div class="col-md-6" data-goal-id="<?php echo $goal['id']; ?>">
                                <div class="card goal-card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="goal-icon">
                                                    <i class="fas fa-bullseye"></i>
                                                </div>
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?php echo $this->escape($goal['name']); ?></h5>
                                                    <small class="text-muted">
                                                        <?php echo $statusBadge; ?>
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="dropdown">
                                                <button class="btn btn-options" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                                                    <?php if (!$isCompleted): ?>
                                                    <li>
                                                        <a class="dropdown-item py-2 fw-medium btn-deposit-trigger" href="#"
                                                            data-id="<?php echo $goal['id']; ?>"
                                                            data-name="<?php echo $this->escape($goal['name']); ?>">
                                                            <i class="fas fa-coins text-warning me-2" style="width:20px"></i>Nạp tiền
                                                        </a>
                                                    </li>
                                                    <?php if (!empty($goal['current_amount']) && $goal['current_amount'] > 0): ?>
                                                    <li>
                                                        <a class="dropdown-item py-2 text-primary btn-withdraw-goal" href="#" data-id="<?php echo $goal['id']; ?>">
                                                            <i class="fas fa-arrow-up me-2" style="width:20px"></i>Rút về số dư
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php else: ?>
                                                    <li>
                                                        <a class="dropdown-item py-2 text-success disabled" href="#">
                                                            <i class="fas fa-check-circle me-2" style="width:20px"></i>Hoàn thành
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <hr class="dropdown-divider my-1">
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 text-primary btn-edit-goal" href="#" data-goal-id="<?php echo $goal['id']; ?>">
                                                            <i class="fas fa-edit me-2" style="width:20px"></i>Chỉnh sửa
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 text-danger btn-delete-goal" href="#" data-goal-id="<?php echo $goal['id']; ?>">
                                                            <i class="fas fa-trash-alt me-2" style="width:20px"></i>Xóa
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small text-muted">Tiến độ</span>
                                                <span class="small fw-bold"><?php echo round($percent); ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-2">
                                                <small class="text-muted"><?php echo number_format($goal['current_amount'] ?? 0, 0, ',', '.'); ?> ₫ / <?php echo number_format($goal['target_amount'] ?? 0, 0, ',', '.'); ?> ₫</small>
                                                <small class="text-muted"><?php echo round($percent); ?>%</small>
                                            </div>
                                        </div>

                                        <?php if (!$isCompleted): ?>
                                        <button class="btn btn-sm btn-outline-primary w-100 mt-auto btn-deposit-trigger" data-id="<?php echo $this->escape($goal['id']); ?>" data-name="<?php echo $this->escape($goal['name']); ?>">
                                            <i class="fas fa-plus-circle me-1"></i> Nạp thêm tiền
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-success w-100 mt-auto" disabled>
                                            <i class="fas fa-check-circle me-1"></i> Hoàn thành
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <img src="https://cdni.iconscout.com/illustration/premium/thumb/goal-achievement-4439199-3728469.png" alt="No Goals" style="width: 150px; opacity: 0.8;">
                            <h6 class="mt-3 text-muted">Bạn chưa có mục tiêu nào.</h6>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="document.getElementById('btnAddGoal').click()">Tạo ngay</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tiến độ tổng quan</h6>
                        <div class="chart-container" style="position: relative; height: 250px;">
                            <canvas id="goalProgressChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Trạng thái mục tiêu</h6>
                        <div class="chart-container" style="position: relative; height: 250px;">
                            <canvas id="goalPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="goalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="goalModalLabel">Thêm Mục Tiêu Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="goalForm">
                <div class="modal-body p-4">
                    <input type="hidden" id="goalId" name="goal_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">TÊN MỤC TIÊU</label>
                        <input type="text" class="form-control bg-light border-0" id="goalName" name="name" required placeholder="VD: Mua Laptop mới">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">SỐ TIỀN MỤC TIÊU</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 amount-input fw-bold" id="goalTargetAmount" name="target_amount" required placeholder="0">
                            <span class="input-group-text bg-light border-0 text-muted">₫</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">NGÀY BẮT ĐẦU</label>
                            <input type="date" class="form-control bg-light border-0" id="goalStartDate" name="start_date">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">HẠN CHÓT</label>
                            <input type="date" class="form-control bg-light border-0" id="goalDeadline" name="deadline" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">DANH MỤC LIÊN KẾT (Tùy chọn)</label>
                        <select class="form-select bg-light border-0" id="goalCategory" name="category_id">
                            <option value="">-- Không liên kết --</option>
                            <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $this->escape($cat['name']); ?></option>
                            <?php endforeach;
                            endif; ?>
                        </select>
                        <div class="form-text small">Giao dịch thuộc danh mục này sẽ tự động cộng vào mục tiêu.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">MÔ TẢ</label>
                        <textarea class="form-control bg-light border-0" id="goalDescription" name="description" rows="2"></textarea>
                    </div>

                    <input type="hidden" id="goalStatus" name="status" value="active">
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveGoal">Lưu Mục Tiêu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Nạp Tiền Vào Mục Tiêu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="depositGoalId" name="goal_id">

                    <p class="text-muted mb-3">Đang nạp cho: <strong id="depositGoalName" class="text-dark"></strong></p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">SỐ TIỀN MUỐN NẠP</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 amount-input fw-bold fs-4 text-success"
                                id="depositAmount" name="amount" required placeholder="0">
                            <span class="input-group-text bg-light border-0 text-muted">₫</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">NGÀY GHI NHẬN</label>
                        <input type="date" class="form-control bg-light border-0"
                            name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">GHI CHÚ (Tùy chọn)</label>
                        <input type="text" class="form-control bg-light border-0"
                            name="note" placeholder="VD: Tiền thưởng, Tiết kiệm tháng 12...">
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <i class="fas fa-save me-2"></i>Xác nhận nạp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Xác nhận xóa mục tiêu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="deleteGoalId" value="">
                <input type="hidden" id="deleteCsrfToken" value="<?php echo $csrf_token; ?>">
                <p class="text-muted">Bạn có chắc chắn muốn xóa mục tiêu này? Hành động này sẽ xóa cả các giao dịch đã nạp (nếu có) liên quan đến mục tiêu.</p>
                <p class="fw-bold" id="deleteGoalName"></p>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnConfirmDeleteGoal">Xóa</button>
            </div>
        </div>
    </div>
</div>
<?php $this->partial('footer'); ?>

<script src="<?php echo BASE_URL; ?>/js/goals.js"></script>