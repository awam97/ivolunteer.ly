<?php include(APPPATH . 'Views/generalheader.php'); ?> 
<br>
<div class="grid-controls-wrapper">
    <div class="filter-row">
        <div class="filter-group">
            <label class="filter-label">فرز حسب</label>
            <select id="sortBy" class="filter-control">
                <option value="name">الاسم (أبجدي)</option>
                <option value="date">التاريخ (الأحدث)</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">الترتيب</label>
            <select id="sortOrder" class="filter-control">
                <option value="desc">تنازلي</option>
                <option value="asc">تصاعدي</option>
            </select>
        </div>
        <div class="filter-group" style="flex: 2;">
            <label class="filter-label">بحث سريع</label>
            <div class="search-input-wrapper">
                <input type="text" id="<?= $entityName; ?>Search" class="filter-control" placeholder="اكتب للبحث عن نشاط أو خبر...">
                <i class="fa-solid fa-magnifying-glass search-icon" style="left: auto; right: 15px;"></i>
            </div>
        </div>
        <div class="filter-group" style="min-width: auto; flex: 0;">
            <label class="filter-label">العرض</label>
            <div class="view-toggle-group" style="display: flex; gap: 5px;">
                <button id="btnGrid" class="btn btn-secondary active" style="height: 48px; border-radius: 12px;"><i class="fa-solid fa-border-all"></i></button>
                <button id="btnList" class="btn btn-secondary" style="height: 48px; border-radius: 12px;"><i class="fa-solid fa-list"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="page-header-box" style="margin-bottom: 30px;">
    <h2 class="box-title"><?= $page_title;?></h2>
    <p class="text-muted" style="margin-top: 5px;">
        <i class="fa-solid fa-location-dot"></i> بمدينة <b><?= $cityName;?></b> 
        <?php if(!empty($searchKey)): ?>
            | نتائج البحث عن: <b>"<?= $searchKey;?>"</b>
        <?php endif; ?>
    </p>
</div>

<div id="tab1">
    <div id="dataContainer" class="view-grid">
        <?php foreach ($entities as $entity): ?>
        <?php
            $folderPath = "uploads/{$entityName}_files/";                
            $fullPath = FCPATH . $folderPath;
            $filePath = glob($fullPath . $entity->id . ".*");
            $defaultImage = 'placeholder_image.jpg'; // Using an existing placeholder
            
            if ($filePath && file_exists($filePath[0])) {
                $fileName = basename($filePath[0]);
                $fileUrl = base_url($folderPath . $fileName);
            } else {
                $fileUrl = base_url('uploads/' . $defaultImage);
            }
            
            $entityCityName = 'غير معروف';
            if(!empty($entity->city_id)){
                $city = array_filter($cities, function($c) use ($entity) {return $c->id == $entity->city_id;});
                $entityCityName = !empty($city) ? reset($city)->name : 'غير معروف';
            }
        ?>
        <div class="<?= $entityName; ?>-item data-card" 
             data-id="<?= $entity->id; ?>" 
             data-name="<?= $entity->name; ?>"
             data-date="<?= $entity->date_from ?? $entity->post_date ?? ''; ?>">
            
            <div class="card-image-wrapper">
                <img src="<?= $fileUrl; ?>" class="card-image" alt="<?= $entity->name; ?>">
                <?php if(!empty($entity->organisation)): ?>
                    <div class="card-badge"><?= $entity->organisation; ?></div>
                <?php endif; ?>
            </div>

            <div class="card-content">
                <h3 class="card-title"><?= $entity->name; ?></h3>
                
                <div class="card-meta">
                    <div class="meta-item">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span><?= $entity->date_from ?? $entity->post_date ?? 'بدون تاريخ'; ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?= $entityCityName; ?></span>
                    </div>
                    <?php if(!empty($entity->admin_id)): ?>
                        <div class="meta-item">
                            <i class="fa-solid fa-user-pen"></i>
                            <span>بواسطة الإدارة</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <a href="<?= base_url('Home/' . $details); ?>?id=<?= $entity->id; ?>" class="btn-details" target="_blank">
                        التفاصيل <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>   
    </div>
</div>
<br><br>
<script>
    $(document).ready(function () 
    {
        const entityName = '<?= $entityName; ?>';                    

        // Search Logic
        $(`#${entityName}Search`).on('input', function () 
        {                        
            const query = $(this).val().toLowerCase();
            $(`.${entityName}-item`).each(function () {
                const itemName = $(this).data('name').toLowerCase();
                $(this).toggle(itemName.includes(query));
            });
        });        
        
        // Sorting Logic
        $('#sortBy, #sortOrder').on('change', function () 
        {
            const sortBy = $('#sortBy').val();
            const sortOrder = $('#sortOrder').val();
            let items = $(`.${entityName}-item`).toArray();

            items.sort(function (a, b) {
                let aValue, bValue;

                if (sortBy === 'name') {
                    aValue = $(a).data('name').toLowerCase();
                    bValue = $(b).data('name').toLowerCase();
                } else {
                    aValue = new Date($(a).data('date'));
                    bValue = new Date($(b).data('date'));
                }

                if (aValue < bValue) return (sortOrder === 'asc' ? -1 : 1);
                if (aValue > bValue) return (sortOrder === 'asc' ? 1 : -1);
                return 0;
            });            
            
            $('#dataContainer').append(items);
        });        
        
        // View Toggle Logic
        const btnGrid = $('#btnGrid');
        const btnList = $('#btnList');
        const dataContainer = $('#dataContainer');
        const currentView = localStorage.getItem('viewMode') || 'grid';
        
        function setViewMode(mode) {
            if (mode === 'list') {
                dataContainer.removeClass('view-grid').addClass('view-list');
                btnList.addClass('active');
                btnGrid.removeClass('active');
                localStorage.setItem('viewMode', 'list');
            } else {
                dataContainer.removeClass('view-list').addClass('view-grid');
                btnGrid.addClass('active');
                btnList.removeClass('active');
                localStorage.setItem('viewMode', 'grid');
            }
        }
        
        setViewMode(currentView);
        
        btnGrid.on('click', () => setViewMode('grid'));
        btnList.on('click', () => setViewMode('list'));
    });        
</script>
