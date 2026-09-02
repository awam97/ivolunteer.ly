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
                            <form id="newsAdd">
                                <div class="row">                                                       
                                    <div class="form-group">                                                       
                                        <?php foreach ($entityData as $field => $attributes): ?>
                                            <div class="<?php echo $attributes['class_id'] ;?>">  
                                                <label for="<?= $attributes['id'] ?>"><b><?= $attributes['placeholder'] ?></b></label>
                                                <?php                                             
                                                    switch ($attributes['type']) {
                                                        case 'text': case 'date': case 'file':
                                                            echo '<input type="' . $attributes['type'] . '" class="form-control" id="' . $attributes['id'] . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                            break;
                                                        case 'select':
                                                            echo '<select class="form-control" id="' . $attributes['id'] . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                            foreach ($attributes['options'] as $value => $label) echo '<option value="' . $value . '">' . $label . '</option>';
                                                            echo '</select>';
                                                            break;
                                                        case 'textarea':
                                                            echo '<textarea class="form-control tinymce-enabled" id="' . $attributes['id'] . '" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '></textarea>';
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
                            <h2><b>تعديل خبر</b></h2>
                            <hr>
                            <form id="newsEdit">
                                <div class="row">
                                    <div class="form-group">
                                        <?php foreach ($entityData as $field => $attributes): ?>
                                            <div class="<?php echo $attributes['class_id'] ;?>" id="news_<?= $field ?>_panel_X">
                                                <label for="news_<?= $attributes['id'] ?>_X"><b><?= $attributes['placeholder'] ?></b></label>
                                                <?php
                                                switch ($attributes['type']) {
                                                    case 'text': case 'date': case 'file':
                                                        echo '<input type="' . $attributes['type'] . '" class="form-control" id="news_' . $attributes['id'] . '_X" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                        break;
                                                    case 'select':
                                                        echo '<select class="form-control" id="news_' . $attributes['id'] . '_X" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '>';
                                                        foreach ($attributes['options'] as $value => $label) echo '<option value="' . $value . '">' . $label . '</option>';
                                                        echo '</select>';
                                                        break;
                                                    case 'textarea':
                                                        echo '<textarea class="form-control tinymce-enabled" id="news_' . $attributes['id'] . '_X" name="' . $field . '" ' . ($attributes['required'] ? 'required' : '') . '></textarea>';
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
            const entityName = 'news';                
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
                if (!confirm(`هل أنت متأكد أنك تريد حذف هذا الخبر؟`)) return;
                $.ajax({
                    url: `<?= base_url("Admin/delete_entity") ?>`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ table: entityName, id_entity: entityId }),
                    success: function (response) {
                        if (response.status === 'success') {
                            $(`.entity-card-wrapper[data-id="${entityId}"]`).fadeOut(400, function() { $(this).remove(); });
                            window.showNotification('success', 'تم حذف الخبر بنجاح');
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
            $(document).on('submit', '#newsEdit', function (e) {
                e.preventDefault();
                // CRITICAL: Synchronize TinyMCE editors before serialization
                if (typeof tinymce !== 'undefined') tinymce.triggerSave();

                let formData = new FormData(this);

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


            // AJAX Add Modal logic
            window.openAddModal = function() {
                const formHtml = $('#tab2').html();
                $('#modalTitle').text('إضافة خبر جديد');
                $('#modalBody').html(formHtml);
                
                // Initialize TinyMCE for textareas in the newly opened modal
                if (window.initTinyMCE) window.initTinyMCE('textarea');
                
                $('#managementModal').fadeIn().css('display', 'flex');

                $('body').addClass('modal-open');
                
                $('#newsAdd').on('submit', function(e) {
                    e.preventDefault();
                    let formData = new FormData(this);
                    const fileInput = $(this).find('input[type="file"]')[0];
                    if (fileInput && fileInput.files.length > 0) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            
                            // CRITICAL: Synchronize TinyMCE editors before serialization
                            if (typeof tinymce !== 'undefined') tinymce.triggerSave();

                            const data = {};
                            $(e.target).serializeArray().forEach(item => data[item.name] = item.value);

                            data.file = event.target.result;
                            $.ajax({
                                url: '<?= base_url("Admin/add_entity") ?>',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ table: entityName, fields_entity: data }),
                                success: function(res) { 
                                    if (res.status === 'success') {
                                        $('#gridContainerOne').prepend(res.rendered_html);
                                        $('#managementModal').fadeOut();
                                        window.showNotification('success', 'تمت إضافة الخبر بنجاح');
                                    } else window.showNotification('error', res.message || 'Error'); 
                                }
                            });
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    } else {
                        // CRITICAL: Synchronize TinyMCE editors before serialization
                        if (typeof tinymce !== 'undefined') tinymce.triggerSave();

                        const data = {};
                        $(this).serializeArray().forEach(item => data[item.name] = item.value);

                        $.ajax({
                            url: '<?= base_url("Admin/add_entity") ?>',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ table: entityName, fields_entity: data }),
                            success: function(res) { 
                                if (res.status === 'success') {
                                    $('#gridContainerOne').prepend(res.rendered_html);
                                    $('#managementModal').fadeOut();
                                    window.showNotification('success', 'تمت إضافة الخبر بنجاح');
                                } else window.showNotification('error', res.message || 'Error'); 
                            }
                        });
                    }
                });
            };
        });

        function editEntity(id) {
            window.editEntityId = id;

            // Inject Edit Form Template into Modal Body
            const editFormHtml = $('#tab3').html();
            $('#modalTitle').text('تعديل الخبر');
            $('#modalBody').html(editFormHtml);

            $.ajax({
                url: `<?= base_url("Admin/data_grap") ?>`,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ table: 'news', id_entity: id }),
                success: function (response) {
                    if (response.status === 'success') {
                        $.each(response.data, function (field, value) {
                            const element = $(`#modalBody #news_${field}_X`);
                            if (!element.is('input[type="file"]')) {
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
                        
                        // CRITICAL: Initialize TinyMCE ONLY AFTER values are set so it picks them up
                        if (window.initTinyMCE) window.initTinyMCE('textarea');

                        $('#managementModal').fadeIn().css('display', 'flex');
                        $('body').addClass('modal-open');
                    }
                }
            });
        }
    </script>
<?php ;};?>
