<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');

// Fetch user's folders for folder select
$currentUser = current_user();
$userFolders = [];
if ($currentUser) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, name FROM folders WHERE user_id = ? ORDER BY name ASC');
    $stmt->execute([$currentUser['id']]);
    $userFolders = $stmt->fetchAll();
}
?>
<div class="modal-backdrop" id="add-item-modal">
    <div class="modal-content" style="max-width: 580px;">
        <div class="modal-header">
            <h3 class="modal-title" id="add-item-modal-title">Add Vault Item</h3>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>

        <!-- Item Type Tabs -->
        <div style="display: flex; border-bottom: 1px solid var(--color-border); background: #F8FAFC;">
            <button type="button" class="item-type-tab active" data-tab="tab-password" onclick="switchAddTab('tab-password')" style="flex: 1; padding: 12px; font-size: 13px; font-weight: 600; background: none; border: none; border-bottom: 2px solid var(--color-brand-red); color: var(--color-brand-red); cursor: pointer;">Password</button>
            <button type="button" class="item-type-tab" data-tab="tab-address" onclick="switchAddTab('tab-address')" style="flex: 1; padding: 12px; font-size: 13px; font-weight: 600; background: none; border: none; border-bottom: 2px solid transparent; color: #64748B; cursor: pointer;">Address</button>
            <button type="button" class="item-type-tab" data-tab="tab-note" onclick="switchAddTab('tab-note')" style="flex: 1; padding: 12px; font-size: 13px; font-weight: 600; background: none; border: none; border-bottom: 2px solid transparent; color: #64748B; cursor: pointer;">Secure Note</button>
            <button type="button" class="item-type-tab" data-tab="tab-folder" onclick="switchAddTab('tab-folder')" style="flex: 1; padding: 12px; font-size: 13px; font-weight: 600; background: none; border: none; border-bottom: 2px solid transparent; color: #64748B; cursor: pointer;">Folder</button>
        </div>

        <div class="modal-body">
            <!-- TAB 1: Password -->
            <form id="form-add-password" onsubmit="submitAddPassword(event)">
                <div class="form-group">
                    <label class="form-label">Application / Site Name *</label>
                    <input type="text" name="app_name" class="form-control" placeholder="e.g. Amazon, Google, GitHub" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Login URL</label>
                    <input type="url" name="login_url" class="form-control" placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="form-label">Username / Email</label>
                    <input type="text" name="username" class="form-control" placeholder="username@example.com">
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="form-label" style="margin-bottom: 0;">Password *</label>
                        <button type="button" onclick="openPasswordGenerator()" style="background: none; border: none; font-size: 12px; font-weight: 600; color: var(--color-brand-red); cursor: pointer;">Generate</button>
                    </div>
                    <input type="password" name="password" id="input-add-pass" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Folder (Optional)</label>
                    <select name="folder_id" class="form-control">
                        <option value="">None (Personal Vault)</option>
                        <?php foreach ($userFolders as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Credential</button>
                </div>
            </form>

            <!-- TAB 2: Address -->
            <form id="form-add-address" onsubmit="submitAddAddress(event)" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Address Label *</label>
                    <input type="text" name="label" class="form-control" placeholder="e.g. Home, Office" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" placeholder="e.g. Alex">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" placeholder="e.g. Morgan">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" name="address_line1" class="form-control" placeholder="e.g. 100 Enterprise Way">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="e.g. San Francisco">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State / Province</label>
                        <input type="text" name="state" class="form-control" placeholder="e.g. California">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Zip / Postal Code</label>
                        <input type="text" name="zip_code" class="form-control" placeholder="e.g. 94107">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" placeholder="e.g. United States">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>

            <!-- TAB 3: Secure Note -->
            <form id="form-add-note" onsubmit="submitAddNote(event)" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Server SSH Key, API Token" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Note Type</label>
                    <select name="type" class="form-control">
                        <option value="note">Secure Note</option>
                        <option value="token">API Token / Key</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Content *</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Enter confidential information..." required></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Secure Note</button>
                </div>
            </form>

            <!-- TAB 4: Folder -->
            <form id="form-add-folder" onsubmit="submitAddFolder(event)" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Folder Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Client Accounts, HR Team" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Customer / Client Name</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="e.g. Acme Corp">
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Contact Email</label>
                    <input type="email" name="customer_email" class="form-control" placeholder="e.g. client@acme.com">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddItemModal(tabName = 'password') {
    switchAddTab('tab-' + tabName);
    openModal('add-item-modal');
}

function switchAddTab(tabId) {
    document.querySelectorAll('.item-type-tab').forEach(t => {
        if (t.getAttribute('data-tab') === tabId) {
            t.style.borderBottomColor = 'var(--color-brand-red)';
            t.style.color = 'var(--color-brand-red)';
        } else {
            t.style.borderBottomColor = 'transparent';
            t.style.color = '#64748B';
        }
    });

    document.getElementById('form-add-password').style.display = tabId === 'tab-password' ? 'block' : 'none';
    document.getElementById('form-add-address').style.display = tabId === 'tab-address' ? 'block' : 'none';
    document.getElementById('form-add-note').style.display = tabId === 'tab-note' ? 'block' : 'none';
    document.getElementById('form-add-folder').style.display = tabId === 'tab-folder' ? 'block' : 'none';
}

async function submitAddPassword(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    try {
        const res = await fetch('<?= APP_URL ?>/api/vault', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok) {
            toast.success('Password entry saved');
            closeModal('add-item-modal');
            form.reset();
            setTimeout(() => location.reload(), 500);
        } else {
            toast.error(result.error || 'Failed to save password');
        }
    } catch (err) {
        toast.error('Network error');
    }
}

async function submitAddAddress(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    try {
        const res = await fetch('<?= APP_URL ?>/api/addresses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok) {
            toast.success('Address saved');
            closeModal('add-item-modal');
            form.reset();
            setTimeout(() => location.reload(), 500);
        } else {
            toast.error(result.error || 'Failed to save address');
        }
    } catch (err) {
        toast.error('Network error');
    }
}

async function submitAddNote(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    try {
        const res = await fetch('<?= APP_URL ?>/api/notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok) {
            toast.success('Secure note saved');
            closeModal('add-item-modal');
            form.reset();
            setTimeout(() => location.reload(), 500);
        } else {
            toast.error(result.error || 'Failed to save note');
        }
    } catch (err) {
        toast.error('Network error');
    }
}

async function submitAddFolder(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    try {
        const res = await fetch('<?= APP_URL ?>/api/folders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok) {
            toast.success('Folder created');
            closeModal('add-item-modal');
            form.reset();
            setTimeout(() => location.reload(), 500);
        } else {
            toast.error(result.error || 'Failed to create folder');
        }
    } catch (err) {
        toast.error('Network error');
    }
}
</script>
