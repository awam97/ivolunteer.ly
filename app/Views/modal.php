    <script type="text/javascript">
	function showAjaxModal(url)
	{
		jQuery('#modal_ajax .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="assets/images/preloader.gif" /></div>');
		jQuery('#modal_ajax').modal('show', {backdrop: 'true'});
		$.ajax({
			url: url,
			success: function(response)
			{
				jQuery('#modal_ajax .modal-body').html(response);
                  $(".mydatepicker").datepicker({
                    dateFormat: 'dd-mm-yy'
                })
			}
		});
	}
	</script>
    
    <div class="modal fade" id="modal_ajax">
        <div class="modal-dialog">
            <div class="modal-content">            
                <div class="modal-body" style="height:500px; overflow:auto;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('إغلاق'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
	function confirm_modal(delete_url)
	{
		jQuery('#modal-4').modal('show', {backdrop: 'static'});
		document.getElementById('delete_link').setAttribute('href' , delete_url);
	}
	</script>

    <script type="text/javascript">
	function confirm_modal_approve(approve_url)
	{
		jQuery('#modal-5').modal('show', {backdrop: 'static'});
		document.getElementById('approve_link').setAttribute('href' , approve_url);
	}
	</script>
	
    <script type="text/javascript">
	function confirm_modal_disapprove(disapprove_url)
	{
		jQuery('#modal-6').modal('show', {backdrop: 'static'});
		document.getElementById('disapprove_link').setAttribute('href' , disapprove_url);
	}
	</script>
	
    <script type="text/javascript">
	function confirm_modal_approve_exam(approve_url)
	{
		jQuery('#modal-7').modal('show', {backdrop: 'static'});
		document.getElementById('approve_exam_link').setAttribute('href' , approve_url);
	}
	</script>	
		
    <script type="text/javascript">
	function confirm_modal_disapprove_exam(disapprove_url)
	{
		jQuery('#modal-8').modal('show', {backdrop: 'static'});
		document.getElementById('disapprove_exam_link').setAttribute('href' , disapprove_url);
	}
	</script>
	
    <script type="text/javascript">
	function confirm_modal_approve_news(approve_url)
	{
		jQuery('#modal-9').modal('show', {backdrop: 'static'});
		document.getElementById('approve_news_link').setAttribute('href' , approve_url);
	}
	</script>	
		
    <script type="text/javascript">
	function confirm_modal_disapprove_news(disapprove_url)
	{
		jQuery('#modal-10').modal('show', {backdrop: 'static'});
		document.getElementById('disapprove_news_link').setAttribute('href' , disapprove_url);
	}
	</script>	

    <div class="modal fade" id="modal-4">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="delete_link">حذف</a>
                    <button type="button" class="btn btn-info" data-dismiss="modal">إلغاء</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modal-5">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="approve_link"><?php echo get_phrase('تفعيل'); ?></a>
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('الغاء'); ?></button>
                </div>
            </div>
        </div>
    </div>    
    
    <div class="modal fade" id="modal-6">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="disapprove_link"><?php echo get_phrase('تعطيل'); ?></a>
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('الغاء'); ?></button>
                </div>
            </div>
        </div>
    </div>   
    
    <div class="modal fade" id="modal-7">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="approve_exam_link"><?php echo get_phrase('موافقة'); ?></a>
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('الغاء'); ?></button>
                </div>
            </div>
        </div>
    </div>         
    
    <div class="modal fade" id="modal-8">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="disapprove_exam_link"><?php echo get_phrase('إلغاء الموافقة'); ?></a>
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('الغاء'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modal-9">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="approve_news_link"><?php echo get_phrase('موافقة'); ?></a>
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('الغاء'); ?></button>
                </div>
            </div>
        </div>
    </div>         
    
    <div class="modal fade" id="modal-10">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">            
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('هل أنت متأكد ؟'); ?></h4>
                </div>
                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-danger" id="disapprove_news_link"><?php echo get_phrase('إلغاء الموافقة'); ?></a>
                    <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('الغاء'); ?></button>
                </div>
            </div>
        </div>
    </div>     