<?php if (!empty($loginType)): ?>
    <div class="redirect-dashboard" style="position: fixed; top: 100px; right: 30px; z-index: 1000;">        
        <a href="<?= base_url($loginType . '/dashboard'); ?>" class="btn btn-danger" style="border-radius: 12px; padding: 12px 25px; font-weight: 800; box-shadow: var(--shadow-lg);">
            <i class="fa-solid fa-gauge-high"></i> لوحة التحكم
        </a>
    </div>
<?php endif; ?>

<?php include(APPPATH . 'Views/generalheader.php'); ?> 

<?php
    $folderPath = "uploads/news_files/";                
    $fullPath = FCPATH . $folderPath;
    $filePath = glob($fullPath . $entities->id . ".*");
    $defaultImage = 'placeholder_image.jpg';
    
    if ($filePath && file_exists($filePath[0])) {
        $fileName = basename($filePath[0]);
        $fileUrl = base_url($folderPath . $fileName);
    } else {
        $fileUrl = base_url('uploads/' . $defaultImage);
    }
?>

<div class="news-article-card">
    <div class="news-header">
        <span class="news-tag">أخبار المتطوعين</span>
        <h1 class="news-title-primary"><?= $entities->name; ?></h1>
        <div class="news-meta-header">
            <span><i class="fa-solid fa-calendar-day"></i> نشر بتاريخ: <?= $entities->post_date ?? 'بدون تاريخ'; ?></span>
            <span><i class="fa-solid fa-user-pen"></i> بواسطة: الإدارة العامة</span>
        </div>
    </div>

    <div class="news-thumbnail-wrapper">
        <img src="<?= $fileUrl; ?>" class="news-thumbnail" alt="<?= $entities->name; ?>">
    </div>

    <div class="news-body">
        <?= $entities->post_content; ?>
    </div>

    <div class="news-footer-actions">
        <div style="font-weight: 800; color: var(--text-muted);">شارك هذا الخبر:</div>
        <div class="share-group">
            <a href="#" class="share-btn" title="فيسبوك"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" class="share-btn" title="تويتر"><i class="fa-brands fa-twitter"></i></a>
            <a href="#" class="share-btn" title="واتساب"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="#" class="share-btn" title="نسخ الرابط"><i class="fa-solid fa-link"></i></a>
        </div>
    </div>
</div>
