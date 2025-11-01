// admin/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    // --- State ---
    let filters = {
        start_date: '',
        end_date: '',
        tld: '',
        category: ''
    };
    let charts = {}; // To hold chart instances for updates

    // --- DOM Elements ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const logoutBtn = document.getElementById('logout-btn');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const exportCsvBtn = document.getElementById('export-csv-btn');
    const exportJsonBtn = document.getElementById('export-json-btn');

    // --- Event Listeners ---
    themeToggleBtn.addEventListener('click', toggleTheme);
    logoutBtn.addEventListener('click', logout);
    applyFiltersBtn.addEventListener('click', applyFilters);
    exportCsvBtn.addEventListener('click', () => exportData('csv'));
    exportJsonBtn.addEventListener('click', () => exportData('json'));

    // --- Initial Load ---
    populateFilterDropdowns();
    fetchDashboardData();

    // --- Functions ---
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
    }

    async function logout() {
        await fetch('../api/admin/logout.php');
        window.location.href = 'login.html';
    }

    function applyFilters() {
        filters.start_date = document.getElementById('start-date').value;
        filters.end_date = document.getElementById('end-date').value;
        filters.tld = document.getElementById('tld-filter').value;
        filters.category = document.getElementById('category-filter').value;
        fetchDashboardData();
    }

    function buildQueryString() {
        const params = new URLSearchParams();
        for (const key in filters) {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        }
        return params.toString();
    }

    async function fetchDashboardData() {
        const queryString = buildQueryString();
        try {
            const [statsRes, tldRes, keywordsRes, recentRes, dailyGenRes, categoryRes] = await Promise.all([
                fetch(`../api/admin/stats.php?${queryString}`),
                fetch(`../api/admin/tld-chart.php?${queryString}`),
                fetch(`../api/admin/trending-keywords.php?${queryString}`),
                fetch(`../api/admin/recent-domains.php?${queryString}`),
                fetch(`../api/admin/daily-generation.php?${queryString}`),
                fetch(`../api/admin/categories.php?${queryString}`)
            ]);

            if (statsRes.status === 401) {
                window.location.href = 'login.html';
                return;
            }

            const stats = await statsRes.json();
            const tldData = await tldRes.json();
            const keywordsData = await keywordsRes.json();
            const recentDomains = await recentRes.json();
            const dailyGenData = await dailyGenRes.json();
            const categoryData = await categoryRes.json();

            populateStatsCards(stats);
            renderOrUpdateChart('daily-generation-chart', 'line', dailyGenData, "Domains Generated");
            renderOrUpdateChart('category-distribution-chart', 'doughnut', categoryData, "Category Distribution");
            renderOrUpdateChart('tld-pie-chart', 'pie', tldData, "TLD Distribution");
            renderOrUpdateChart('keywords-bar-chart', 'bar', { labels: keywordsData.map(k => k.keyword), data: keywordsData.map(k => k.count) }, "Top Keywords");
            populateRecentDomainsTable(recentDomains);
            populateCommonCategoriesTable(categoryData);


        } catch (error) {
            console.error('Failed to load dashboard data:', error);
        }
    }

    async function populateFilterDropdowns() {
        const tldData = await (await fetch('../api/admin/tld-chart.php')).json();
        const categoryData = await (await fetch('../api/admin/categories.php')).json();

        const tldFilter = document.getElementById('tld-filter');
        tldData.labels.forEach(tld => tldFilter.innerHTML += `<option value="${tld}">${tld}</option>`);

        const categoryFilter = document.getElementById('category-filter');
        categoryData.labels.forEach(cat => categoryFilter.innerHTML += `<option value="${cat}">${cat}</option>`);
    }

    function populateStatsCards(stats) {
        document.getElementById('total-domains').textContent = stats.total_domains;
        document.getElementById('total-keywords').textContent = stats.total_keywords;
        document.getElementById('top-tld').textContent = stats.top_tld;
        document.getElementById('avg-length').textContent = stats.avg_length;
        document.getElementById('today-count').textContent = stats.today_count;
    }

    function renderOrUpdateChart(canvasId, type, chartData, label) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const existingChart = charts[canvasId];

        if (existingChart) {
            existingChart.data.labels = chartData.labels;
            existingChart.data.datasets[0].data = chartData.data;
            existingChart.update();
        } else {
            charts[canvasId] = new Chart(ctx, {
                type: type,
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: label,
                        data: chartData.data,
                        backgroundColor: ['#4A90E2', '#50E3C2', '#F5A623', '#F8E71C', '#BD10E0'],
                    }]
                },
                options: { responsive: true, indexAxis: type === 'bar' ? 'y' : 'x' }
            });
        }
    }

    function exportData(format) {
        const queryString = buildQueryString();
        const url = `../api/admin/export.php?format=${format}&${queryString}`;
        window.open(url, '_blank');
    }

    function populateRecentDomainsTable(domains) {
        const tbody = document.querySelector('#recent-domains-table tbody');
        tbody.innerHTML = '';
        if (domains.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">No data available for the selected filters.</td></tr>';
            return;
        }
        domains.forEach(domain => {
            const row = `<tr>
                <td>${domain.keyword}</td>
                <td>${domain.domain_name}</td>
                <td>${domain.tld}</td>
                <td>${domain.category || 'N/A'}</td>
                <td>${new Date(domain.timestamp).toLocaleString()}</td>
            </tr>`;
            tbody.innerHTML += row;
        });
    }

    function populateCommonCategoriesTable(categoryData) {
        const tbody = document.querySelector('#common-categories-table tbody');
        tbody.innerHTML = '';
        if (categoryData.labels.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2">No category data available.</td></tr>';
            return;
        }
        for(let i=0; i< categoryData.labels.length; i++){
            const row = `<tr>
                <td>${categoryData.labels[i]}</td>
                <td>${categoryData.data[i]}</td>
            </tr>`;
            tbody.innerHTML += row;
        }
    }
});
