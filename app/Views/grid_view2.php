<div class="slider-container">
    <?php foreach ($entities as $entity): ?>
        <?php
            $folderPath = "uploads/{$entityName}_files/";                
            $fullPath = FCPATH . $folderPath;
            $filePath = glob($fullPath . $entity->id . ".*");
            $defaultImage = 'placeholder_image.jpg';
            
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
        <div class="slider-item-wrapper">
            <div class="data-card" style="margin: 0;">
                <div class="card-image-wrapper">
                    <img src="<?= $fileUrl; ?>" class="card-image" alt="<?= $entity->name; ?>">
                    <?php if(!empty($entity->organisation)): ?>
                        <div class="card-badge"><?= $entity->organisation; ?></div>
                    <?php endif; ?>
                </div>

                <div class="card-content">
                    <h3 class="card-title"><?= $entity->name; ?></h3>
                    
                    <div class="card-meta">
                        <div class="meta-item" style="font-size: 12px;">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span><?= $entity->date_from ?? $entity->post_date ?? 'بدون تاريخ'; ?></span>
                        </div>
                        <div class="meta-item" style="font-size: 12px;">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?= $entityCityName; ?></span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="<?= base_url('Home/' . $details); ?>?id=<?= $entity->id; ?>" class="btn-details" target="_blank">
                            التفاصيل <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>   
</div>
<br>
<br><br>
