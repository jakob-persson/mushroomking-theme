<?php
/**
 * Template Name: Mushroom Stats
 *
 * A dedicated page to show detailed mushroom statistics.
 */

get_header();
global $wpdb;

// --- Stats Queries ---

// Total kilograms
$total_kg = floatval($wpdb->get_var("SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms"));

// Best day
$best_day_kg = floatval($wpdb->get_var("
  SELECT SUM(kilograms)
  FROM {$wpdb->prefix}mushrooms
  WHERE start_date IS NOT NULL AND start_date != '0000-00-00'
  GROUP BY start_date
  ORDER BY SUM(kilograms) DESC
  LIMIT 1
"));

// Adventures count
$total_rounds = intval($wpdb->get_var("
  SELECT COUNT(DISTINCT DATE(start_date))
  FROM {$wpdb->prefix}mushrooms
  WHERE start_date IS NOT NULL AND start_date != '0000-00-00'
"));

// Locations
$total_locations = intval($wpdb->get_var("
  SELECT COUNT(DISTINCT location)
  FROM {$wpdb->prefix}mushrooms
  WHERE location IS NOT NULL AND location != ''
"));

// --- Mushrooms data ---
$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mushrooms");

// Totals by type
$totals_by_type = [];
$kg_per_month_by_type = [];
foreach ($results as $row) {
    $raw = isset($row->types) ? $row->types : (isset($row->type) ? $row->type : '');
    $types = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($types)) {
        foreach ($types as $type => $kg) {
            $totals_by_type[$type] = ($totals_by_type[$type] ?? 0) + floatval($kg);
            $year_month = date('Y-m', strtotime($row->start_date));
            $kg_per_month_by_type[$type][$year_month] = ($kg_per_month_by_type[$type][$year_month] ?? 0) + floatval($kg);
        }
    }
}

// All months for chart x-axis
$all_months = [];
foreach ($results as $row) {
    $all_months[] = date('Y-m', strtotime($row->start_date));
}
$all_months = array_unique($all_months);
sort($all_months);

// Prepare data for JS
$kg_by_type_json = json_encode($kg_per_month_by_type);
$months_json = json_encode($all_months); // YYYY-MM

// --- Grouped Mushrooms for Adventures ---
$grouped_mushrooms = $wpdb->get_results("
  SELECT
    DATE(start_date) AS grouped_date,
    location,
    GROUP_CONCAT(photo_url) AS photos,
    SUM(kilograms) AS total_kg
  FROM {$wpdb->prefix}mushrooms
  GROUP BY grouped_date, location
  ORDER BY grouped_date DESC
");

$hunts = array_map(function($entry) {
    $photos = array_filter(explode(',', $entry->photos));
    return [
        'location' => $entry->location ?? 'Unknown',
        'date' => $entry->grouped_date,
        'timestamp' => $entry->grouped_date ? strtotime($entry->grouped_date) : time(),
        'total_kg' => floatval($entry->total_kg),
        'photo' => $photos[0] ?? null,
    ];
}, $grouped_mushrooms);
?>

<!-- HEADER BAR -->
<div class="flex w-full bg-[#1E2330] h-[40px] items-center relative">
    <div class="absolute left-4 flex items-center h-full">
        <img src="<?= get_template_directory_uri(); ?>/images/mk-logo3.png" alt="Logo" class="h-[14px] lg:h-[14px]">
    </div>
    <div class="mx-auto text-white text-sm">Hi, here's detailed stat for your adventures</div>
</div>

<div class="fixed top-[40px] left-0 right-0 bottom-0 flex bg-[#F1F0EE] rounded-t-xl w-full z-10">
<!-- SIDEBAR -->
<aside class="w-64 bg-[#ECECE9] border-r border-gray-200 flex flex-col rounded-tl-xl sticky top-[40px] h-[calc(100vh-40px)] z-40">
   <div class="p-4  flex items-center gap-2 text-xs font-medium relative" x-data="{ open: false }">
    <img src="<?= get_template_directory_uri(); ?>/images/mk.jpg" alt="Profile Image" class="rounded-full w-8 h-8 border border-white">
    <div class="flex items-center gap-1 cursor-pointer select-none" @click="open = !open">
        <span>mushroomking</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </div>
    <div x-show="open" @click.outside="open = false" x-transition class="absolute top-full right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg text-gray-700 text-sm z-50">
        <ul class="py-1">
            <li><a href="/profile" class="block px-4 py-2 hover:bg-gray-100">Profile</a></li>
            <li><a href="/settings" class="block px-4 py-2 hover:bg-gray-100">Settings</a></li>
            <li><form method="POST" action="/logout"><button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button></form></li>
        </ul>
    </div>
</div>

<nav class="flex-1 p-4 space-y-2 text-sm">
    <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100">
        <i class="fas fa-chart-line w-4 text-gray-500"></i>
        Insights
    </a>
    <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100">
        <i class="fas fa-plus-circle w-4 text-gray-500"></i>
        Create
    </a>
</nav>

<div class="p-4 border-t">
    <button class="w-full px-4 py-2 bg-[#5D18A2] text-white rounded-lg">Add adventure</button>
</div>
</aside>

<!-- Sticky Header -->
<div class="fixed top-[40px] left-[218px] right-0 z-30 bg-[#eff0ec] border-b h-[65px] flex items-center px-8">
    <div class="text-base font-semibold text-[#111827]">Overview</div>
    <div class="ml-auto relative">
        <button id="timeframeBtn" class="bg-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-100 flex items-center gap-2">
            Lifetime
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div id="timeframeMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-300 rounded-lg shadow-lg">
            <ul class="text-sm text-gray-700">
                <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">Lifetime</button></li>
                <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">1 Month</button></li>
                <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">3 Months</button></li>
                <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">6 Months</button></li>
                <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">12 Months</button></li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("timeframeBtn");
    const menu = document.getElementById("timeframeMenu");
    btn.addEventListener("click", () => menu.classList.toggle("hidden"));
    document.addEventListener("click", (e) => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add("hidden");
    });
});
</script>

<main class="w-full overflow-y-auto h-full mt-[60px]">
<div class="p-8">

<!-- OVERVIEW STATS -->
<div class="bg-white rounded-3xl p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
    <div class="flex items-center gap-1 mb-6">
        <h2 class="text-lg font-semibold">Overview 2025</h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-sm text-gray-700">
        <div class="flex items-center gap-2">
            <div class="bg-gray-100 rounded-full p-2">
                <img src="<?= get_template_directory_uri(); ?>/images/basket2.svg" class="w-5 h-5" alt="Total Kilograms">
            </div>
            <div><span class="font-semibold text-gray-900"><?= rtrim(rtrim(number_format($total_kg,2,'.',''),'0'),'.') ?></span> Kilograms</div>
        </div>
        <div class="flex items-center gap-2">
            <div class="bg-gray-100 rounded-full p-2">
                <img src="<?= get_template_directory_uri(); ?>/images/mountain.svg" class="w-5 h-5" alt="Adventures">
            </div>
            <div><span class="font-semibold text-gray-900"><?= $total_rounds ?></span> Adventures</div>
        </div>
        <div class="flex items-center gap-2">
            <div class="bg-gray-100 rounded-full p-2">
                <img src="<?= get_template_directory_uri(); ?>/images/locations2.svg" class="w-5 h-5" alt="Locations">
            </div>
            <div><span class="font-semibold text-gray-900"><?= $total_locations ?></span> Locations</div>
        </div>
        <div class="flex items-center gap-2">
            <div class="bg-gray-100 rounded-full p-2">
                <img src="<?= get_template_directory_uri(); ?>/images/trophy3.svg" class="w-5 h-5" alt="Best Day">
            </div>
            <div><span class="font-semibold text-gray-900"><?= rtrim(rtrim(number_format($best_day_kg,2,'.',''),'0'),'.') ?></span> Best Day (kg)</div>
        </div>
    </div>
</div>

<!-- MUSHROOM ADVENTURES -->
<div id="adventuresContainer" class="bg-white rounded-3xl p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
    <div class="flex items-center gap-1 mb-6"><h2 class="text-lg font-semibold">Mushroom adventures</h2></div>
    <div class="flex overflow-x-auto no-scrollbar space-x-2 mb-6" id="adventureFilters"></div>
    <div class="space-y-4" id="adventureList"></div>
    <div class="text-center mt-6"><button id="loadMoreHunts" class="px-6 py-3 bg-[#5D18A2] text-white rounded-full text-sm font-semibold">Load More</button></div>
</div>

<!-- MONTHLY HARVEST -->
<div class="bg-white rounded-3xl p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
    <div class="flex items-center gap-1 mb-6"><h2 class="text-lg font-semibold">Monthly Harvest</h2></div>
    <div class="mb-4">
        <label for="typeFilter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Mushroom Type</label>
        <select id="typeFilter" class="border rounded px-3 py-2 text-sm">
            <option value="all">All</option>
            <?php foreach(array_keys($totals_by_type) as $type): ?>
            <option value="<?= esc_attr($type) ?>"><?= esc_html($type) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <canvas id="mushroomChart" height="120"></canvas>
</div>

</div> <!-- end p-8 -->
</main>
</div> <!-- end main wrapper -->

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const months = <?= $months_json ?>;
    const dataByType = <?= $kg_by_type_json ?>;
    const monthsLabels = months.map(m=>{
        const [y,mo]=m.split('-'); const d=new Date(y,mo-1);
        return d.toLocaleString('default',{month:'short',year:'numeric'});
    });

    const ctx = document.getElementById('mushroomChart').getContext('2d');
    const chart = new Chart(ctx,{
        type:'line',
        data:{labels:monthsLabels,datasets:[{label:'Kilograms',data:months.map(()=>0),borderColor:'rgba(124, 58, 237, 0.8)',backgroundColor:'rgba(124, 58, 237, 0)',tension:0.4,fill:true,pointBackgroundColor:'rgba(124, 58, 237, 1)',pointRadius:4}]},
        options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{ticks:{color:'#6B7280',stepSize:100},grid:{drawBorder:false}},x:{ticks:{color:'#6B7280'},grid:{display:false}}}}
    });

    const typeSelect = document.getElementById('typeFilter');
    typeSelect.addEventListener('change', function(){
        const type = this.value;
        chart.data.datasets[0].data = months.map(m=>type==='all'?Object.values(dataByType).reduce((sum,t)=>sum+(t[m]||0),0):(dataByType[type][m]||0));
        chart.update();
    });
    typeSelect.dispatchEvent(new Event('change'));

    // --- Mushroom Adventures ---
    const hunts = <?= json_encode($hunts) ?>;
    let filter='all',visibleCount=10;
    const filters=['all','top10','recent','heavy'];
    const filterContainer=document.getElementById('adventureFilters');
    const listContainer=document.getElementById('adventureList');
    const loadMoreBtn=document.getElementById('loadMoreHunts');

    function renderFilters(){
        filterContainer.innerHTML='';
        filters.forEach(f=>{
            const btn=document.createElement('button');
            btn.textContent=f==='all'?'All':f==='top10'?'Top 10':f==='recent'?'Last 7 Days':'Over 30kg';
            btn.className=`flex-shrink-0 px-4 py-2 rounded-full text-sm font-regular whitespace-nowrap ${filter===f?'bg-[#CEE027] text-[#111827]':'bg-white border-[#F4F4F1] border text-[#111827]'}`;
            btn.addEventListener('click',()=>{filter=f; visibleCount=10; renderHunts(); renderFilters();});
            filterContainer.appendChild(btn);
        });
    }

    function getFilteredHunts(){
        const now=Date.now()/1000;
        let result=[];
        if(filter==='top10') result=hunts.slice().sort((a,b)=>b.total_kg-a.total_kg).slice(0,10);
        else if(filter==='recent') result=hunts.filter(h=>h.timestamp>=now-7*24*3600);
        else if(filter==='heavy') result=hunts.filter(h=>h.total_kg>30);
        else result=hunts;
        return result.slice(0,visibleCount);
    }

    function renderHunts(){
        const items=getFilteredHunts();
        listContainer.innerHTML='';
        if(items.length===0){listContainer.innerHTML='<div class="text-center text-gray-400 text-sm mt-4">No hunts match this filter.</div>';return;}
        items.forEach(h=>{
            const div=document.createElement('div');
            div.className='flex items-center justify-between bg-white border border-[#F4F4F1] rounded-2xl p-4';
            div.innerHTML=`<div class="flex items-center gap-4">${h.photo?`<img src="${h.photo}" class="w-12 h-12 rounded-md object-cover"/>`:`<div class="w-12 h-12 bg-gray-200 rounded-md flex items-center justify-center text-gray-500">🍄</div>`}<div class="text-sm font-regular flex flex-col"><div><strong>${h.location}</strong></div><div class="text-sm">${new Date(h.timestamp*1000).toLocaleDateString()}</div></div></div><div class="text-sm dark"><span class="font-medium">${h.total_kg}</span> kg</div>`;
            listContainer.appendChild(div);
        });
    }

    loadMoreBtn.addEventListener('click',()=>{visibleCount+=10;renderHunts();});
    renderFilters();
    renderHunts();
});
</script>

<?php get_footer(); ?>
