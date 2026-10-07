export const toLocalIsoDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const formatCurrentDate = () => {
    const dateElement = document.querySelector('#current-date');

    if (!dateElement) {
        return;
    }

    const currentDate = new Date();
    const formattedDate = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(currentDate);

    dateElement.dateTime = toLocalIsoDate(currentDate);
    dateElement.textContent = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
};

const initializeIcons = () => {
    if (window.lucide) {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 1.8,
            },
        });
    }
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        formatCurrentDate();
        initializeIcons();
    });
}

if (typeof window !== 'undefined') {
    window.addEventListener('load', initializeIcons);
}
