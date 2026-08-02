<?php
/**
 * Trash Bin for deleted Reports and General Enquiries.
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Trash Bin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .trash-toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(160px, 220px) minmax(170px, 230px) auto;
            gap: .75rem;
            align-items: end;
        }
        .trash-toolbar .form-group { margin: 0; }
        .trash-toolbar label {
            display: block;
            margin-bottom: .35rem;
            color: var(--text-secondary-1);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .trash-toolbar input,
        .trash-toolbar select {
            width: 100%;
            box-sizing: border-box;
        }
        .trash-type {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .5rem;
            border-radius: 6px;
            background: color-mix(in srgb, var(--primary-color-1) 12%, transparent);
            color: var(--primary-color-1);
            font-size: .78rem;
            font-weight: 800;
            white-space: nowrap;
        }
        .trash-reason {
            font-weight: 700;
            color: var(--text-color-1);
        }
        .trash-muted {
            color: var(--text-secondary-1);
            font-size: .78rem;
        }
        .trash-preview {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .trash-actions {
            display: flex;
            gap: .45rem;
            justify-content: flex-end;
            white-space: nowrap;
        }
        .trash-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-top: 1rem;
        }
        .trash-pagination__buttons {
            display: flex;
            gap: .4rem;
        }
        .trash-empty {
            padding: 3rem 1rem !important;
            text-align: center;
            color: var(--text-secondary-1);
        }
        .trash-modal {
            position: fixed;
            inset: 0;
            z-index: 10100;
            display: none;
            place-items: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .65);
            backdrop-filter: blur(5px);
        }
        .trash-modal.active { display: grid; }
        .trash-modal__dialog {
            width: min(460px, 94vw);
            overflow: hidden;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            background: var(--card-bg-1);
            box-shadow: 0 24px 70px rgba(0, 0, 0, .3);
        }
        .trash-modal__header,
        .trash-modal__body,
        .trash-modal__actions { padding: 1rem 1.15rem; }
        .trash-modal__header {
            display: flex;
            align-items: center;
            gap: .75rem;
            border-bottom: 1px solid var(--border-color-1);
        }
        .trash-modal__header i {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 8px;
            color: #fff;
            background: #dc2626;
        }
        .trash-modal__header h2 { margin: 0; font-size: 1.05rem; }
        .trash-modal__header p,
        .trash-modal__body p { margin: .25rem 0 0; color: var(--text-secondary-1); }
        .trash-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            border-top: 1px solid var(--border-color-1);
        }
        #trashActionMessage {
            min-height: 1.25rem;
            margin-top: .75rem;
            color: #dc2626;
            font-weight: 700;
        }
        @media (max-width: 900px) {
            .trash-toolbar { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 620px) {
            .trash-toolbar { grid-template-columns: 1fr; }
            .trash-pagination { align-items: stretch; flex-direction: column; }
            .trash-pagination__buttons .btn { flex: 1; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>

    <main class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Trash Bin</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-trash-alt" style="color:var(--primary-color-1);margin-right:.5rem;"></i> Trash Bin</h1>
                <p>Review deleted reports and general enquiries before removing them permanently.</p>
            </div>

            <div class="sub-container">
                <div class="page-content">
                    <section class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-filter"></i> Find Deleted Items</h2>
                        </div>
                        <div class="module-card-content">
                            <div class="trash-toolbar">
                                <div class="form-group">
                                    <label for="trashSearch">Search</label>
                                    <input id="trashSearch" type="search" placeholder="Citizen, location, message, or ID">
                                </div>
                                <div class="form-group">
                                    <label for="trashType">Item Type</label>
                                    <select id="trashType">
                                        <option value="all">All items</option>
                                        <option value="report">Reports</option>
                                        <option value="general_enquiry">General enquiries</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="trashReason">Reason</label>
                                    <select id="trashReason">
                                        <option value="all">All reasons</option>
                                        <option value="duplicate">Duplicate</option>
                                        <option value="false_report">False report</option>
                                        <option value="spam">Spam or irrelevant</option>
                                        <option value="test_report">Test submission</option>
                                        <option value="resolved_elsewhere">Resolved elsewhere</option>
                                        <option value="privacy_request">Privacy request</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary" id="trashApplyBtn">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-trash-alt"></i> Deleted Reports and Enquiries</h2>
                            <span class="trash-muted" id="trashTotal">0 items</span>
                        </div>
                        <div class="module-card-content table-responsive">
                            <table class="data-table" id="trashTable">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Citizen</th>
                                        <th>Last Message</th>
                                        <th>Reason</th>
                                        <th>Deleted By</th>
                                        <th>Deleted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="trashTableBody">
                                    <tr><td colspan="7" class="trash-empty"><i class="fas fa-spinner fa-spin"></i> Loading trash...</td></tr>
                                </tbody>
                            </table>
                            <div class="trash-pagination">
                                <span class="trash-muted" id="trashPageSummary">Page 1</span>
                                <div class="trash-pagination__buttons">
                                    <button type="button" class="btn btn-secondary" id="trashPrevBtn" title="Previous page">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="trashNextBtn" title="Next page">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <div class="trash-modal" id="permanentDeleteModal" aria-hidden="true">
        <div class="trash-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="permanentDeleteTitle">
            <div class="trash-modal__header">
                <i class="fas fa-trash-alt"></i>
                <div>
                    <h2 id="permanentDeleteTitle">Permanently delete this item?</h2>
                    <p>This action cannot be undone.</p>
                </div>
            </div>
            <div class="trash-modal__body">
                <p id="permanentDeleteSummary"></p>
                <div id="trashActionMessage"></div>
            </div>
            <div class="trash-modal__actions">
                <button type="button" class="btn btn-secondary" id="permanentDeleteCancel">Cancel</button>
                <button type="button" class="btn btn-danger" id="permanentDeleteConfirm">
                    <i class="fas fa-trash-alt"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>

    <script>
        const trashState = {
            page: 1,
            limit: 10,
            totalPages: 1,
            selected: null,
            loading: false
        };

        function escapeTrashHtml(value) {
            const element = document.createElement('div');
            element.textContent = value == null ? '' : String(value);
            return element.innerHTML;
        }

        async function readTrashResponse(response) {
            const raw = await response.text();
            let data = {};
            try {
                data = raw ? JSON.parse(raw) : {};
            } catch (error) {
                data = { success: false, message: raw || 'Invalid server response' };
            }
            if (!response.ok) {
                data.success = false;
                data.message = data.message || 'HTTP ' + response.status;
            }
            return data;
        }

        function formatTrashDate(value) {
            if (!value) return 'Unknown';
            const parsed = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString();
        }

        function renderTrash(items, pagination) {
            const body = document.getElementById('trashTableBody');
            const total = Number(pagination.total || 0);
            trashState.totalPages = Math.max(1, Number(pagination.totalPages || 1));
            document.getElementById('trashTotal').textContent = total + (total === 1 ? ' item' : ' items');
            document.getElementById('trashPageSummary').textContent =
                'Page ' + trashState.page + ' of ' + trashState.totalPages + ' · ' + total + ' total';
            document.getElementById('trashPrevBtn').disabled = trashState.page <= 1;
            document.getElementById('trashNextBtn').disabled = trashState.page >= trashState.totalPages;

            if (!items.length) {
                body.innerHTML = '<tr><td colspan="7" class="trash-empty"><i class="fas fa-trash-alt"></i><br>No deleted items found.</td></tr>';
                return;
            }

            body.innerHTML = items.map(function (item) {
                const typeIcon = item.itemType === 'general_enquiry' ? 'fa-comments' : 'fa-clipboard-list';
                const details = item.details
                    ? '<div class="trash-muted">' + escapeTrashHtml(item.details) + '</div>'
                    : '';
                const phone = item.userPhone
                    ? '<div class="trash-muted">' + escapeTrashHtml(item.userPhone) + '</div>'
                    : '';
                return '<tr>' +
                    '<td><span class="trash-type"><i class="fas ' + typeIcon + '"></i> ' + escapeTrashHtml(item.itemTypeLabel) + '</span></td>' +
                    '<td><strong>' + escapeTrashHtml(item.userName || 'Unknown') + '</strong>' + phone + '</td>' +
                    '<td><div class="trash-preview" title="' + escapeTrashHtml(item.lastMessage || '') + '">' + escapeTrashHtml(item.lastMessage || 'No messages') + '</div>' +
                        '<div class="trash-muted">' + Number(item.messageCount || 0) + ' messages</div></td>' +
                    '<td><span class="trash-reason">' + escapeTrashHtml(item.reasonLabel) + '</span>' + details + '</td>' +
                    '<td>' + escapeTrashHtml(item.deletedBy || 'Administrator') + '</td>' +
                    '<td><small>' + escapeTrashHtml(formatTrashDate(item.deletedAt)) + '</small></td>' +
                    '<td><div class="trash-actions">' +
                        '<button type="button" class="btn btn-secondary btn-sm restore-trash-btn" data-id="' + Number(item.id) + '" title="Restore to Open"><i class="fas fa-undo"></i></button>' +
                        '<button type="button" class="btn btn-danger btn-sm permanent-trash-btn" data-id="' + Number(item.id) + '" title="Delete permanently"><i class="fas fa-trash-alt"></i></button>' +
                    '</div></td>' +
                '</tr>';
            }).join('');

            body.querySelectorAll('.restore-trash-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    restoreTrashItem(Number(button.dataset.id));
                });
            });
            body.querySelectorAll('.permanent-trash-btn').forEach(function (button) {
                const item = items.find(function (entry) { return Number(entry.id) === Number(button.dataset.id); });
                button.addEventListener('click', function () { openPermanentDelete(item); });
            });
        }

        async function loadTrash() {
            if (trashState.loading) return;
            trashState.loading = true;
            const body = document.getElementById('trashTableBody');
            body.innerHTML = '<tr><td colspan="7" class="trash-empty"><i class="fas fa-spinner fa-spin"></i> Loading trash...</td></tr>';

            const params = new URLSearchParams({
                page: String(trashState.page),
                limit: String(trashState.limit),
                type: document.getElementById('trashType').value,
                reason: document.getElementById('trashReason').value,
                search: document.getElementById('trashSearch').value.trim()
            });

            try {
                const response = await fetch('../api/chat-trash.php?' + params.toString(), { cache: 'no-store' });
                const data = await readTrashResponse(response);
                if (!data.success) throw new Error(data.message || 'Unable to load trash');
                renderTrash(Array.isArray(data.items) ? data.items : [], data.pagination || {});
            } catch (error) {
                body.innerHTML = '<tr><td colspan="7" class="trash-empty" style="color:#dc2626;">' + escapeTrashHtml(error.message) + '</td></tr>';
            } finally {
                trashState.loading = false;
            }
        }

        async function restoreTrashItem(trashId) {
            const button = document.querySelector('.restore-trash-btn[data-id="' + trashId + '"]');
            if (button) button.disabled = true;
            try {
                const response = await fetch('../api/chat-trash.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'restore', trashId: trashId })
                });
                const data = await readTrashResponse(response);
                if (!data.success) throw new Error(data.message || 'Unable to restore item');
                await loadTrash();
            } catch (error) {
                window.alert(error.message || 'Unable to restore item');
            } finally {
                if (button) button.disabled = false;
            }
        }

        function openPermanentDelete(item) {
            if (!item) return;
            trashState.selected = item;
            const noun = item.itemType === 'general_enquiry' ? 'general enquiry' : 'report';
            document.getElementById('permanentDeleteTitle').textContent = 'Permanently delete this ' + noun + '?';
            document.getElementById('permanentDeleteSummary').textContent =
                (item.userName || 'Unknown') + ' · ' + (item.reasonLabel || 'No reason');
            document.getElementById('trashActionMessage').textContent = '';
            const modal = document.getElementById('permanentDeleteModal');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closePermanentDelete() {
            const modal = document.getElementById('permanentDeleteModal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            trashState.selected = null;
        }

        async function confirmPermanentDelete() {
            if (!trashState.selected) return;
            const button = document.getElementById('permanentDeleteConfirm');
            const message = document.getElementById('trashActionMessage');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            message.textContent = '';

            try {
                const response = await fetch('../api/chat-trash.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_permanently',
                        trashId: Number(trashState.selected.id)
                    })
                });
                const data = await readTrashResponse(response);
                if (!data.success) throw new Error(data.message || 'Permanent deletion failed');
                closePermanentDelete();
                if (trashState.page > 1 && document.querySelectorAll('#trashTableBody tr').length <= 1) {
                    trashState.page -= 1;
                }
                await loadTrash();
            } catch (error) {
                message.textContent = error.message || 'Permanent deletion failed';
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Permanently';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('trashApplyBtn').addEventListener('click', function () {
                trashState.page = 1;
                loadTrash();
            });
            document.getElementById('trashSearch').addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    trashState.page = 1;
                    loadTrash();
                }
            });
            document.getElementById('trashPrevBtn').addEventListener('click', function () {
                if (trashState.page > 1) {
                    trashState.page -= 1;
                    loadTrash();
                }
            });
            document.getElementById('trashNextBtn').addEventListener('click', function () {
                if (trashState.page < trashState.totalPages) {
                    trashState.page += 1;
                    loadTrash();
                }
            });
            document.getElementById('permanentDeleteCancel').addEventListener('click', closePermanentDelete);
            document.getElementById('permanentDeleteConfirm').addEventListener('click', confirmPermanentDelete);
            document.getElementById('permanentDeleteModal').addEventListener('click', function (event) {
                if (event.target.id === 'permanentDeleteModal') closePermanentDelete();
            });
            loadTrash();
        });
    </script>
</body>
</html>
