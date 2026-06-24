/**
 * Admin Todo - Dashboard JavaScript
 * Handles drag-and-drop, modals, task CRUD, category management.
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initSortable();
    initTaskModal();
    initCategoriesModal();
});

/* ---- Sidebar ---- */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.getElementById('mobile-menu-btn');

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !menuBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

/* ---- Drag & Drop with SortableJS ---- */
function initSortable() {
    ['high', 'medium', 'low'].forEach(priority => {
        const el = document.getElementById(`list-${priority}`);
        if (!el) return;

        new Sortable(el, {
            group: 'tasks',
            animation: 200,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
            delay: 50,
            delayOnTouchOnly: true,
            onEnd: function(evt) {
                const targetList = evt.to;
                const newPriority = targetList.dataset.priority;
                const order = Array.from(targetList.children).map(card => card.dataset.id);
                saveOrder(newPriority, order);
                updateCounts();
            },
        });
    });
}

async function saveOrder(priority, order) {
    try {
        await fetch('/api/reorder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ priority, order }),
        });
    } catch (err) {
        console.error('Failed to save order:', err);
    }
}

function updateCounts() {
    ['high', 'medium', 'low'].forEach(p => {
        const list = document.getElementById(`list-${p}`);
        const count = document.getElementById(`count-${p}`);
        if (list && count) {
            count.textContent = list.children.length;
        }
    });
}

/* ---- Task Modal ---- */
function initTaskModal() {
    const modal = document.getElementById('task-modal');
    const newBtn = document.getElementById('new-task-btn');
    const closeBtn = document.getElementById('modal-close');
    const form = document.getElementById('task-form');
    const deleteBtn = document.getElementById('delete-task-btn');

    if (!modal) return;

    if (newBtn) {
        newBtn.addEventListener('click', () => openTaskModal());
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    if (form) {
        form.addEventListener('submit', handleTaskSubmit);
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', handleTaskDelete);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
            closeCategoriesModal();
        }
    });
}

function openTaskModal(task = null) {
    const modal = document.getElementById('task-modal');
    const title = document.getElementById('modal-title');
    const form = document.getElementById('task-form');
    const deleteBtn = document.getElementById('delete-task-btn');

    if (task) {
        title.textContent = 'Edit Task';
        document.getElementById('task-id').value = task.id;
        document.getElementById('task-title').value = task.title || '';
        document.getElementById('task-desc').value = task.description || '';
        document.getElementById('task-priority').value = task.priority || 'low';
        document.getElementById('task-status').value = task.status || 'pending';
        document.getElementById('task-category').value = task.category_id || '';
        document.getElementById('task-assigned').value = task.assigned_to || '';
        if (deleteBtn) deleteBtn.style.display = '';
    } else {
        title.textContent = 'New Task';
        form.reset();
        document.getElementById('task-id').value = '';
        document.getElementById('task-priority').value = 'low';
        if (deleteBtn) deleteBtn.style.display = 'none';
    }

    modal.classList.add('open');
    setTimeout(() => document.getElementById('task-title').focus(), 200);
}

function closeModal() {
    const modal = document.getElementById('task-modal');
    if (modal) modal.classList.remove('open');
}

async function editTask(id) {
    try {
        const res = await fetch(`/api/tasks.php?id=${id}`);
        if (!res.ok) throw new Error('Failed to load task');
        const task = await res.json();
        openTaskModal(task);
    } catch (err) {
        console.error('Error loading task:', err);
    }
}

async function handleTaskSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('task-id').value;
    const data = {
        title: document.getElementById('task-title').value,
        description: document.getElementById('task-desc').value,
        priority: document.getElementById('task-priority').value,
        status: document.getElementById('task-status').value,
        category_id: document.getElementById('task-category').value || null,
        assigned_to: document.getElementById('task-assigned').value || null,
    };

    try {
        const method = id ? 'PUT' : 'POST';
        if (id) data.id = id;

        const res = await fetch('/api/tasks.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });

        if (!res.ok) {
            const err = await res.json();
            alert(err.error || 'Failed to save task');
            return;
        }

        closeModal();
        location.reload();
    } catch (err) {
        console.error('Error saving task:', err);
        alert('Failed to save task');
    }
}

async function handleTaskDelete() {
    const id = document.getElementById('task-id').value;
    if (!id || !confirm('Delete this task?')) return;

    try {
        const res = await fetch(`/api/tasks.php?id=${id}`, { method: 'DELETE' });
        if (!res.ok) throw new Error('Delete failed');
        closeModal();
        location.reload();
    } catch (err) {
        console.error('Error deleting task:', err);
        alert('Failed to delete task');
    }
}

/* ---- Categories Modal ---- */
function initCategoriesModal() {
    const btn = document.getElementById('manage-categories-btn');
    const modal = document.getElementById('categories-modal');
    const form = document.getElementById('add-category-form');

    if (btn && modal) {
        btn.addEventListener('click', () => modal.classList.add('open'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeCategoriesModal();
        });
    }

    if (form) {
        form.addEventListener('submit', handleAddCategory);
    }

    document.querySelectorAll('.delete-category').forEach(btn => {
        btn.addEventListener('click', () => handleDeleteCategory(btn.dataset.id));
    });
}

function closeCategoriesModal() {
    const modal = document.getElementById('categories-modal');
    if (modal) modal.classList.remove('open');
}

async function handleAddCategory(e) {
    e.preventDefault();
    const form = e.target;
    const name = form.querySelector('[name="name"]').value.trim();
    const color = form.querySelector('[name="color"]').value;

    if (!name) return;

    try {
        const res = await fetch('/api/categories.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, color }),
        });

        if (!res.ok) throw new Error('Failed to add category');
        location.reload();
    } catch (err) {
        console.error('Error adding category:', err);
        alert('Failed to add category');
    }
}

async function handleDeleteCategory(id) {
    if (!confirm('Delete this category?')) return;

    try {
        const res = await fetch(`/api/categories.php?id=${id}`, { method: 'DELETE' });
        if (!res.ok) throw new Error('Failed to delete');

        const item = document.querySelector(`.category-item[data-id="${id}"]`);
        if (item) {
            item.style.transition = 'all 0.2s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            setTimeout(() => item.remove(), 200);
        }
    } catch (err) {
        console.error('Error deleting category:', err);
    }
}
