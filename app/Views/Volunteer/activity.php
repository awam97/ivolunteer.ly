<?php $isRegistered = $db->table('volunteer_activities')->where('volunteer_id', $volunteer_id)->where('activity_id', $id)->countAllResults() ;?>

<div class="row">
    <div class="col-md-12">
    <div class="login-panel">
            <div class="page-title-box-lite">
                <div class="box-title-lite">
                    <div class="row">  
                        <div class="col-md-9 col-xs-8">  
                            <h2> 
                                <b>
                                    <?php 
                                    if ($isRegistered > 0) {
                                        $status = $db->table('volunteer_activities')->where('volunteer_id', $volunteer_id)->where('activity_id', $id)->get()->getRow()->status;
                                        echo $status == 0 ? 'قيد المراجعة' : ($status == 1 ? 'تمت الموافقة' : 'تم الانجاز');
                                    }
                                    ?>
                                </b>
                            </h2>
                        </div>                      
                        <div class="col-md-3 col-xs-4">
                            <?php if($isRegistered == 0){;?>
                                <button type="button" class="btn btn-info btn-lg" data-volunteer-id="<?= $volunteer_id ?>" data-activity-id="<?= $id ?>" onclick="registerVolunteer(this)">سجل في النشاط</button>                                 
                            <?php ;}else{;?>
                                <button type="submit" class="btn btn-info btn-lg" data-volunteer-id="<?= $volunteer_id ?>" data-activity-id="<?= $id ?>" onclick="deleteVolunteer(this)">الانسحاب من النشاط</button>                                                    
                            <?php ;};?>
                        </div>
                    </div>           
                </div>  
            </div>
            <div class="white-box">
                <div class="row">
                    <div class="col-md-6">
                        <img style="border-radius:20px; width:100%" src="https://portal.i-volunteer.ly/uploads/activities_files/<?= $id; ?>.png"> 
                    </div>
                    <div class="col-md-6">                        
                        <table style="text-align:center;width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>تاريخ بدء النشاط</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('date_from')->where('id', $id)->get()->getRow()->date_from; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>تاريخ انتهاء النشاط</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('date_to')->where('id', $id)->get()->getRow()->date_to; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>المدينة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('cities')->select('name')->where('id', $db->table('activities')->select('city_id')->where('id', $id)->get()->getRow()->city_id)->get()->getRow()->name; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>المؤسسة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('organisation')->where('id', $id)->get()->getRow()->organisation; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>وصف النشاط</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('description')->where('id', $id)->get()->getRow()->description; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>المتطلبات للمشاركة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('required_files')->where('id', $id)->get()->getRow()->required_files; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالسفر</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('transportation')->where('id', $id)->get()->getRow()->transportation == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالإقامة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('residency')->where('id', $id)->get()->getRow()->residency == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالإعاشة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('expenses')->where('id', $id)->get()->getRow()->expenses == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                            <tr style="">
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالتدريب</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('activities')->select('training')->where('id', $id)->get()->getRow()->training == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function deleteVolunteer(button) 
    {
        const entityName = '<?php echo $entityName; ?>'; // Ensure this PHP variable is correctly set
        const volunteerId = button.getAttribute('data-volunteer-id');
        const entityId = '<?php echo $id; ?>'; 

        if (!confirm(`هل أنت متأكد أنك تريد إلفاء تسجيلك في النشاط؟`)) return;

        // First AJAX request: Delete the entity
        $.ajax({
            url: `<?= base_url("Volunteer/delete_entity") ?>`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                table: entityName,
                id_entity: entityId,
                conditions: {
                    volunteer_id: volunteerId
                }
            }),
            success: function (response) {
                if (response.status === 'success') {
                    // Second AJAX request: Send unenrollment notification
                    $.ajax({
                        url: `<?= base_url("Volunteer/activity_unenroll_notification") ?>`,
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            volunteer_id: volunteerId,
                            activity_id: entityId // Assuming `entityId` represents the activity ID
                        }),
                        success: function (notificationResponse) {
                            if (notificationResponse.status === 'success') {
                                alert('تم إرسال إشعار إلغاء التسجيل بنجاح.');
                            } 
                            location.reload(); // Reload the page after the second request
                        },
                        error: function (xhr) {
                            location.reload(); // Reload even if the notification fails
                        }
                    });
                } else {
                    alert('حدث خطأ أثناء الحذف: ' + response.message);
                }
            },
            error: function (xhr) {
                alert(`حدث خطأ: ${xhr.responseJSON?.message || 'حاول مرة أخرى.'}`);
            }
        });
    }



    function registerVolunteer(button) {
        const table_name = '<?php echo $entityName;?>';
        const volunteerId = button.getAttribute('data-volunteer-id');
        const activityId = button.getAttribute('data-activity-id');

        if (!volunteerId || !activityId) {
            alert('Missing volunteer or activity ID.');
            return;
        }

        fetch('<?= base_url('Volunteer/add_entity') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                table_name: table_name,
                fields_entity: {
                    volunteer_id: volunteerId,
                    activity_id: activityId
                }
            })
        })
        
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Make the second AJAX request to the Volunteers controller
                return fetch('<?= base_url('Volunteer/activity_enroll_notification') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        volunteer_id: volunteerId,
                        activity_id: activityId
                    })
                });
                
            } else {
                throw new Error('Error: ' + data.message);
            }
        })
        .then(response => response.json())
        .then(secondData => {
            if (secondData.status === 'success') {
                alert('Second request completed: ' + secondData.message);
            } else {
                alert('Error in second request: ' + secondData.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            location.reload();
        });
    }

</script>
