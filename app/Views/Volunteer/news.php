<div class="news-library-wrapper fade-in-up">
    <div class="counter-panel">
        <div class="white-box" style="padding: 25px;">
            <div class="row">
                <?php if (empty($news)) : ?>
                    <div class="col-md-12">
                        <div class="empty-state-container">
                            <i class="fa-solid fa-newspaper empty-state-icon"></i>
                            <h3 class="empty-state-title">لا توجد أخبار جديدة حالياً</h3>
                            <p class="empty-state-desc">سنقوم بإشعارك فور صدور أي إعلانات أو أخبار جديدة تتعلق بالنشاطات التطوعية.</p>
                        </div>
                    </div>
                <?php else : ?>
                    <?php foreach ($news as $item) : 
                        $folderPath = "uploads/news_files/";                
                        $filePath = glob($folderPath . $item->news_id  . ".*");                 
                        
                        $fileUrl = 'https://portal.i-volunteer.ly/uploads/placeholder_image.jpg';
                        if ($filePath) {
                            $ext = pathinfo($filePath[0], PATHINFO_EXTENSION);
                            $fileUrl = base_url($folderPath . $item->news_id . '.' . $ext);
                        }

                        $activity = $db->table('activities')->where('id', $item->activity_id)->get()->getRow();
                        $activity_name = $activity ? $activity->name : 'إعلان عام';
                    ?>
                        <div class="col-md-4 mb-4">
                            <article class="news-card-premium" onclick="window.location.href='<?= base_url('Volunteer/news_page?id=' . $item->news_id) ?>'">
                                <div class="news-image-container">
                                    <img src="<?= $fileUrl ?>" class="news-img" alt="<?= $item->name ?>">
                                    <div class="glass-badge">
                                        <i class="fa-solid fa-tag mr-1"></i> <?= $activity_name ?>
                                    </div>
                                </div>
                                <div class="news-body">
                                    <div class="news-meta">
                                        <span class="news-date">
                                            <i class="fa-regular fa-calendar-days"></i> <?= date('Y/m/d', strtotime($item->post_date)) ?>
                                        </span>
                                    </div>
                                    <h3 class="news-headline"><a href="<?= base_url('Volunteer/news_page?id=' . $item->news_id) ?>" style="color: inherit; text-decoration: none;"><?= $item->name ?></a></h3>
                                    <div class="news-footer">
                                        <a href="<?= base_url('Volunteer/news_page?id=' . $item->news_id) ?>" class="news-action">
                                            <span>استمر في القراءة</span>
                                            <div class="action-circle"><i class="fa-solid fa-arrow-left"></i></div>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .news-library-wrapper {
        margin-top: 1rem;
    }
</style>
