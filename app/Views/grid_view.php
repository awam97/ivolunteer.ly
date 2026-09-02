<?php include(APPPATH . 'Views/generalheader.php'); ?> 
<br>
<hr>
<h2><b>جميع النشاطات المتاحة</b></h2>
<br>
<div class="row">
    <?php foreach ($entities as $entity): ?>
        <?php 
        // Retrieve the city name for the current entity (if available)
        $city = array_filter($cities, function($c) use ($entity) {
            return $c['id'] == $entity->city_id;
        });
        $cityName = !empty($city) ? reset($city)['name'] : 'غير معروف'; 
        ?>
        <div class="col-md-4 col-xs-6 <?= $entityName; ?>-item" data-id="<?= $entity->id; ?>" city-id="<?= $entity->city_id; ?>" city-name="<?= $cityName; ?>">
            <div class="white-box table-box-items-no-padding">                         
                <div class="table-box-top" style="border-radius: 20px 20px 0px 0px; background: linear-gradient(to bottom, rgba(48, 67, 0, 0.7), rgba(255, 255, 255, 1)), url(https://portal.i-volunteer.ly/uploads/activities_files/<?= $entity->id; ?>.png) no-repeat center center;
                    background-size: cover; position: relative; height: 120px; color: white; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
                    display: flex;">                    
                    <span style="margin-left: 10px;margin-right: 10px"><?= $entity->organisation; ?></span>
                </div>
                <div class="table-box-bottom">
                    <center>                                                                 
                        <h4><b><?= $entity->name; ?></b></h4> 
                        <h5><?= $cityName; ?></h5>
                        <hr>                               
                        <a href="#tab3" class="btn btn-info" data-toggle="tab">تفاصيل</a>
                        <a class="btn btn-danger" data-id="<?= $entity->id; ?>">المفضلة</a>
                    </center>
                </div>
            </div>
        </div>
    <?php endforeach; ?>   
</div>
<br><br>
