<?php 
    $folderPath = "uploads/news_files/";                
    $filePath = glob($folderPath . $news->id  . ".*");                 
    
    $fileUrl = 'https://portal.i-volunteer.ly/uploads/placeholder_image.jpg';
    if ($filePath) {
        $ext = pathinfo($filePath[0], PATHINFO_EXTENSION);
        $fileUrl = base_url($folderPath . $news->id . '.' . $ext);
    }

    // Robust Activity Fetching
    $activity = $db->table('activities')->where('id', $news->activity_id)->get()->getRow();
    $activity_name = $activity ? $activity->name : 'إعلان عام';
?>

<div class="news-detail-container fade-in-up">
    <!-- Back Header -->
    <div class="reading-toolbar">
        <a href="<?= base_url('Volunteer/dashboard') ?>" class="back-link">
            <i class="fa-solid fa-arrow-right"></i>
            <span>العودة للرئيسية</span>
        </a>
        <div class="reading-meta-top">
            <span class="meta-tag"><i class="fa-solid fa-tag"></i> <?= $activity_name ?></span>
        </div>
    </div>

    <!-- Article Content -->
    <article class="reading-article">
        <div class="article-hero-wrap">
            <img src="<?= $fileUrl ?>" class="article-hero-img" alt="<?= $news->name ?>">
            <div class="article-hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-date"><i class="fa-regular fa-calendar-days"></i> <?= date('Y/m/d', strtotime($news->post_date)) ?></span>
                <h1 class="hero-title"><?= $news->name ?></h1>
            </div>
        </div>

        <div class="article-body-context">
            <div class="article-main-text">
                <?= $news->post_content ?>
            </div>
            
            <footer class="article-end">
                <hr>
                <div class="end-brand">
                    <img src="https://i-volunteer.ly/wp-content/uploads/2023/05/logo-1.png" alt="I-Volunteer">
                    <p>فريق منصة أنا متطوع - معاً لنبني مستقبلاً أفضل.</p>
                </div>
            </footer>
        </div>
    </article>
</div>

<style>
    .news-detail-container {
        max-width: 1000px;
        margin: 0 auto;
        padding-bottom: 50px;
    }

    .reading-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 0 10px;
    }

    .back-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #304300;
        text-decoration: none;
        font-weight: 700;
        transition: transform 0.2s ease;
    }

    .back-link:hover {
        transform: translateX(-5px);
    }

    .meta-tag {
        background: rgba(48, 67, 0, 0.05);
        color: #304300;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .reading-article {
        background: var(--card-bg, #ffffff);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .article-hero-wrap {
        position: relative;
        height: 450px;
        width: 100%;
    }

    .article-hero-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .article-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0) 20%, rgba(0,0,0,0.8) 100%);
    }

    .hero-content {
        position: absolute;
        bottom: 40px;
        left: 40px;
        right: 40px;
        color: white;
    }

    .hero-date {
        font-size: 0.9rem;
        opacity: 0.9;
        display: block;
        margin-bottom: 10px;
    }

    .hero-title {
        font-size: 2.5rem;
        font-weight: 900;
        line-height: 1.3;
        margin: 0;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .article-body-context {
        padding: 40px 60px;
        color: #334155;
        font-size: 1.25rem;
        line-height: 1.9;
        text-align: justify;
    }

    .article-main-text {
        margin-bottom: 50px;
    }

    .article-end {
        margin-top: 50px;
        text-align: center;
    }

    .article-end hr {
        border-color: rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .end-brand img {
        height: 40px;
        filter: grayscale(1) opacity(0.5);
        margin-bottom: 15px;
    }

    .end-brand p {
        font-size: 0.9rem;
        color: #94a3b8;
    }

    @media (max-width: 768px) {
        .article-hero-wrap {
            height: 300px;
        }
        .hero-title {
            font-size: 1.75rem;
        }
        .hero-content {
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        .article-body-context {
            padding: 30px 20px;
            font-size: 1.1rem;
        }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>
