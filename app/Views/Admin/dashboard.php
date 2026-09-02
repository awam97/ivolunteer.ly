<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<div class="dashboard-stats-grid">
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite"><?= $translate->translate_phrase('admins',$language); ?></div>           
            <div class="counter-icon"><i class="fa-solid fa-user-shield"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter"><?php echo $admins;?></div>
        </div>
    </div>
    
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite"><?= $translate->translate_phrase('cities',$language); ?></div>           
            <div class="counter-icon"><i class="fa-solid fa-map-location-dot"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter"><?php echo $cities;?></div>
        </div>
    </div>
    
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite"><?= $translate->translate_phrase('activities',$language); ?></div>           
            <div class="counter-icon"><i class="fa-solid fa-calendar-check"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter"><?php echo $activities;?></div>
        </div>
    </div>    
    
    <div class="counter-panel">
        <div class="page-title-box-lite">
            <div class="box-title-lite"><?= $translate->translate_phrase('volunteers',$language); ?></div>           
            <div class="counter-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="white-box-counter">
            <div class="counter"><?php echo $volunteers;?></div>
        </div>
    </div>     
</div>
<div class="row">
    <div class="col-md-8">
        <div class="counter-panel">
            <div class="page-title-box-lite">
                <div class="box-title-lite">معدل تسجيل المتطوعين خلال السنة (يومياً)</div>           
                <div class="counter-icon"><i class="fa-solid fa-chart-line"></i></div>
            </div>
            <div class="white-box" style="height: 350px;">
                <canvas id="registrationChart"></canvas>
            </div>            
        </div>
    </div>
    <div class="col-md-4">
        <div class="counter-panel">
            <div class="page-title-box-lite">
                <div class="box-title-lite">عدد المتطوعين حسب كل مدينة</div>           
            </div>
            <div class="white-box-counter" style="height: 350px; display: flex; align-items: center; justify-content: center;">
                <canvas id="citiesChart"></canvas>
            </div>        
        </div>
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="counter-panel">
            <div class="page-title-box-lite">
                    <div class="box-title-lite">المتطوعين الأكثر نشاطاً</div>           
            </div>
            <div class="white-box">
                <table style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الإسم</th>
                            <th>الساعات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_volunteers)): ?>
                            <?php foreach ($top_volunteers as $index => $volunteer): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($volunteer['name']); ?></td>
                                    <td><?= htmlspecialchars($volunteer['total_hours']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>            
        </div>
    </div>
</div>

<?php $cities = array_map(function ($city) { return $city->name; }, $cities_data);?>

<script>
    const registrationLabels = <?php echo json_encode($registrationLabels); ?>;
    const registrationData = <?php echo json_encode($registrationData); ?>;

    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.color = '#64748b';

    const ctsx = document.getElementById('citiesChart').getContext('2d');
    const regtx = document.getElementById('registrationChart').getContext('2d');

    // Registration Trend Chart (Line)
    const regGradient = regtx.createLinearGradient(0, 0, 0, 300);
    regGradient.addColorStop(0, 'rgba(48, 67, 0, 0.4)');
    regGradient.addColorStop(1, 'rgba(48, 67, 0, 0)');

    new Chart(regtx, {
        type: 'line',
        data: {
            labels: registrationLabels,
            datasets: [{
                label: 'المتطوعين الجدد',
                data: registrationData,
                fill: true,
                backgroundColor: regGradient,
                borderColor: '#304300',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#304300',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    borderRadius: 8,
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: { precision: 0 }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

    // Volunteers chart
    const cityLabels = <?php echo json_encode($cities); ?>;
    const volunteerData = <?php echo json_encode($volunteersPerCity); ?>; 

    new Chart(ctsx, {
        type: 'doughnut',
        data: {
            labels: cityLabels,
            datasets: [{                
                data: volunteerData, 
                backgroundColor: [
                    '#304300',   
                    '#526b0a',
                    '#87A052',
                    '#daac18',
                    '#fbd04d'
                ],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            cutout: '75%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    borderRadius: 8,
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000
            }
        }
    });

</script>

