
<div class="white-box">    
    <?php foreach ($entities as $entity): ?>        
        <table style="text-align:center;width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>تاريخ بدء النشاط</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->date_from; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>تاريخ انتهاء النشاط</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->date_to; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>المدينة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $db->table('cities')->where('id',$entity->city_id)->get()->getRow()->name; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>المؤسسة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->organisation; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>وصف النشاط</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->description; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>المتطلبات للمشاركة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->required_files; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالمواصلات</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->transportation == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالإقامة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->residency == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                            <tr>
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالإعاشة</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->expenses == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                            <tr style="">
                                <td style="background-color: #f2f2f2;padding: 10px; border: 1px solid #ddd;"><b>التكفل بالتدريب</b></td>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $entity->training == 0 ? 'لا' : 'نعم'; ?></td>
                            </tr>
                        </table>
    <?php endforeach; ?>
</div>  
