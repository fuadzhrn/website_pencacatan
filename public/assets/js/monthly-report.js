export const REPORT_MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const STATUS_LABELS = {
    completed: 'Sesuai / selesai',
    issue: 'Tidak sesuai / temuan',
    notScheduled: 'Tidak dijadwalkan',
    pending: 'Belum diperiksa',
};

const STATUS_SYMBOLS = {
    completed: '✓',
    issue: '×',
    notScheduled: '−',
    pending: '•',
};

const MACHINE_PROFILES = {
    baggage: {
        name: 'X-Ray Baggage Smiths Detection HI-Scan 100100T',
        brand: 'Smiths Detection',
        type: 'HI-Scan 100100T',
        serial: 'SD-HS100100T-021',
        location: 'Security Check Point Terminal 1',
        airport: 'Bandar Udara Malikussaleh',
        officer: 'Budi Santoso',
        officerNip: '19890214 201503 1 002',
        supervisor: 'Ahmad Fauzi',
        supervisorNip: '19850708 201001 1 006',
        seed: 0,
    },
    cabin: {
        name: 'X-Ray Cabin',
        brand: 'Rapiscan Systems',
        type: 'Rapiscan 620XR',
        serial: 'RS-620XR-014',
        location: 'Pemeriksaan Cabin Terminal 2',
        airport: 'Bandar Udara Malikussaleh',
        officer: 'Andi Pratama',
        officerNip: '19910417 201802 1 004',
        supervisor: 'Ahmad Fauzi',
        supervisorNip: '19850708 201001 1 006',
        seed: 3,
    },
    cargo: {
        name: 'X-Ray Cargo',
        brand: 'Astrophysics',
        type: 'XIS-1818',
        serial: 'AP-XIS1818-008',
        location: 'Area Pemeriksaan Cargo',
        airport: 'Bandar Udara Malikussaleh',
        officer: 'Siti Rahma',
        officerNip: '19920711 201903 2 005',
        supervisor: 'Dedi Kurniawan',
        supervisorNip: '19831119 200901 1 008',
        seed: 6,
    },
};

export const getMachineProfile = (machine) => MACHINE_PROFILES[machine] ?? MACHINE_PROFILES.baggage;

export const getDaysInMonth = (month, year) => new Date(Number(year), Number(month), 0).getDate();

const getInspectionCutoff = (month, year, daysInMonth) => {
    const numericYear = Number(year);
    const numericMonth = Number(month);

    if (numericYear < 2026 || (numericYear === 2026 && numericMonth < 10)) {
        return daysInMonth;
    }

    if (numericYear === 2026 && numericMonth === 10) {
        return 8;
    }

    return 0;
};

const isScheduled = (frequency, day) => {
    if (frequency === 'weekly') {
        return day % 7 === 0;
    }

    if (frequency === 'monthly') {
        return day === 1;
    }

    return true;
};

export const buildMonthlyStatusMatrix = (activities, filters) => {
    const daysInMonth = getDaysInMonth(filters.month, filters.year);
    const cutoff = getInspectionCutoff(filters.month, filters.year, daysInMonth);
    const machineSeed = getMachineProfile(filters.machine).seed;

    return activities.map((activity, activityIndex) => Array.from({ length: 31 }, (_, dayIndex) => {
        const day = dayIndex + 1;

        if (day > daysInMonth || !isScheduled(activity.frequency, day)) {
            return 'notScheduled';
        }

        if (day > cutoff) {
            return 'pending';
        }

        const hasIssue = (activityIndex * 7 + day + Number(filters.month) + machineSeed) % 29 === 0;

        return hasIssue ? 'issue' : 'completed';
    }));
};

export const summarizeMonthlyReport = (matrix) => {
    const summary = matrix.flat().reduce((totals, status) => {
        if (Object.hasOwn(totals, status)) {
            totals[status] += 1;
        }

        return totals;
    }, {
        completed: 0,
        issue: 0,
        notScheduled: 0,
        pending: 0,
    });
    const scheduledTotal = summary.completed + summary.issue + summary.pending;
    const inspectedTotal = summary.completed + summary.issue;

    return {
        ...summary,
        findings: summary.issue,
        completionPercentage: scheduledTotal === 0 ? 0 : Math.round((inspectedTotal / scheduledTotal) * 100),
    };
};

const escapeCsvCell = (value) => `"${String(value ?? '').replaceAll('"', '""')}"`;

export const buildMonthlyReportCsv = ({ metadata, activities, matrix }) => {
    const informationRows = [
        ['Nama Laporan', 'Laporan Preventive Maintenance Bulanan'],
        ['Nama Mesin', metadata.machine],
        ['Merk', metadata.brand],
        ['Tipe', metadata.type],
        ['Serial Number', metadata.serial],
        ['Lokasi', metadata.location],
        ['Bandar Udara', metadata.airport],
        ['Periode', `${metadata.monthName} ${metadata.year}`],
        ['Petugas', metadata.officer],
        ['Supervisor', metadata.supervisor],
    ];
    const tableHeader = ['No', 'Kegiatan', 'Kriteria', ...Array.from({ length: 31 }, (_, index) => index + 1), 'Keterangan'];
    const tableRows = activities.map((activity, index) => [
        index + 1,
        activity.name,
        activity.criteria,
        ...matrix[index].map((status) => STATUS_LABELS[status]),
        activity.note,
    ]);

    return [
        ...informationRows,
        [],
        tableHeader,
        ...tableRows,
    ].map((row) => row.map(escapeCsvCell).join(';')).join('\r\n');
};

