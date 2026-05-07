/**
 * Sentinel — Chart.js Dashboard Charts
 */

// Chart.js global defaults from DESIGN.md §12
Chart.defaults.color = '#9aa0b4';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = 'IBM Plex Sans';
Chart.defaults.font.size = 11;

const SentinelCharts = {
    eventsChart: null,
    riskChart: null,
    typesChart: null,

    chartDefaults: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#9aa0b4',
                    font: { family: 'Inter', size: 11 },
                    padding: 16,
                },
            },
            tooltip: {
                backgroundColor: 'rgba(15, 20, 35, 0.9)',
                titleColor: '#e8eaed',
                bodyColor: '#9aa0b4',
                borderColor: 'rgba(99, 102, 241, 0.2)',
                borderWidth: 1,
                cornerRadius: 8,
                titleFont: { family: 'IBM Plex Sans', size: 12, weight: '600' },
                bodyFont: { family: 'JetBrains Mono', size: 11 },
                padding: 12,
            },
        },
    },

    init() {
        this.initEventsChart();
        this.initRiskChart();
        this.initTypesChart();
    },

    initEventsChart() {
        const ctx = document.getElementById('events-chart');
        if (!ctx) return;

        this.eventsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Events',
                    data: [],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#6366f1',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }],
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: '#5a6178', font: { family: 'JetBrains Mono', size: 10 }, maxTicksLimit: 12 },
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: '#5a6178', font: { family: 'JetBrains Mono', size: 10 } },
                        beginAtZero: true,
                    },
                },
                plugins: {
                    ...this.chartDefaults.plugins,
                    legend: { display: false },
                },
            },
        });
    },

    initRiskChart() {
        const ctx = document.getElementById('risk-chart');
        if (!ctx) return;

        this.riskChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Low', 'Moderate', 'Elevated', 'High', 'Critical'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(52, 211, 153, 0.8)',
                        'rgba(96, 165, 250, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    borderColor: 'rgba(10, 14, 26, 0.5)',
                    borderWidth: 2,
                    hoverOffset: 6,
                }],
            },
            options: {
                ...this.chartDefaults,
                cutout: '65%',
                plugins: {
                    ...this.chartDefaults.plugins,
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#9aa0b4',
                            font: { family: 'Inter', size: 11 },
                            padding: 12,
                            usePointStyle: true,
                            pointStyleWidth: 8,
                        },
                    },
                },
            },
        });
    },

    initTypesChart() {
        const ctx = document.getElementById('types-chart');
        if (!ctx) return;

        this.typesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Count',
                    data: [],
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.6)',
                        'rgba(34, 211, 238, 0.6)',
                        'rgba(52, 211, 153, 0.6)',
                        'rgba(251, 191, 36, 0.6)',
                        'rgba(251, 113, 133, 0.6)',
                        'rgba(167, 139, 250, 0.6)',
                        'rgba(96, 165, 250, 0.6)',
                        'rgba(251, 146, 60, 0.6)',
                    ],
                    borderRadius: 4,
                    borderSkipped: false,
                }],
            },
            options: {
                ...this.chartDefaults,
                indexAxis: 'y',
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: '#5a6178', font: { family: 'JetBrains Mono', size: 10 } },
                        beginAtZero: true,
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#9aa0b4', font: { family: 'JetBrains Mono', size: 10 } },
                    },
                },
                plugins: {
                    ...this.chartDefaults.plugins,
                    legend: { display: false },
                },
            },
        });
    },

    updateEventsChart(eventsData) {
        if (!this.eventsChart) return;

        // Aggregate by hour
        const hourly = {};
        eventsData.forEach(item => {
            const hour = new Date(item.hour).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            hourly[hour] = (hourly[hour] || 0) + parseInt(item.count);
        });

        this.eventsChart.data.labels = Object.keys(hourly);
        this.eventsChart.data.datasets[0].data = Object.values(hourly);
        this.eventsChart.update('none');
    },

    updateRiskChart(riskData) {
        if (!this.riskChart) return;

        const levelMap = { low: 0, moderate: 1, elevated: 2, high: 3, critical: 4 };
        const data = [0, 0, 0, 0, 0];

        riskData.forEach(item => {
            const idx = levelMap[item.level];
            if (idx !== undefined) data[idx] = parseInt(item.count);
        });

        this.riskChart.data.datasets[0].data = data;
        this.riskChart.update('none');
    },

    updateTypesChart(typesData) {
        if (!this.typesChart) return;

        this.typesChart.data.labels = typesData.map(t => t.event_type);
        this.typesChart.data.datasets[0].data = typesData.map(t => parseInt(t.count));
        this.typesChart.update('none');
    },
};

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('dashboard-page')) {
        SentinelCharts.init();
    }
});
