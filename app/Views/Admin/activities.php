<?php if (!isset($hidden)|| (isset($hidden) && $hidden == 1) ){;?>
    <!-- Bulk Action Toolbar -->
    <div id="bulkActionsToolbar" class="bulk-actions-toolbar" style="display: none;">
        <div class="container-fluid">
            <div class="row" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 30px;">
                <div class="selection-info">
                    <span id="selectedCount">0</span> عناصر مختارة
                </div>
                <div class="bulk-buttons">
                    <button id="bulkDeleteBtn" class="btn btn-danger"><i class="fa fa-trash"></i> حذف المحدد</button>
                    <button id="cancelSelectionBtn" class="btn btn-default">إلغاء</button>
                </div>
            </div>
        </div>
    </div>

    <div class="view-mode-container view-grid">
        <div class="tab-content">
            <div id="tab1" class="tab-pane fade in active">
                <div class="row grid-container" id="gridContainerOne">
                    <?php foreach ($entities as $entity): ?>
                        <?php include(APPPATH . 'Views/Admin/partials/entity_card.php'); ?>
                    <?php endforeach; ?>   
                </div>
                <?php if (isset($pager)): ?>
                    <div class="pagination-container">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hidden Template for Add Modal -->
            <div id="tab2" style="display: none;">
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">                    
                            <form id="activitiesAdd">
                                <div class="row">                                                       
                                    <div class="form-group">                                                       
                                        <?php foreach ($entityData as $field => $attributes): ?>
                                            <div class="<?php echo $attributes['class_id'] ;?>">  
                                                <?php                                             
                                                    echo '<label for="' . $attributes['id'] . '"><b>' . $attributes['placeholder'] . '</b></label>';
                                                    switch ($attributes['type']) {
                                                        case 'text': case 'date': case 'email': case 'password': case 'file': case 'phone':
                                                            echo '<input type="' . $attributes['type'] . '" class="form-control" id="' . $attributes['id'] . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                            break;
                                                        case 'select':
                                                            echo '<select class="form-control" id="' . $attributes['id'] . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                            foreach ($attributes['options'] as $value => $label) echo '<option value="' . $value . '">' . $label . '</option>';
                                                            echo '</select>';
                                                            break;
                                                        case 'textarea':
                                                            echo '<textarea class="form-control" id="' . $attributes['id'] . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '></textarea>';
                                                            break;
                                                        case 'radio':
                                                            echo '<div class="radio-toggle-group">';
                                                            echo '  <label class="test-toggle-label">';
                                                            echo '    <input type="radio" name="' . $field . '" value="1" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                            echo '    <span class="toggle-btn">نعم</span>';
                                                            echo '  </label>';
                                                            echo '  <label class="test-toggle-label">';
                                                            echo '    <input type="radio" name="' . $field . '" value="0" checked>';
                                                            echo '    <span class="toggle-btn">لا</span>';
                                                            echo '  </label>';
                                                            echo '</div>';
                                                            break;
                                                    }
                                                ?>
                                            </div>                   
                                        <?php endforeach; ?>    
                                        <div class="col-md-12" style="margin-top: 20px;">
                                            <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fa-solid fa-floppy-disk"></i> <?= $translate->translate_phrase('save',$language);?></button>                                                                                                                                      
                                        </div>
                                    </div>                             
                                </div>
                            </form>                                        
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="tab3" class="tab-pane fade">
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">                    
                            <h2><b>تعديل نشاط</b></h2>
                            <hr>
                            <form id="activitiesEdit">
                                <div class="row">
                                    <div class="form-group">
                                        <?php foreach ($entityData as $field => $attributes): ?>
                                            <div class="<?php echo $attributes['class_id'] ;?>" id="activities_<?= $field ?>_panel_X">
                                                <label for="activities_<?= $attributes['id'] ?>_X"><b><?= $attributes['placeholder'] ?></b></label>
                                                <?php
                                                switch ($attributes['type']) {
                                                    case 'text': case 'date': case 'email': case 'password': case 'file': case 'phone':
                                                        echo '<input type="' . $attributes['type'] . '" class="form-control" id="activities_' . $attributes['id'] . '_X" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                        break;
                                                    case 'select':
                                                        echo '<select class="form-control" id="activities_' . $attributes['id'] . '_X" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                        foreach ($attributes['options'] as $value => $label) echo '<option value="' . $value . '">' . $label . '</option>';
                                                        echo '</select>';
                                                        break;
                                                    case 'textarea':
                                                        echo '<textarea class="form-control" id="activities_' . $attributes['id'] . '_X" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '></textarea>';
                                                        break;
                                                    case 'radio':
                                                        echo '<div class="radio-toggle-group">';
                                                        echo '  <label class="test-toggle-label">';
                                                        echo '    <input type="radio" name="' . $field . '" value="1" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                        echo '    <span class="toggle-btn">نعم</span>';
                                                        echo '  </label>';
                                                        echo '  <label class="test-toggle-label">';
                                                        echo '    <input type="radio" name="' . $field . '" value="0">';
                                                        echo '    <span class="toggle-btn">لا</span>';
                                                        echo '  </label>';
                                                        echo '</div>';
                                                        break;
                                                }
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <button type="submit" class="col-md-12 col-xs-12 btn btn-info btn-lg"><i class="fa-solid fa-floppy-disk"></i> <?= $translate->translate_phrase('save',$language);?></button>
                                    </div>
                                </div>
                            </form>                                          
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () 
        {
            const entityName = 'activities';                
            let selectAllMode = false;
            let selectedEntities = [];

            function updateToolbar() {
                const count = selectAllMode ? 'الكل' : selectedEntities.length;
                $('#selectedCount').text(count);
                if (selectedEntities.length > 0 || selectAllMode) $('#bulkActionsToolbar').fadeIn();
                else {
                    $('#bulkActionsToolbar').fadeOut();
                    $('#selectAllBanner').hide();
                    selectAllMode = false;
                }

                // Show "Select All across pages" banner if all on-page items are checked
                const totalOnPage = $('.entity-checkbox').length;
                if (selectedEntities.length === totalOnPage && totalOnPage > 0 && !selectAllMode) {
                    $('#selectAllBanner').css('display', 'flex');
                } else if (!selectAllMode) {
                    $('#selectAllBanner').hide();
                }
            }

            $('.entity-checkbox').on('change', function () {
                const entityId = $(this).data('id').toString();
                if (this.checked) { if (!selectedEntities.includes(entityId)) selectedEntities.push(entityId); }
                else { selectedEntities = selectedEntities.filter(id => id !== entityId); selectAllMode = false; }
                updateToolbar();
            });

            window.select_all = function(forceState) {
                const checkboxes = $('.entity-checkbox');
                const newState = (forceState !== undefined) ? forceState : !checkboxes.first().prop('checked');
                checkboxes.prop('checked', newState);
                selectedEntities = newState ? checkboxes.map(function() { return $(this).data('id').toString(); }).get() : [];
                if (!newState) selectAllMode = false;
                updateToolbar();
            };

            $('#selectAllRecordsBtn').on('click', function() {
                selectAllMode = true;
                $('#selectionHint').text('تم اختيار جميع السجلات في النظام.');
                $(this).hide();
                updateToolbar();
            });

            $('#cancelSelectionBtn').on('click', function() {
                $('.entity-checkbox').prop('checked', false);
                selectedEntities = [];
                selectAllMode = false;
                $('#selectionHint').text('تم اختيار كافة العناصر في هذه الصفحة.');
                $('#selectAllRecordsBtn').show();
                updateToolbar();
            });

            //Delete
            $('.btn-delete').on('click', function () {            
                const entityId = $(this).data('id');            
                if (!confirm(`هل أنت متأكد أنك تريد حذف هذا النشاط؟`)) return;
                $.ajax({
                    url: `<?= base_url("Admin/delete_entity") ?>`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ table: entityName, id_entity: entityId }),
                    success: function (response) {
                        if (response.status === 'success') {
                            $(`.entity-card-wrapper[data-id="${entityId}"]`).fadeOut(400, function() { $(this).remove(); });
                            window.showNotification('success', 'تم حذف النشاط بنجاح');
                        }
                        else window.showNotification('error', 'حدث خطأ أثناء الحذف.');
                    }
                });
            });

            //Bulk Delete
            $('#bulkDeleteBtn').on('click', function () {
                if (!selectAllMode && selectedEntities.length === 0) return alert('يرجى تحديد العناصر المراد حذفها.');
                const confirmMsg = selectAllMode ? 'هل أنت متأكد أنك تريد حذف جميع السجلات؟' : 'هل أنت متأكد أنك تريد حذف العناصر المختارة؟';
                if (!confirm(confirmMsg)) return;
                
                $.ajax({
                    url: `<?= base_url("Admin/bulk_delete") ?>`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ 
                        table: entityName, 
                        ids: selectedEntities,
                        selectAll: selectAllMode,
                        search: $('#globalSearch').val()
                    }),
                    success: function (response) {
                        if (response.status === 'success') {
                            if (selectAllMode) location.reload(); 
                            else {
                                selectedEntities.forEach(id => {
                                    $(`.entity-card-wrapper[data-id="${id}"]`).fadeOut(400, function() { $(this).remove(); });
                                });
                                selectedEntities = [];
                                updateToolbar();
                                window.showNotification('success', 'تم حذف العناصر المختارة بنجاح');
                            }
                        }
                        else window.showNotification('error', 'حدث خطأ أثناء الحذف: ' + (response.message || ''));
                    }
                });
            });

            // Edit Submission (Use delegation for modal injection support)
            $(document).on('submit', '#activitiesEdit', function (e) {
                e.preventDefault();
                
                // CRITICAL: Synchronize TinyMCE editors before serialization
                if (typeof tinymce !== 'undefined') tinymce.triggerSave();

                let formData = new FormData(this);
                formData.append('table', entityName);
                formData.append('id_entity', window.editEntityId);

                $.ajax({
                    url: '<?= base_url("Admin/update_post_entity") ?>',
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function (response) {
                        if (response.status === 'success') {
                            $(`.entity-card-wrapper[data-id="${window.editEntityId}"]`).replaceWith(response.rendered_html);
                            $('#managementModal').fadeOut();
                            window.showNotification('success', 'تم تحديث البيانات بنجاح');
                        }
                        else window.showNotification('error', response.message);
                    }
                });
            });

            // AJAX Add Submission Handler (Delegated for SPA)
            $(document).on('submit', '#activitiesAdd', function(e) {
                e.preventDefault();
                
                // CRITICAL: Synchronize TinyMCE editors before serialization
                if (typeof tinymce !== 'undefined') tinymce.triggerSave();

                let formData = new FormData(this);
                const fileInput = $(this).find('input[type="file"]')[0];
                
                if (fileInput && fileInput.files.length > 0) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const data = {};
                        $(e.target).serializeArray().forEach(item => data[item.name] = item.value);
                        data.file = event.target.result;
                        
                        const insert_notifications = data.insert_notifications;
                        delete data.insert_notifications;

                        $.ajax({
                            url: '<?= base_url("Admin/add_entity") ?>',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ 
                                table: entityName, 
                                fields_entity: data,
                                insert_notifications: insert_notifications,
                                notification_type: 'activities'
                            }),
                            success: function(res) { 
                                if (res.status === 'success') {
                                    $('#gridContainerOne').prepend(res.rendered_html);
                                    $('#managementModal').fadeOut();
                                    window.showNotification('success', 'تمت إضافة النشاط بنجاح');
                                } else window.showNotification('error', res.message || 'Error'); 
                            }
                        });
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                } else {
                    const data = {};
                    $(this).serializeArray().forEach(item => data[item.name] = item.value);
                    
                    const insert_notifications = data.insert_notifications;
                    delete data.insert_notifications;

                    $.ajax({

                        url: '<?= base_url("Admin/add_entity") ?>',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ 
                            table: entityName, 
                            fields_entity: data,
                            insert_notifications: insert_notifications,
                            notification_type: 'activities'
                        }),
                        success: function(res) { 
                            if (res.status === 'success') {
                                $('#gridContainerOne').prepend(res.rendered_html);
                                window.showNotification('success', 'تمت إضافة النشاط بنجاح');
                                $('#managementModal').fadeOut();
                            } else window.showNotification('error', res.message || 'Error'); 
                        }
                    });
                }

            });
        });

        // Global Modal Triggers
        function openAddModal() {
            const formHtml = $('#tab2').html();
            $('#modalTitle').text('إضافة نشاط جديد');
            $('#modalBody').html(formHtml);
            $('#managementModal').fadeIn().css('display', 'flex');
            $('body').addClass('modal-open');
            
            // Initialize TinyMCE for textareas in the newly opened modal
            if (window.initTinyMCE) window.initTinyMCE('textarea');
        }

        function editEntity(id) {
            window.editEntityId = id;

            // Inject Edit Form Template into Modal Body
            const editFormHtml = $('#tab3').html();
            $('#modalTitle').text('تعديل النشاط');
            $('#modalBody').html(editFormHtml);

            $.ajax({
                url: `<?= base_url("Admin/data_grap") ?>`,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ table: 'activities', id_entity: id }),
                success: function (response) {
                    try {
                        if (response.status === 'success') {
                            // Populate form inside modal
                            $.each(response.data, function (field, value) {
                                // 1. Try finding by activities_ prefix (Standard fields)
                                const element = $(`#modalBody #activities_${field}_X`);
                                
                                // 2. Handle Radio Buttons (By Name)
                                const radioInput = $(`#modalBody input[name="${field}"][type="radio"]`);
                                if (radioInput.length > 0) {
                                    $(`#modalBody input[name="${field}"][value="${value}"]`).prop('checked', true);
                                } 
                                // 3. Handle Regular Inputs (including dates)
                                else if (element.length > 0 && !element.is('input[type="file"]')) {
                                    // Reformat DD-MM-YYYY to YYYY-MM-DD for HTML5 date inputs
                                    if (element.attr('type') === 'date' && value && value.includes('-')) {
                                        const parts = value.split('-');
                                        if (parts.length === 3 && parts[0].length === 2) {
                                            value = `${parts[2]}-${parts[1]}-${parts[0]}`;
                                        }
                                    }
                                    element.val(value);
                                }

                            });
                            
                            // CRITICAL: Initialize TinyMCE AFTER values are set so it picks them up
                            if (window.initTinyMCE) window.initTinyMCE('textarea');
                            
                            $('#managementModal').fadeIn().css('display', 'flex');
                            $('body').addClass('modal-open');
                        } else {
                            window.showNotification('error', 'حدث خطأ في تحميل البيانات');
                        }
                    } catch (e) {
                        console.error('Populate Error:', e);
                        // Show modal anyway if possible, or show error
                        $('#managementModal').fadeIn().css('display', 'flex');
                    }
                },
                error: function() {
                    window.showNotification('error', 'فشل الاتصال بالخادم');
                }
            });
        }
    </script>
    <style>
        .radio-toggle-group {
            display: flex;
            gap: 10px;
            margin-top: 5px;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            width: fit-content;
        }

        .test-toggle-label {
            margin-bottom: 0;
            display: block;
        }

        .test-toggle-label input[type="radio"] {
            display: none;
        }

        .test-toggle-label .toggle-btn {
            display: block;
            padding: 8px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-bottom: 0;
            user-select: none;
            color: var(--text-muted);
            font-size: 13px;
        }

        .test-toggle-label input[type="radio"]:checked + .toggle-btn {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(48, 67, 0, 0.2);
        }

        /* Gray color for "No" when selected */
        .test-toggle-label input[value="0"]:checked + .toggle-btn {
            background: #cbd5e1;
            color: #475569;
            box-shadow: none;
        }
    </style>
<?php ;};?>
