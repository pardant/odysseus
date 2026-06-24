<?php
/**
 * Task card partial - rendered inside priority columns.
 * Expects $task array to be set.
 */
$catColor = $task['category_color'] ?? '#6366f1';
$catName = $task['category_name'] ?? null;
$assignedName = $task['assigned_name'] ?? $task['assigned_username'] ?? null;
$statusClass = str_replace('_', '-', $task['status']);
?>
<div class="task-card" data-id="<?= $task['id'] ?>" ondblclick="editTask(<?= $task['id'] ?>)">
    <div class="task-card-header">
        <h4 class="task-title"><?= h($task['title']) ?></h4>
        <span class="task-status <?= $statusClass ?>"><?= h(ucfirst(str_replace('_', ' ', $task['status']))) ?></span>
    </div>
    <?php if (!empty($task['description'])): ?>
    <p class="task-desc"><?= h(mb_strimwidth($task['description'], 0, 120, '...')) ?></p>
    <?php endif; ?>
    <div class="task-card-footer">
        <div class="task-meta">
            <?php if ($catName): ?>
            <span class="task-category" style="--cat-color: <?= h($catColor) ?>"><?= h($catName) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($assignedName): ?>
        <div class="task-assignee" title="Assigned to <?= h($assignedName) ?>">
            <span class="assignee-avatar"><?= strtoupper(substr($assignedName, 0, 1)) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