export const createToastNotifier = ({ show, startHiding, hide, schedule = setTimeout, cancel = clearTimeout }) => {
    let fadeTimer = null;
    let hideTimer = null;

    return (message) => {
        if (fadeTimer !== null) {
            cancel(fadeTimer);
        }

        if (hideTimer !== null) {
            cancel(hideTimer);
        }

        show(message);
        fadeTimer = schedule(() => {
            fadeTimer = null;
            startHiding();
            hideTimer = schedule(() => {
                hideTimer = null;
                hide();
            }, 180);
        }, 3000);
    };
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.querySelector('[data-report-filter]');
        const reportRows = [...document.querySelectorAll('[data-report-row]')];
        const toast = document.querySelector('[data-report-toast]');
        let currentReport = null;

        const activities = reportRows.map((row) => ({
            name: row.dataset.activity,
            criteria: row.dataset.criteria,
            frequency: row.dataset.frequency,
            note: row.querySelector('[data-row-note]').textContent.trim(),
        }));

        const getFilters = () => ({
            machine: filterForm.elements.machine.value,
            month: Number(filterForm.elements.month.value),
            year: Number(filterForm.elements.year.value),
        });

        const setText = (selector, value) => {
            document.querySelectorAll(selector).forEach((element) => {
                element.textContent = value;
            });
        };

        const showToast = createToastNotifier({
            show: (message) => {
                toast.querySelector('span').textContent = message;
                toast.hidden = false;
                toast.classList.add('is-visible');
            },
            startHiding: () => {
                toast.classList.remove('is-visible');
            },
            hide: () => {
                toast.hidden = true;
            },
            schedule: window.setTimeout.bind(window),
            cancel: window.clearTimeout.bind(window),
        });

        const renderMetadata = (filters) => {
            const machine = getMachineProfile(filters.machine);
            const monthName = REPORT_MONTHS[filters.month - 1];

            setText('[data-report-machine]', machine.name);
            setText('[data-report-brand]', machine.brand);
            setText('[data-report-type]', machine.type);
            setText('[data-report-serial]', machine.serial);
            setText('[data-report-location]', machine.location);
            setText('[data-report-airport]', machine.airport);
            setText('[data-report-month]', monthName);
            setText('[data-report-year]', filters.year);
            setText('[data-report-officer]', machine.officer);
            setText('[data-report-supervisor]', machine.supervisor);
            setText('[data-signature-officer]', machine.officer);
            setText('[data-signature-supervisor]', machine.supervisor);
            setText('[data-signature-officer-nip]', machine.officerNip);
            setText('[data-signature-supervisor-nip]', machine.supervisorNip);
            setText('[data-summary-period]', `${monthName} ${filters.year}`);

            return {
                ...machine,
                machine: machine.name,
                monthName,
                year: filters.year,
            };
        };

        const renderMatrix = (matrix) => {
            reportRows.forEach((row, rowIndex) => {
                row.querySelectorAll('[data-status-symbol]').forEach((symbol, dayIndex) => {
                    const status = matrix[rowIndex][dayIndex];
                    symbol.className = `report-symbol report-symbol--${status.replace(/[A-Z]/g, (character) => `-${character.toLowerCase()}`)}`;
                    symbol.textContent = STATUS_SYMBOLS[status];
                    symbol.setAttribute('aria-label', STATUS_LABELS[status]);
                    symbol.title = `${dayIndex + 1}: ${STATUS_LABELS[status]}`;
                });
            });
        };

        const renderSummary = (summary) => {
            setText('[data-summary-activities]', activities.length);
            setText('[data-summary-completed]', summary.completed);
            setText('[data-summary-issue]', summary.issue);
            setText('[data-summary-not-scheduled]', summary.notScheduled);
            setText('[data-summary-findings]', summary.findings);
            setText('[data-summary-percentage]', `${summary.completionPercentage}%`);

            const progress = document.querySelector('[data-summary-progress]');
            progress.style.width = `${summary.completionPercentage}%`;
        };

        const renderReport = ({ notify = false } = {}) => {
            const filters = getFilters();
            const metadata = renderMetadata(filters);
            const matrix = buildMonthlyStatusMatrix(activities, filters);
            const summary = summarizeMonthlyReport(matrix);

            renderMatrix(matrix);
            renderSummary(summary);
            currentReport = { filters, metadata, matrix, summary };

            if (notify) {
                showToast(`Laporan ${metadata.monthName} ${metadata.year} berhasil ditampilkan.`);
            }
        };

        const downloadCsv = () => {
            if (!currentReport) {
                return;
            }

            const csv = buildMonthlyReportCsv({
                metadata: currentReport.metadata,
                activities,
                matrix: currentReport.matrix,
            });
            const fileName = `laporan-maintenance-${currentReport.filters.machine}-${currentReport.filters.year}-${String(currentReport.filters.month).padStart(2, '0')}.csv`;
            const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8;' });
            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = downloadUrl;
            link.download = fileName;
            document.body.append(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(downloadUrl);
            showToast('Export Excel berhasil disimulasikan.');
        };

        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            renderReport({ notify: true });
        });

        filterForm.addEventListener('reset', () => {
            window.setTimeout(() => renderReport({ notify: true }), 0);
        });

        document.querySelector('[data-print-report]').addEventListener('click', () => {
            showToast('Mode cetak laporan sedang disiapkan.');
            window.setTimeout(() => window.print(), 180);
        });

        document.querySelector('[data-export-report]').addEventListener('click', downloadCsv);

        renderReport();
    });
}
