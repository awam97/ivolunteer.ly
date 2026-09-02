<?php $volunteer_city = $db->table('volunteers')->where('id', $volunteer_id)->get()->getRow()->city_id;?>

<div class="dashboard-stats-grid">
    <!-- Available Activities -->
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite">النشاطات المتاحة</div>           
            <div class="counter-icon"><i class="fa-solid fa-calendar-plus"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter">
                <?php echo $db->table('activities')
                    ->select('activities.*')
                    ->join('volunteer_activities', 'volunteer_activities.activity_id = activities.id AND volunteer_activities.volunteer_id = ' . $volunteer_id, 'left')
                    ->where('activities.date_from>', date("Y/m/d"))
                    ->where('volunteer_activities.activity_id IS NULL')
                    ->countAllResults(); ?>
            </div>
        </div>
    </div>

    <!-- My Activities -->
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite">نشاطاتك</div>           
            <div class="counter-icon"><i class="fa-solid fa-list-check"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter">
                <?php echo $db->table('volunteer_activities')->where('volunteer_id', $volunteer_id)->countAllResults(); ?>
            </div>
        </div>
    </div>

    <!-- Volunteer Hours -->
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite">ساعاتك التطوعية</div>           
            <div class="counter-icon"><i class="fa-solid fa-user-clock"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter">
                <?php 
                    $total_hours = $db->table('activities')
                        ->selectSum('activities.hours', 'total_hours')
                        ->join('volunteer_activities', 'volunteer_activities.activity_id = activities.id')
                        ->where('volunteer_activities.volunteer_id', $volunteer_id)
                        ->where('volunteer_activities.status', '2')
                        ->get()->getRow();
                    echo ($total_hours && $total_hours->total_hours !== null) ? $total_hours->total_hours : "0";
                ?>
            </div>
        </div>
    </div>

    <!-- Certificates -->
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite">شهاداتك</div>           
            <div class="counter-icon"><i class="fa-solid fa-award"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter">
                <?php echo $db->table('volunteer_activities')->where('volunteer_id', $volunteer_id)->where('status', '2')->countAllResults(); ?>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in-up" style="margin-top: 2rem;">
    <div class="col-md-12">
        <div class="counter-panel">
            <div class="section-headline-wrapper">
                <div class="section-headline">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>آخر الأخبار والإعلانات</span>
                </div>
                <a href="<?= base_url('Volunteer/news') ?>" class="view-all-btn">
                    <span>عرض الكل</span>
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>
            <div class="white-box" style="padding: 25px;">
                <div class="row">
                    <?php 
                        $news = $db->table('news')
                            ->select('id as news_id, name, post_date, activity_id')
                            ->orderBy('post_date', 'DESC')
                            ->limit(3)
                            ->get()->getResult();
                        
                        if (empty($news)) : 
                    ?>
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

                            // Robust Activity Fetching
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
</div>

<style>
    .news-library-wrapper {
        margin-top: 1rem;
    }
</style>
