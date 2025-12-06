<?php $this->partial('header'); ?>

<!-- Goals Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/user/goals/goals.css">

<section class="goals-section">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">
            <i class="bi bi-trophy"></i> Mục Tiêu Tiết Kiệm
        </h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#goalModal" id="btnAddGoal">
            <i class="bi bi-plus-circle"></i> Thêm Mục Tiêu
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tổng Mục Tiêu</p>
                            <h4 class="mb-0" id="totalGoals"><?php echo $statistics['total_goals'] ?? 0; ?></h4>
                        </div>
                        <div class="stat-icon bg-primary-light">
                            <i class="bi bi-trophy"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Đang Hoạt Động</p>
                            <h4 class="mb-0 text-primary" id="activeGoals"><?php echo $statistics['active_goals'] ?? 0; ?></h4>
                        </div>
                        <div class="stat-icon bg-success-light">
                            <i class="bi bi-rocket-takeoff"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Đã Hoàn Thành</p>
                            <h4 class="mb-0 text-success" id="completedGoals"><?php echo $statistics['completed_goals'] ?? 0; ?></h4>
                        </div>
                        <div class="stat-icon bg-info-light">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tỷ Lệ Đạt</p>
                            <h4 class="mb-0 text-info" id="achievementRate">
                                <?php 
                                $rate = $statistics['total_target'] > 0 
                                    ? round(($statistics['total_saved'] / $statistics['total_target']) * 100, 1) 
                                    : 0;
                                echo $rate . '%';
                                ?>
                            </h4>
                        </div>
                        <div class="stat-icon bg-warning-light">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Goals Grid -->
    <div class="row" id="goalsContainer">
        <?php if (!empty($goals)): ?>
            <?php foreach ($goals as $goal): ?>
                <div class="col-lg-4 col-md-6 mb-4" data-goal-id="<?php echo $goal['id']; ?>">
                    <div class="card goal-card h-100">
                        <div class="card-body">
                            <!-- Goal Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="goal-name mb-1"><?php echo $this->escape($goal['name']); ?></h5>
                                    <span class="badge bg-<?php echo $goal['status'] === 'completed' ? 'success' : ($goal['status'] === 'active' ? 'primary' : 'secondary'); ?>">
                                        <?php 
                                        echo $goal['status'] === 'completed' ? 'Hoàn thành' : 
                                             ($goal['status'] === 'active' ? 'Đang hoạt động' : 'Đã hủy'); 
                                        ?>
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item btn-edit-goal" href="#" data-goal-id="<?php echo $goal['id']; ?>">
                                            <i class="bi bi-pencil"></i> Chỉnh sửa
                                        </a></li>
                                        <li><a class="dropdown-item btn-mark-completed" href="#" data-goal-id="<?php echo $goal['id']; ?>">
                                            <i class="bi bi-check-circle"></i> Đánh dấu hoàn thành
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger btn-delete-goal" href="#" data-goal-id="<?php echo $goal['id']; ?>">
                                            <i class="bi bi-trash"></i> Xóa
                                        </a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Goal Description -->
                            <?php if (!empty($goal['description'])): ?>
                                <p class="text-muted small mb-3"><?php echo $this->escape($goal['description']); ?></p>
                            <?php endif; ?>

                            <!-- Goal Progress -->
                            <div class="goal-progress mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-success fw-bold">
                                        <?php echo number_format($goal['current_amount'], 0, ',', '.'); ?>₫
                                    </span>
                                    <span class="text-muted">
                                        / <?php echo number_format($goal['target_amount'], 0, ',', '.'); ?>₫
                                    </span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-<?php echo $goal['progress_percentage'] >= 100 ? 'success' : 'primary'; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo min($goal['progress_percentage'], 100); ?>%"
                                         aria-valuenow="<?php echo $goal['progress_percentage']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="text-end mt-1">
                                    <small class="text-muted"><?php echo number_format($goal['progress_percentage'], 1); ?>%</small>
                                </div>
                            </div>

                            <!-- Goal Deadline -->
                            <div class="goal-deadline">
                                <i class="bi bi-calendar3"></i>
                                <small class="text-muted">
                                    Đến hạn: <?php echo date('d/m/Y', strtotime($goal['deadline'])); ?>
                                    <?php
                                    $daysLeft = floor((strtotime($goal['deadline']) - time()) / (60 * 60 * 24));
                                    if ($daysLeft > 0) {
                                        echo " <span class='text-info'>($daysLeft ngày)</span>";
                                    } elseif ($daysLeft === 0) {
                                        echo " <span class='text-warning'>(Hôm nay!)</span>";
                                    } else {
                                        echo " <span class='text-danger'>(Quá hạn)</span>";
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-trophy text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">Chưa có mục tiêu nào</h5>
                        <p class="text-muted">Hãy tạo mục tiêu tiết kiệm đầu tiên của bạn!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#goalModal">
                            <i class="bi bi-plus-circle"></i> Tạo Mục Tiêu
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Goal Modal (Add/Edit) -->
<div class="modal fade" id="goalModal" tabindex="-1" aria-labelledby="goalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                    
                    <div class="mb-3">
                        <label for="goalDeadline" class="form-label">Ngày Đến Hạn <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="goalDeadline" name="deadline" required>
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

<!-- Goals JavaScript -->
<script src="<?php echo BASE_URL; ?>/user/goals/goals.js"></script>

<?php $this->partial('footer'); ?>
