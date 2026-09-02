<?php
    $fileModel = new \App\Models\FileModel();
    $image_url = $fileModel->get_image_url($entityName, $entity->id);
    $fallback_image = 'https://portal.i-volunteer.ly/uploads/placeholder_image.jpg';
    $db = \Config\Database::connect();
?>

<?php if ($entityName == 'news'): ?>
    <div class="news-item entity-card-wrapper" data-id="<?= $entity->id; ?>">
        <article class="news-card-premium admin-card">
            <div class="card-selection-overlay">
                <label class="select-checkbox">
                    <input class="entity-checkbox" type="checkbox" name="entity" data-id="<?= $entity->id; ?>" value="<?= $entity->id; ?>">
                    <span></span>
                </label>
            </div>

            <div class="news-image-container">
                <img src="<?= esc($image_url ?: $fallback_image); ?>" class="news-img" alt="<?= $entity->name; ?>" onerror="this.src='<?= $fallback_image ?>'">
                <div class="glass-badge">
                    <i class="fa-solid fa-tag mr-1"></i> 
                    <?php 
                        if (!empty($entity->activity_id) && ($entity->activity_id > 0)) {
                            $activity = $db->table('activities')->where('id', $entity->activity_id)->get()->getRow();
                            echo $activity ? esc($activity->name) : 'بلا تصنيف';
                        } else {
                            echo 'بلا تصنيف';
                        }
                    ?>
                </div>
            </div>
            <div class="news-body">
                <div class="news-meta">
                    <span class="news-date">
                        <i class="fa-regular fa-calendar-days"></i> <?= date('Y/m/d', strtotime($entity->post_date ?? date('Y-m-d'))) ?>
                    </span>
                </div>
                <h3 class="news-headline searchable-title"><?= $entity->name; ?></h3>
                
                <div class="admin-actions-footer">
                    <button class="btn-admin-action edit" onclick="editEntity(<?= $entity->id; ?>)" title="تعديل">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>تعديل</span>
                    </button>
                    <button class="btn-admin-action delete btn-delete" data-id="<?= $entity->id; ?>" title="حذف">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>حذف</span>
                    </button>
                </div>
            </div>
        </article>
    </div>

<?php else: ?>
    <div class="<?= $entityName ?>-item entity-card-wrapper" data-id="<?= $entity->id; ?>">
        <div class="white-box table-box-items entity-card-content">  
            <div class="card-selection-overlay">
                <label class="select-checkbox">
                    <input class="entity-checkbox" type="checkbox" name="entity" data-id="<?= $entity->id; ?>" value="<?= $entity->id; ?>">
                    <span></span>
                </label>
            </div>
            
            <div class="entity-header-row">
                <div class="entity-avatar-container">
                    <img src="<?= esc($image_url ?: $fallback_image); ?>" class="entity-avatar" alt="<?= $entity->name; ?>" onerror="this.src='<?= $fallback_image ?>'">
                    <?php if($entityName == 'volunteers' && !empty($entity->academic_value)): ?>
                        <span class="academic-badge" title="<?= esc($entity->academic_value); ?>"><i class="fa-solid fa-graduation-cap"></i></span>
                    <?php endif; ?>
                </div>

                <div class="entity-info">                                               
                    <h2 class="searchable-title"><b><?= $entity->name; ?></b></h2>
                    
                    <?php if($entityName == 'volunteers'): ?>
                        <p class="entity-username text-muted">@<?= $entity->username; ?></p>
                        <div class="entity-meta-tags">
                            <span class="badge <?= $entity->gender == 1 ? 'badge-gender-male' : 'badge-gender-female'; ?>" title="<?= $entity->gender == 1 ? 'ذكر' : 'أنثى'; ?>">
                                <i class="fa-solid <?= $entity->gender == 1 ? 'fa-mars' : 'fa-venus'; ?>"></i>
                            </span>
                            <span class="badge badge-outline-info"><?= $entity->birthdate; ?></span>
                        </div>
                    <?php elseif($entityName == 'activities'): ?>
                        <div class="entity-meta">
                            <span class="activity-badge"><i class="fa-solid fa-location-dot"></i> <?= esc($entity->city_name ?? 'المدينة'); ?></span>
                            <span class="meta-date text-muted"><i class="fa-solid fa-calendar-days"></i> <?= esc($entity->date_from ?? 'التاريخ'); ?></span>
                        </div>
                    <?php elseif($entityName == 'cities'): ?>
                        <div class="entity-meta">
                            <span class="text-muted"><i class="fa-solid fa-map-location-dot"></i> رمز المدينة: <?= esc($entity->id); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="entity-actions">
                <?php if($entityName == 'volunteers'): ?>
                    <div class="contact-actions">
                        <a href="tel:<?= $entity->phone; ?>" class="btn btn-default btn-sm contact-link" title="<?= $entity->phone; ?>"><i class="fa-solid fa-phone"></i></a>
                        <a href="mailto:<?= $entity->email; ?>" class="btn btn-default btn-sm contact-link" title="<?= $entity->email; ?>"><i class="fa-solid fa-envelope"></i></a>
                        <a href="https://wa.me/<?= str_replace(['+', ' '], '', $entity->phone); ?>" target="_blank" class="btn btn-default btn-sm contact-link whatsapp" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                <?php endif; ?>
                
                <div class="management-actions">
                    <button class="btn btn-info btn-sm btn-edit" onclick="editEntity(<?= $entity->id; ?>)"><i class="fa-solid fa-pen-to-square"></i> <?= $translate->translate_phrase('edit',$language);?></button>
                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $entity->id; ?>"><i class="fa-solid fa-trash-can"></i> <?= $translate->translate_phrase('delete',$language);?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
