import Chart from 'chart.js/auto';

const DEFAULT_COLORS = {
    revenue: '#059669',
    orders: '#0284c7',
    doughnut: ['#059669', '#0284c7', '#d97706', '#7c3aed', '#db2777', '#dc2626', '#64748b'],
};

document.addEventListener('DOMContentLoaded', () => {
    const configs = [
        { id: 'revenueChart', type: 'line', defaultColor: DEFAULT_COLORS.revenue },
        { id: 'orderChart', type: 'bar', defaultColor: DEFAULT_COLORS.orders },
        { id: 'statusChart', type: 'doughnut', defaultColor: DEFAULT_COLORS.doughnut },
        { id: 'paymentMethodChart', type: 'doughnut', defaultColor: DEFAULT_COLORS.doughnut },
    ];

    configs.forEach(({ id, type, defaultColor }) => {
        const el = document.getElementById(id);
        if (!el) return;

        const labels = JSON.parse(el.dataset.labels || '[]');
        const data = JSON.parse(el.dataset.data || '[]');
        const colors = el.dataset.colors ? JSON.parse(el.dataset.colors) : defaultColor;

        const dataset = {
            label: el.dataset.label || '',
            data,
            backgroundColor: type === 'line' ? colors : (colors || defaultColor),
            borderColor: type === 'line' ? (Array.isArray(colors) ? colors[0] : colors) : undefined,
            tension: type === 'line' ? 0.3 : undefined,
        };

        new Chart(el, {
            type,
            data: { labels, datasets: [dataset] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type === 'doughnut' },
                },
            },
        });
    });
});
