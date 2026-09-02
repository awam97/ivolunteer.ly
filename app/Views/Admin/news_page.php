<?php 
    $entity = $entities[0];
    $image_url = $filemodel->get_image_url('news', $entity->id);
    $fallback_image = base_url('uploads/placeholder_image.jpg');
    
    // Get category name
    $category_name = 'بلا تصنيف';
    if (!empty($entity->activity_id) && ($entity->activity_id > 0)) {
        $activity = $db->table('activities')->where('id', $entity->activity_id)->get()->getRow();
        if ($activity) {
            $category_name = $activity->name;
        }
    }
?>

<div class="news-detail-wrapper">
    <!-- Top Actions -->
    <div class="detail-actions-bar">
        <a href="<?= base_url('Admin/news'); ?>" class="btn-back">
            <i class="fa fa-arrow-right"></i>
            <span>العودة للأخبار</span>
        </a>
    </div>

    <!-- Article Content -->
    <article class="article-container">
        <!-- Hero Image -->
        <div class="article-hero">
            <img src="<?= esc($image_url ?: $fallback_image); ?>" class="hero-image" alt="<?= esc($entity->name); ?>" onerror="this.src='<?= $fallback_image ?>'">
        </div>

        <div class="article-content">
            <!-- Header -->
            <header class="article-header">
                <span class="article-category"><?= esc($category_name); ?></span>
                <h1 class="article-title"><?= esc($entity->name); ?></h1>
                
                <div class="article-meta">
                    <div class="meta-item">
                        <i class="fa-regular fa-calendar"></i>
                        <span><?= esc($entity->post_date); ?></span>
                    </div>
                </div>
            </header>

            <!-- Body -->
            <div class="article-body">
                <?= $entity->post_content; ?>
            </div>
        </div>
    </article>
</div>
