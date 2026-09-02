<?php if($db->table('volunteer_activities')->where('id',$id)->get()->getRow()->status==2){;?>

<style> 
    .certificate 
    {
        padding:0px !important;
        margin:0px !important;
        width: 100% !important;
        height: 100% !important;                                                               
        background-image: url('https://portal.i-volunteer.ly/uploads/certificate.png') !important;
        background-size: cover !important;                                       
    }

    .header
    {
        padding-right:30px !important;
        text-align:right !important;
        color:white !important;
    }

    .content
    {
        padding-right:90px !important;
        padding-left:130px !important;
        text-align:center !important;            
    }

    .logos
    {
        padding-left:30px !important;
        text-align:left !important;            
    }

    table
    {
        width:90% !important;
        font-size:24px !important;
        font-weight:bold !important;
        text-align:center !important;
    }

    td
    {       
        font-size:20px !important;
        font-weight:bold !important;
        text-align:center !important;
    }

    .header
    {            
        padding-top:8px !important;               
    }

    .header h1
    {
        color:white !important;
        font-weight:bold !important;      
        font-size:35px !important;               
    }
    
    .header h3
    {
        color:white !important;
        font-weight:bold !important;       
        font-size:25px !important;                            
    }

    @media print {
        @page {
            size: A4 landscape !important;
            margin: 0 !important
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important; /* Prevents scrollbars */
        }

        .certificate {
            width: 100% !important;
            height: 100% !important;
        }

        .content, .header, .logos {
            margin: 0 auto;
            padding: 0 auto;
        }
    }

</style>
<?php foreach ($entities as $entity) :;?>
    <div class="certificate">           
        <div class="header">
            <h3>دولــــة لــيبيـــا</h3>
            <h1>شهادة مشاركة في نشاط تطوعي</h1>
        </div>   
        <div class="logos">
            <?php echo $barcode ;?>
            <img width="100px" style="border-radius:50%;" src="https://portal.i-volunteer.ly/uploads/vwyo.png"></img>
            <img width="110px" src="https://portal.i-volunteer.ly/uploads/logo-color-1.png"></img>
        </div>                  
        <center><h1><b><?php echo $db->table('volunteers')->where('id',$entity->volunteer_id)->get()->getRow()->name;?><b></h1></center>
        <hr>
        <div class="content">
            <center><h2><b>            
    تشهد إدارة منصة أنا متطوع و منظمة شباب العمل التطوعي بأن المتطوع/ـة المذكور أسمه/هـا أعلاه قد شارك/ت في <b><?php echo $db->table('activities')->where('id',$entity->activity_id)->get()->getRow()->name;?><b>
    في الفترة من <b><?php echo $db->table('activities')->where('id',$entity->activity_id)->get()->getRow()->date_from;?><b>  إلى  <b><?php echo $db->table('activities')->where('id',$entity->activity_id)->get()->getRow()->date_to;?><b>
    <br>بإجمالي عدد ساعات تطوع : <b><?php echo $db->table('activities')->where('id',$entity->activity_id)->get()->getRow()->hours;?> ساعات<b>
    <br><br>بارك الله جهودكم و جزاكم عنا خير الجزاء
            <b></h2></center>
        </div>
        <br>
        <br>
        
        
        <table>
            <tbody>
                <tr>
                    <td>
                        منصة أنا متطوع                        
                    </td>
                    <td>منظمة شباب العمل التطوعي</td>
                </tr>   
                <tr>
                    <td style="vertical-align:top !important">                        
                        <img width="120px" src="https://portal.i-volunteer.ly/uploads/logo-stamp.png"></img>
                    </td>     
                    <td style="vertical-align:top !important">
                        <img width="240px" src="https://portal.i-volunteer.ly/uploads/vwyo-stamp.png"></img>
                    </td>               
                </tr>                                                    
            </tbody>
        </table>
    </div>
<?php endforeach;};?>
<script>
    window.onload = function () {
        window.print();
    };
</script>
