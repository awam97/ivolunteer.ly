<?php $activity_id = $db->table('activities')->where('id',$id)->get()->getRow()->id; ;?>
<style>
    @media print {
        @page {size: landscape};
            
        div {
            break-inside: none;
        }
           
        .breaked
        {
            page-break-after: always;
            margin-bottom:8px;
            padding:8px;
            height:100%;
            border:1px solid black;
            vertical-align:middle !important;
        }
    }
</style>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<body dir="rtl"> 
    <div class="breaked" dir="rtl" style="border:none;text-align:center;padding:13px">
        <div dir="rtl" class="col-xs-12">
            <table style="font-size:12px;border-collapse: collapse;border:1px solid black;text-align:center" width="100%">
                <thead>
                    <tr>
                        <th colspan="12" style="padding:10px;text-align:center">                            
                            <img width="100px" src="https://portal.i-volunteer.ly/uploads/logo-color-1.png"></img>
                            <br>
                            <h4><b>كشف بالمتطوعين المسجلين بالنشاط التطوعي : <?php echo $db->table('activities')->where('id',$activity_id)->get()->getRow()->name; ;?></b></h4>
                        </th>                            
                    </tr>
                    <tr class="theader" style="background-color: #f4f4f4;height:28px">
                        <th style="width:3%;text-align:center;border-collapse: collapse;border:1px solid black">م</th>
                        <th style="width:15%;text-align:center;border-collapse: collapse;border:1px solid black">إسم المتطوع</th>
                        <th style="width:5%;text-align:center;border-collapse: collapse;border:1px solid black">المدينة</th>
                        <th style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black">التعريف الشخصي</th>
                        <th style="width:3%;text-align:center;border-collapse: collapse;border:1px solid black">الجنس</th>
                        <th style="width:5%;text-align:center;border-collapse: collapse;border:1px solid black">تاريخ الميلاد</th>
                        <th style="width:2%;text-align:center;border-collapse: collapse;border:1px solid black">رقم الهاتف</th>
                        <th style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black">البريد الإلكتروني</th>
                        <th style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black">المؤهل العلمي</th>
                        <th style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black">الهوايات</th>
                    </tr>
                </thead>
                <tbody>    
                <?php $i=1;foreach ($entities as $entity) :;?>                    
                    <tr class="theader" style="background-color: #f4f4f4;height:28px">
                        <td style="width:3%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $i;?></td>
                        <td style="width:15%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->name;?></td>
                        <td style="width:5%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('cities')->where('id',$db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->city_id)->get()->getRow()->name;?></td>
                        <td style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->identity;?></td>
                        <td style="width:3%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('genders')->where('id',$db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->gender)->get()->getRow()->name;?></td>
                        <td style="width:5%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->birthdate;?></td>
                        <td style="width:2%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->phone;?></td>
                        <td style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->email;?></td>
                        <td style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->academic_value;?></td>
                        <td style="width:8%;text-align:center;border-collapse: collapse;border:1px solid black"><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->hobbies;?></td>
                    </tr>
                    <?php $i++;endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
</body>

<script>
    window.onload = function () {
        window.print();
    };
</script>