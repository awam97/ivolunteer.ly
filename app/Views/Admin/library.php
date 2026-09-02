<?php if (!isset($hidden)|| (isset($hidden) && $hidden == 1) ){?>
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

    <div class="view-mode-container library-modern-view view-grid">
        <div class="tab-content">
            <div id="tab1" class="tab-pane fade in active">
                <div class="row grid-container" id="mediaLibraryGrid">
                    <?php foreach ($entities as $entity): ?>
                        <div class="library-item media-item entity-card-wrapper" data-id="<?= $entity->id; ?>">
                            <div class="white-box table-box-items entity-card-content">  
                                <div class="card-selection-overlay">
                                    <label class="select-checkbox">
                                        <input class="entity-checkbox" type="checkbox" name="entity" data-id="<?= $entity->id; ?>" value="<?= $entity->id; ?>">
                                        <span></span>
                                    </label>
                                </div>
                                
                                <div class="media-container-wrapper">
                                    <?php
                                    $uploadDirDisk = FCPATH . 'uploads/' . $entityName . '_files/';
                                    $uploadDirUrl = base_url() . 'uploads/' . $entityName . '_files/';
                                    $placeholderImage = 'https://portal.i-volunteer.ly/uploads/placeholder_image.jpg';
                                    $placeholderPDF = 'https://portal.i-volunteer.ly/uploads/placeholder_pdf.jpg';
                                    
                                    // Search for actual file on disk
                                    $files = glob($uploadDirDisk . $entity->id . ".*");
                                    $displayPath = $placeholderImage;
                                    $actualPath = "";
                                    $isImage = false;

                                    if (!empty($files)) {
                                        $fileName = basename($files[0]);
                                        $actualPath = $uploadDirUrl . $fileName;
                                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        
                                        if (in_array($fileExt, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
                                            $displayPath = $actualPath;
                                            $isImage = true;
                                        } else if ($fileExt === 'pdf') {
                                            $displayPath = $placeholderPDF;
                                        }
                                    }
                                    ?>
                                    <div class="media-thumbnail-wrapper <?= $isImage ? 'is-image' : 'is-doc'; ?>">
                                        <img src="<?= esc($displayPath); ?>" class="media-thumbnail" alt="<?= $entity->name ?? 'Media'; ?>" onerror="this.src='<?= $placeholderImage; ?>'">
                                    </div>
                                    
                                    <div class="media-hover-overlay">
                                        <div class="media-meta">
                                            <p class="searchable-title"><b><?= esc($entity->filename); ?></b></p>
                                        </div>
                                        <div class="media-quick-actions">
                                            <?php if($actualPath): ?>
                                                <a href="<?= esc($actualPath); ?>" class="btn-media-view" target="_blank" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                            <?php endif; ?>
                                            <button class="btn-media-delete btn-delete" data-id="<?= $entity->id; ?>" title="حذف"><i class="fa-solid fa-trash-can"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                <div class="white-box">                    
                    <form id="libraryAdd">
                        <div class="row">                                                       
                            <div class="form-group col-md-12">                                                       
                                <label for="file"><b>اختر ملف الوسائط</b></label>
                                <input type="file" class="form-control" id="file" name="file" required>
                                <p class="text-muted" style="margin-top:10px;">يمكنك تحميل الصور (JPG, PNG) أو ملفات PDF.</p>
                            </div>                   
                            <div class="col-md-12" style="margin-top: 20px;">
                                <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fa-solid fa-cloud-arrow-up"></i> تحميل الآن</button>                                                                                                                                      
                            </div>
                        </div>
                    </form>                                        
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () 
        {
            const entityName = 'library';                
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
                if (!confirm(`هل أنت متأكد أنك تريد حذف هذا الملف؟`)) return;
                $.ajax({
                    url: `<?= base_url("Admin/delete_entity") ?>`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ table: 'library', id_entity: entityId }),
                    success: function (response) {
                        if (response.status === 'success') {
                            $(`.entity-card-wrapper[data-id="${entityId}"]`).fadeOut(400, function() { $(this).remove(); });
                            window.showNotification('success', 'تم حذف الملف بنجاح');
                        }
                        else window.showNotification('error', 'حدث خطأ أثناء الحذف.');
                    }
                });
            });

            //Bulk Delete
            $('#bulkDeleteBtn').on('click', function () {
                if (!selectAllMode && selectedEntities.length === 0) return alert('يرجى تحديد العناصر المراد حذفها.');
                const confirmMsg = selectAllMode ? 'هل أنت متأكد أنك تريد حذف جميع السجلات؟' : 'هل أنت متأكد أنك تريد حذف الملفات المختارة؟';
                if (!confirm(confirmMsg)) return;
                
                $.ajax({
                    url: `<?= base_url("Admin/bulk_delete") ?>`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ 
                        table: 'library', 
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

            // AJAX Add Modal logic
            window.openAddModal = function() {
                const formHtml = $('#tab2').html();
                $('#modalTitle').text('تحميل وسائط جديدة');
                $('#modalBody').html(formHtml);
                $('#managementModal').fadeIn().css('display', 'flex');
                $('body').addClass('modal-open');
                
                $('#libraryAdd').on('submit', function(e) {
                    e.preventDefault();
                    let formData = new FormData(this);
                    const fileInput = $(this).find('input[type="file"]')[0];
                    if (fileInput && fileInput.files.length > 0) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const data = {};
                            data.file = event.target.result;
                            data.filename = fileInput.files[0].name;
                            $.ajax({
                                url: '<?= base_url("Admin/add_entity") ?>',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ table: 'library', fields_entity: data }),
                                success: function(res) { 
                                if (res.status === 'success') {
                                    $('#gridContainerOne').prepend(res.rendered_html);
                                    $('#managementModal').fadeOut();
                                    window.showNotification('success', 'تم تحميل الوسائط بنجاح');
                                } else window.showNotification('error', 'Error'); 
                            }
                            });
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                });
            };
        });
    </script>
<?php ;};?>
