<?php $volunteer_city = $db->table('volunteers')->where('id', $volunteer_id)->get()->getRow()->city_id;?>

<div class="activities-wrapper fade-in-up">
    <div class="counter-panel">

        <div class="white-box" style="padding: 25px;">
            <input style="display:none" type="text" name="selected" id="selected">   
            
            <?php 
                $activities = $db->table('activities')
                    ->select('activities.*')
                    ->join('volunteer_activities', 'volunteer_activities.activity_id = activities.id AND volunteer_activities.volunteer_id = ' . $volunteer_id, 'left')
                    ->where('activities.date_from>', date("Y/m/d"))
                    ->where('volunteer_activities.activity_id IS NULL')
                    ->get()->getResult();

                if (empty($activities)) : ?>
                    <div class="empty-state-container">
                        <i class="fa-solid fa-calendar-day empty-state-icon"></i>
                        <h3 class="empty-state-title">لا توجد نشاطات متاحة حالياً</h3>
                        <p class="empty-state-desc">لقد قمت بالتسجيل في جميع النشاطات المتاحة المتوفرة حالياً في مدينتك. سنقوم بإشعارك فور توفر فرص جديدة.</p>
                    </div>
                <?php else : 
                    $dataset = '[';
                    foreach ($activities as $row) {
                        $link1 = base_url('Volunteer/activity?id=' . $row->id);            
                        $transportation = $row->transportation ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $residency = $row->residency ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $expenses = $row->expenses ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $training = $row->training ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $city_name = $db->table('cities')->where('id', $row->city_id)->get()->getRow()->name;

                        $dataset .= '["<input class=\'selecting\' onchange=\'selectfunction(this.id)\' type=\'checkbox\' id=' . $row->id . '></input>",'
                                . '"<a class=\'table-action-link\' href=\'' . $link1 . '\'>' . $row->name . '</a>",'
                                . '"' . $transportation . '",'
                                . '"' . $residency . '",'
                                . '"' . $expenses . '",'
                                . '"' . $training . '",'
                                . '"' . $row->organisation . '",'
                                . '"' . $row->date_from . '",'
                                . '"' . $row->date_to . '",'
                                . '"' . $row->hours . '",'
                                . '"' . $city_name . '",'
                                . '],';
                    }
                    $dataset = rtrim($dataset, ',') . ']';
            ?>
                <div class="table-responsive">
                    <table class="table premium-table datatable" id="activitiesTable">
                        <!-- DataTable logic builds this -->
                    </table>
                </div>

                <script>
                    $(document).ready(function () {
                        var dataSet = <?php echo $dataset; ?>;
                        $('#activitiesTable').DataTable({
                            data: dataSet,                
                            "pageLength": 10,
                            "language": {
                                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json"
                            },
                            columns: [
                                { title: "<input id='select_all' onchange='select_all()' class='select_all' type='checkbox'></input>", orderable: false },                    
                                { title: "النشاط" },
                                { title: "المواصلات" },
                                { title: "الإقامة" },
                                { title: "الإعاشة" },
                                { title: "التدريب" },
                                { title: "المنظمة" },
                                { title: "من" },
                                { title: "إلى" },
                                { title: "الساعات" },
                                { title: "المدينة" }
                            ],
                            initComplete: function () {
                                // Custom styling for search/length if needed
                            }
                        });
                    });

                    function select_all() {
                        var allcheckboxes = document.querySelectorAll('input[class="selecting"]');            
                        var checkedall_id = document.getElementById('select_all').checked;
                        if(checkedall_id == true) {
                            allcheckboxes.forEach(checkbox => {checkbox.checked = true;selectfunction(checkbox.id)});
                        } else {                                                
                            allcheckboxes.forEach(checkbox => {checkbox.checked = false;}); 
                            document.getElementById('selected').value = '';
                        }
                    }

                    function selectfunction(id) {
                        var checkbox = document.getElementById(id);
                        if(checkbox.checked == true) {                
                            document.getElementById('selected').value += ','+id;
                        } else {
                            let numberArray = document.getElementById('selected').value.split(',').map(Number);
                            numberArray = numberArray.filter(num => num !== Number(id) && num !== Number(0));
                            document.getElementById('selected').value = `${numberArray.join(',')}`;
                        }
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .activities-wrapper {
        margin-top: 1rem;
    }
    
    .status-icon {
        font-size: 1.1rem;
    }
    .status-icon.success { color: #10b981; }
    .status-icon.muted { color: #e2e8f0; opacity: 0.5; }
    
    .table-action-link {
        color: var(--primary);
        font-weight: 700;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .table-action-link:hover { color: var(--primary-light); text-decoration: underline; }

    .premium-table thead th {
        background: rgba(48, 67, 0, 0.02);
        color: var(--text-main);
        font-weight: 700;
        border-bottom: 2px solid var(--border-color);
        padding: 15px !important;
    }
    
    .premium-table tbody td {
        padding: 15px !important;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
    }

    /* DataTables Pagination & Info Alignment */
    .dataTables_wrapper .dataTables_paginate {
        float: left !important;
        margin-top: 15px;
    }
    .dataTables_wrapper .dataTables_info {
        float: right !important;
        margin-top: 15px;
        color: var(--text-muted);
    }
</style>
