<?php
    $folderPath = "uploads/{$entityName}_files/";                
    $fullPath = FCPATH . $folderPath;
    $filePath = glob($fullPath . $entities->id . ".*");
    $defaultImage = 'placeholder_image.jpg';
    
    if ($filePath && file_exists($filePath[0])) {
        $fileName = basename($filePath[0]);
        $fileUrl = base_url($folderPath . $fileName);
    } else {
        $fileUrl = base_url('uploads/' . $defaultImage);
    }

    $cityName = $db->table('cities')->where('id', $entities->city_id)->get()->getRow()->name ?? 'غير معروف';
?>

<div class="activity-detail-card">
    <div class="activity-hero">
        <img src="<?= $fileUrl; ?>" class="activity-hero-img" alt="<?= $entities->name; ?>">
        <div class="activity-hero-overlay">
            <h1 class="activity-title-main"><?= $entities->name; ?></h1>
        </div>
    </div>

    <div class="activity-info-grid">
        <div class="info-box">
            <div class="info-icon"><i class="fa-solid fa-calendar-plus"></i></div>
            <span class="info-label">تاريخ البدء</span>
            <span class="info-value"><?= $entities->date_from; ?></span>
        </div>
        <div class="info-box">
            <div class="info-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <span class="info-label">تاريخ الانتهاء</span>
            <span class="info-value"><?= $entities->date_to; ?></span>
        </div>
        <div class="info-box">
            <div class="info-icon"><i class="fa-solid fa-city"></i></div>
            <span class="info-label">المدينة</span>
            <span class="info-value"><?= $cityName; ?></span>
        </div>
        <div class="info-box">
            <div class="info-icon"><i class="fa-solid fa-building"></i></div>
            <span class="info-label">المؤسسة المنظمة</span>
            <span class="info-value"><?= $entities->organisation; ?></span>
        </div>
    </div>

    <div class="activity-description">
        <h2 class="section-title"><i class="fa-solid fa-file-lines"></i> عن النشاط</h2>
        <div class="description-content">
            <?= $entities->description; ?>
        </div>

        <?php if(!empty($entities->required_files)): ?>
            <h2 class="section-title" style="margin-top: 40px;"><i class="fa-solid fa-clipboard-list"></i> متطلبات المشاركة</h2>
            <div class="description-content">
                <?= $entities->required_files; ?>
            </div>
        <?php endif; ?>

        <h2 class="section-title" style="margin-top: 40px;"><i class="fa-solid fa-star"></i> المميزات والتكفل</h2>
        <div class="benefits-row">
            <div class="benefit-tag <?= $entities->transportation == 1 ? 'yes' : 'no' ?>">
                <i class="fa-solid <?= $entities->transportation == 1 ? 'fa-check' : 'fa-xmark' ?>"></i> التكفل بالمواصلات
            </div>
            <div class="benefit-tag <?= $entities->residency == 1 ? 'yes' : 'no' ?>">
                <i class="fa-solid <?= $entities->residency == 1 ? 'fa-check' : 'fa-xmark' ?>"></i> التكفل بالإقامة
            </div>
            <div class="benefit-tag <?= $entities->expenses == 1 ? 'yes' : 'no' ?>">
                <i class="fa-solid <?= $entities->expenses == 1 ? 'fa-check' : 'fa-xmark' ?>"></i> التكفل بالإعاشة
            </div>
            <div class="benefit-tag <?= $entities->training == 1 ? 'yes' : 'no' ?>">
                <i class="fa-solid <?= $entities->training == 1 ? 'fa-check' : 'fa-xmark' ?>"></i> التكفل بالتدريب
            </div>
        </div>
    </div>
</div>
