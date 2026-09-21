<style>
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .card-header {
        background-color: #fafafa;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
    }

    .card-body {
        padding: 24px;
    }

    .form-control, .form-select, .form-check-input {
        border-radius: 2px;
        border: 1px solid #d9d9d9;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .btn {
        border-radius: 2px;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-secondary {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .btn-secondary:hover {
        background-color: #e6f7ff;
        border-color: #40a9ff;
        color: #40a9ff;
    }

    .system-role-warning {
        background-color: #fff7e6;
        border: 1px solid #ffd591;
        color: #ad6800;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
    }

    .permission-matrix {
        border: 1px solid #f0f0f0;
        border-radius: 6px;
        overflow: hidden;
    }

    .module-group {
        border-bottom: 1px solid #f0f0f0;
    }

    .module-group:last-child {
        border-bottom: none;
    }

    .module-group-header {
        background: #fafafa;
        padding: 12px 16px;
        cursor: pointer;
        border: none;
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        transition: background-color 0.2s;
    }

    .module-group-header:hover {
        background: #f0f0f0;
    }

    .module-group-header i {
        transition: transform 0.2s;
    }

    .module-group-header.collapsed i {
        transform: rotate(-90deg);
    }

    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        color: #262626;
        background-color: #fafafa !important;
    }

    .form-check-input:checked {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .form-check-input:indeterminate {
        background-color: #faad14;
        border-color: #faad14;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
    }

    .permission-global-controls {
        background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
        border: 1px solid #d9d9d9;
    }

    .table-secondary {
        background-color: #f8f9fa !important;
    }

    .text-muted {
        font-size: 0.875rem;
    }

    td .text-muted {
        font-size: 1rem;
        opacity: 0.5;
    }

    .module-info small {
        font-style: italic;
        color: #6c757d;
    }

    .montera-tree{max-height:520px;overflow-y:auto;border:1px solid #e6e8f0;border-radius:8px;padding:6px 10px;background:#fff}
    .montera-tree .mt-row{display:flex;align-items:center;gap:9px;padding:6px 8px;border-radius:6px}
    .montera-tree .mt-row:hover{background:#f6f7fb}
    .montera-tree .mt-group{font-weight:600;color:#1f2340}
    .montera-tree .mt-kids{margin-left:13px;padding-left:16px;border-left:1px solid #e3e6ef}
    .montera-tree .mt-kids.hide{display:none}
    .montera-tree .mt-act{font-size:.88rem;color:#555}
    .montera-tree .mt-toggle{cursor:pointer;width:16px;color:#8a90a6;font-size:.8rem;user-select:none}
    .montera-tree .mt-toggle.empty{visibility:hidden}
    .montera-tree label{cursor:pointer;margin:0}
    .montera-tree input[type=checkbox]{flex:0 0 auto;margin:0}
    .montera-tree::-webkit-scrollbar{width:7px}
    .montera-tree::-webkit-scrollbar-thumb{background:#cfd4e4;border-radius:8px}
</style>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Role: <?= htmlspecialchars($data['display_name']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (in_array($data['name'], ['super_admin', 'admin', 'employee'])): ?>
                        <div class="system-role-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>System Role:</strong> This is a system role. Some fields may be restricted from editing.
                        </div>
                    <?php endif; ?>

                    <form id="roleForm" onsubmit="submitRole(event)">
                        <input type="hidden" name="id" value="<?= $data['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="dt[name]" 
                                           value="<?= htmlspecialchars($data['name']) ?>"
                                           <?= in_array($data['name'], ['super_admin', 'admin', 'employee']) ? 'readonly' : '' ?>
                                           required pattern="[a-z_]+" 
                                           title="Use lowercase letters and underscores only">
                                    <small class="form-text text-muted">Use lowercase with underscores (e.g., senior_developer)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="display_name" name="dt[display_name]" 
                                           value="<?= htmlspecialchars($data['display_name']) ?>"
                                           required placeholder="e.g., Senior Developer">
                                    <small class="form-text text-muted">Human-readable name for display</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="dt[is_active]" 
                                               value="1" <?= $data['is_active'] ? 'checked' : '' ?>
                                               <?= in_array($data['name'], ['super_admin', 'admin', 'employee']) ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <?php if (in_array($data['name'], ['super_admin', 'admin', 'employee'])): ?>
                                        <input type="hidden" name="dt[is_active]" value="<?= $data['is_active'] ?>">
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Only active roles can be assigned to users</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="dt[description]" rows="3"
                                      placeholder="Describe the role's responsibilities and scope..."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Optional description of the role</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="divisi" class="form-label">Nama Divisi</label>
                                    <input type="text" class="form-control" id="divisi" name="dt[divisi]"
                                           list="divisi_list" placeholder="Ketik atau pilih divisi" value="<?= htmlspecialchars($data['divisi']) ?>">
                                    <datalist id="divisi_list">
                                        <option value="Admin ads">
                                        <option value="Anak Content">
                                        <option value="Admin Aff">
                                        <option value="Anak Live">
                                        <option value="Design Graphics">
                                        <option value="Admin">
                                        <option value="Anak Packing">
                                        <option value="Finance">
                                        <option value="Marketing">
                                        <option value="CS">
                                        <option value="HR">
                                        <option value="Buzzer">
                                    </datalist>
                                    <small class="form-text text-muted">Bisa ketik bebas atau pilih dari daftar</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_karyawan" class="form-label">Nama Karyawan</label>
                                    <input type="text" class="form-control" id="nama_karyawan" name="dt[nama_karyawan]"
                                           placeholder="Contoh: Budi, Siti" value="<?= htmlspecialchars($data['nama_karyawan']) ?>">
                                    <small class="form-text text-muted">Catatan siapa pemegang peran ini</small>
                                </div>
                            </div>
                        </div>

                        <!-- Akses Web Montera Ganteng Section -->
                        <div class="mt-4">
                            <h6 class="mb-3">
                                <i class="bi bi-shield-lock me-2"></i>Akses Web Montera Ganteng
                            </h6>
                            
                            <!-- Global Controls -->
                            <div class="permission-global-controls mb-3 p-3 bg-light border rounded">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="master-check-all">
                                            <label class="form-check-label fw-bold" for="master-check-all">
                                                Pilih Semua Akses
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <?php
                                        // Get all unique permissions across all modules
                                        $all_permissions = [];
                                        foreach ($module_groups as $group_modules) {
                                            foreach ($group_modules as $module) {
                                                $module_perms = $module_permissions[$module['name']] ?? ['view'];
                                                $all_permissions = array_unique(array_merge($all_permissions, $module_perms));
                                            }
                                        }
                                        
                                        $permission_labels = [
                                            'view' => 'All View',
                                            'create' => 'All Create', 
                                            'edit' => 'All Update',
                                            'delete' => 'All Delete',
                                            'approve' => 'All Approve'
                                        ];
                                        ?>
                                        <div class="d-flex gap-3 flex-wrap">
                                            <?php foreach ($all_permissions as $permission): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input column-check-all" type="checkbox" id="all-<?= $permission ?>" data-permission="<?= $permission ?>">
                                                    <label class="form-check-label" for="all-<?= $permission ?>"><?= $permission_labels[$permission] ?? 'All ' . ucfirst($permission) ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-matrix montera-tree">
                                <?php
                                $lbl = ['view'=>'Lihat','create'=>'Tambah','edit'=>'Ubah','delete'=>'Hapus','approve'=>'Setujui'];
                                $cp  = isset($current_permissions) ? $current_permissions : array();
                                foreach ($module_groups as $group_name => $modules):
                                    if (empty($modules)) continue;
                                    $gid = 'grp-'.preg_replace('/[^a-z0-9]+/','-', strtolower($group_name));
                                ?>
                                <div class="mt-node">
                                    <div class="mt-row mt-group">
                                        <span class="mt-toggle" data-target="#<?= $gid ?>">&#9662;</span>
                                        <input type="checkbox" class="form-check-input mt-chk mt-g" id="<?= $gid ?>-c">
                                        <label for="<?= $gid ?>-c"><?= htmlspecialchars($group_name) ?></label>
                                        <small class="text-muted ms-1">(<?= count($modules) ?>)</small>
                                    </div>
                                    <div id="<?= $gid ?>" class="mt-kids">
                                        <?php foreach ($modules as $m):
                                            $ps  = $module_permissions[$m['name']] ?? ['view'];
                                            $mid = 'mod-'.$m['id'];
                                            $cur = $cp[$m['id']] ?? array();
                                        ?>
                                        <div class="mt-node">
                                            <div class="mt-row mt-mod">
                                                <span class="mt-toggle" data-target="#<?= $mid ?>">&#9662;</span>
                                                <input type="checkbox" class="form-check-input mt-chk mt-m" id="<?= $mid ?>-c">
                                                <label for="<?= $mid ?>-c"><?= htmlspecialchars($m['display_name']) ?></label>
                                            </div>
                                            <div id="<?= $mid ?>" class="mt-kids">
                                                <?php foreach ($ps as $p): ?>
                                                <div class="mt-row mt-act">
                                                    <span class="mt-toggle empty">&#9662;</span>
                                                    <input type="checkbox" class="form-check-input mt-chk mt-a" data-p="<?= $p ?>"
                                                           id="<?= $mid ?>-<?= $p ?>"
                                                           name="permissions[<?= $m['id'] ?>][can_<?= $p ?>]" value="1"
                                                           <?= !empty($cur['can_'.$p]) ? 'checked' : '' ?>>
                                                    <label for="<?= $mid ?>-<?= $p ?>"><?= $lbl[$p] ?? ucfirst($p) ?></label>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url() ?>/roles" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check me-1"></i>Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function submitRole(event) {
    event.preventDefault();
    
    const form = document.getElementById('roleForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Updating...';
    submitBtn.disabled = true;
    
    $.ajax({
        url: '<?= base_url() ?>/roles/update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.indexOf('success') !== -1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Role updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '<?= base_url() ?>/roles';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: response
                });
                
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while updating the role'
            });
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Akses Web Montera Ganteng Check-All Functionality

</script>

<script>
(function(){
  var root=document.querySelector('.montera-tree'); if(!root) return;
  function kids(c){var n=c.closest('.mt-node'); return n?[].slice.call(n.querySelectorAll('.mt-chk')).filter(function(x){return x!==c;}):[];}
  function refresh(){
    ['.mt-m','.mt-g'].forEach(function(sel){
      root.querySelectorAll(sel).forEach(function(p){
        var ch=kids(p); if(!ch.length) return;
        var on=ch.filter(function(c){return c.checked;}).length;
        p.checked = on===ch.length; p.indeterminate = on>0 && on<ch.length;
      });
    });
  }
  root.addEventListener('click',function(e){
    var t=e.target.closest('.mt-toggle'); if(!t||!t.dataset.target) return;
    var k=root.querySelector(t.dataset.target); if(!k) return;
    k.classList.toggle('hide');
    t.textContent = k.classList.contains('hide') ? '\u25B8' : '\u25BE';
  });
  root.addEventListener('change',function(e){
    var c=e.target; if(!c.classList||!c.classList.contains('mt-chk')) return;
    if(c.classList.contains('mt-g')||c.classList.contains('mt-m')){
      kids(c).forEach(function(x){x.checked=c.checked;x.indeterminate=false;});
    }
    refresh();
  });
  // master "Pilih Semua Akses" + kolom All View/Create/Update/Delete/Approve
  document.querySelectorAll('input[type=checkbox]').forEach(function(a){
    if(root.contains(a)) return;
    a.addEventListener('change',function(){
      var only=a.dataset.permission;
      var sel = only ? '.mt-a[data-p="'+only+'"]' : '.mt-chk';
      root.querySelectorAll(sel).forEach(function(x){x.checked=a.checked;x.indeterminate=false;});
      refresh();
    });
  });
  refresh();
})();
</script>
