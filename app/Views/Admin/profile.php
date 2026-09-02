<?php
    $fileModel = new \App\Models\FileModel();
    $image_url = $fileModel->get_image_url($entityName, $admin_id);
    $fallback_image = 'https://portal.i-volunteer.ly/uploads/placeholder_image.jpg';
?>

<div class="profile-page-container">
    <div class="profile-header-banner"></div>

    <div class="profile-dashboard-wrapper">
        <!-- Left Sidebar: Profile Summary Overlap -->
        <aside class="profile-sidebar">
            <div class="profile-card-summary">
                <div class="profile-avatar-wrapper">
                    <img id="profilePreview" src="<?= esc($image_url ?: $fallback_image); ?>" alt="Profile Picture">
                    <div class="profile-avatar-overlay" onclick="document.getElementById('<?= $entityName.'_file_X' ?>').click()">
                        <i class="fa-solid fa-camera fa-2x mb-2"></i>
                        <span>تغيير الصورة</span>
                    </div>
                </div>
                <h1 class="profile-name-title" id="summaryName">جارِ التحميل...</h1>
                <span class="profile-role-badge">
                    <i class="fa-solid fa-shield-halved"></i> 
                    <?= ($entityName == 'admin') ? 'مدير النظام' : 'مستخدم'; ?>
                </span>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <i class="fa-solid fa-calendar-star"></i>
                        <span>تاريخ الانضمام: <?= date('Y/m/d'); ?></span>
                    </div>
                    <div class="stat-item">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span id="summaryEmail">...</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Right Content: Grouped Settings -->
        <main class="profile-main-content">
            <form id="<?= $entityName;?>Edit">
                <!-- Section 1: Personal Info -->
                <div class="settings-card">
                    <div class="section-header">
                        <i class="fa-solid fa-address-card"></i>
                        <h2>البيانات الأساسية</h2>
                    </div>
                    <div class="form-grid-premium">                                                       
                        <?php foreach ($entityData as $field => $attributes): ?>
                            <?php if ($attributes['type'] !== 'password' && $attributes['type'] !== 'file' && $field !== 'file'): ?>
                                <div class="form-group" id="<?= $entityName .'_' . $field . '_container' ?>">  
                                    <label><?= $translate->translate_phrase($attributes['placeholder'],$language) ?></label>
                                    <?php 
                                        switch ($attributes['type']) {
                                            case 'select':
                                                echo '<select class="form-control" id="' . $entityName .'_' .$attributes['id'].'_X' . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                foreach ($attributes['options'] as $value => $label) { echo '<option value="' . $value . '">' . $label . '</option>'; }
                                                echo '</select>';
                                                break;
                                            case 'textarea':
                                                echo '<textarea class="form-control" id="' . $entityName .'_' .$attributes['id'].'_X' . '" name="' . $field . '" placeholder="' . $translate->translate_phrase($attributes['placeholder'],$language) . '" ' . ($attributes['required'] ? 'required' : '') . '></textarea>';
                                                break;
                                            case 'file': break; // Handled separately
                                            default:
                                                echo '<input type="' . $attributes['type'] . '" class="form-control" id="' . $entityName .'_' .$attributes['id'].'_X' . '" name="' . $field . '" placeholder="' . $translate->translate_phrase($attributes['placeholder'],$language) . '" ' . ($attributes['required'] ? 'required' : '') . ' ' . (isset($attributes['accept']) ? 'accept="' . $attributes['accept'] . '"' : '') . '>';
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <!-- Hidden File Input for Avatar Overlap Trigger -->
                        <input type="file" style="display:none" id="<?= $entityName ?>_file_X" name="file" accept="image/*">
                    </div>
                    <div class="card-footer-actions">
                        <button type="button" id="saveProfileInfo" class="premium-save-btn">
                            <i class="fa-solid fa-floppy-disk"></i> حفظ البيانات الأساسية
                        </button>
                    </div>
                </div>

                <!-- Section 2: Security & Account -->
                <div class="settings-card">
                    <div class="section-header">
                        <i class="fa-solid fa-key"></i>
                        <h2>الأمان والحساب</h2>
                    </div>
                    <div class="form-grid-premium">
                        <?php foreach ($entityData as $field => $attributes): ?>
                            <?php if ($attributes['type'] === 'password'): ?>
                                <div class="form-group">
                                    <label><?= $translate->translate_phrase('new-password',$language) ?></label>
                                    <input type="password" class="form-control" id="<?= $entityName .'_' .$attributes['id'].'_X' ?>" name="<?= $field ?>" placeholder="<?= $translate->translate_phrase('new-password',$language) ?>">
                                </div>
                                <div class="form-group">
                                    <label><?= $translate->translate_phrase('repeat-password',$language) ?></label>
                                    <input type="password" class="form-control" id="repeat_password" placeholder="<?= $translate->translate_phrase('repeat-password',$language) ?>">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer-actions">
                        <button type="button" id="updatePassword" class="premium-save-btn">
                            <i class="fa-solid fa-shield-check"></i> تحديث كلمة المرور
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
    $(document).ready(function () 
    {        
        const entityName = '<?= $entityName; ?>';  
        const entityId = '<?= $admin_id; ?>';  

        // SAVE PROFILE INFORMATION
        $('#saveProfileInfo').on('click', function () {
            const btn = $(this);
            let formData = new FormData();
            formData.append('table', entityName);
            formData.append('id_entity', entityId);

            // Collect only non-password fields
            $(`#${entityName}Edit [id*="${entityName}"]`).each(function () {
                const fieldName = $(this).attr('name');
                if (fieldName && fieldName !== 'password') {
                    formData.append(fieldName, $(this).val());
                }
            });

            // Handle file upload if any
            const fileInput = document.getElementById(`${entityName}_file_X`);
            if (fileInput && fileInput.files[0]) {
                formData.append('file', fileInput.files[0]);
            }

            updateProfile(formData, btn);
        });

        // UPDATE PASSWORD
        $('#updatePassword').on('click', function () {
            const btn = $(this);
            const password = $(`#${entityName}_password_X`).val();
            const repeatPassword = $('#repeat_password').val();

            if (password === '') {
                showNotification('error', 'يرجى إدخال كلمة المرور الجديدة أولاً.');
                return;
            }
            
            if (password !== repeatPassword) {
                showNotification('error', 'عذراً، كلمات المرور غير متطابقة.');
                return;
            }

            let formData = new FormData();
            formData.append('table', entityName);
            formData.append('id_entity', entityId);
            formData.append('password', password);

            updateProfile(formData, btn);
        });

        function updateProfile(formData, btn) {
            const originalHtml = btn.html();
            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('Admin/update_post_entity') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.status === 'success') {
                        showNotification('success', response.message || 'تم تحديث البيانات بنجاح');
                        // Update UI Summary if needed
                        $('#summaryName').text($(`#${entityName}_name_X`).val());
                        $('#summaryEmail').text($(`#${entityName}_email_X`).val());
                        
                        // Update Preview if file was selected
                        const fileInput = document.getElementById(`${entityName}_file_X`);
                        if (fileInput && fileInput.files[0]) {
                            const reader = new FileReader();
                            reader.onload = e => $('#profilePreview').attr('src', e.target.result);
                            reader.readAsDataURL(fileInput.files[0]);
                        }
                    } else {
                        showNotification('error', response.message || 'حدث خطأ أثناء التحديث');
                    }
                },
                error: function () {
                    showNotification('error', 'فشل الاتصال بالخادم');
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        }

        // --- Data Fetching & Sync ---
        $.ajax({
            url: `<?= base_url("Admin/data_grap") ?>`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                table: entityName,
                id_entity: entityId
            }),
            success: function(response) {
                if (response.status === 'success') {                                        
                    $.each(response.data, function(field, value) {                                                
                        const fieldId = `<?= $entityName; ?>_${field}_X`;                                                  
                        const element = $(`#${fieldId}`);                    
                        if (element.length) {
                            if (element.is('input, textarea')) {   
                                if (element.attr('type') === 'password') {
                                    element.val('');
                                } else {
                                    element.val(value);
                                }
                            } else if (element.is('select')) {                                
                                element.val(value).change();
                            }
                        }

                        // Sync Summary
                        if (field === 'name') $('#summaryName').text(value);
                        if (field === 'email') $('#summaryEmail').text(value);
                    });             
                }
            }
        });

        // Image Preview Handler
        $(`#<?= $entityName.'_file_X' ?>`).on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    $('#profilePreview').attr('src', event.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>