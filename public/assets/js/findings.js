export const summarizeFindingStatuses = (statuses) => statuses.reduce((summary, status) => {
    const normalizedStatus = String(status).toLowerCase();

    summary.total += 1;

    if (Object.hasOwn(summary, normalizedStatus)) {
        summary[normalizedStatus] += 1;
    }

    return summary;
}, {
    total: 0,
    open: 0,
    review: 0,
    resolved: 0,
});

export const matchesFindingFilters = (finding, filters) => {
    const query = String(filters.search ?? '').trim().toLowerCase();
    const searchableContent = `${finding.machine ?? ''} ${finding.item ?? ''}`.toLowerCase();

    return (!filters.date || finding.date === filters.date)
        && (!filters.machine || finding.machineKey === filters.machine)
        && (!filters.status || finding.statusKey === filters.status)
        && (!filters.frequency || finding.frequencyKey === filters.frequency)
        && (!query || searchableContent.includes(query));
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const rows = [...document.querySelectorAll('[data-finding-row]')];
        const filterForm = document.querySelector('[data-findings-filter]');
        const findingFormDialog = document.querySelector('#finding-form-dialog');
        const findingForm = document.querySelector('[data-finding-form]');
        const detailDialog = document.querySelector('#finding-detail-dialog');
        const statusDialog = document.querySelector('#finding-status-dialog');
        const statusForm = document.querySelector('[data-status-form]');
        const photoDialog = document.querySelector('#finding-photo-dialog');
        const toast = document.querySelector('[data-findings-toast]');
        const photoInput = document.querySelector('[data-photo-input]');
        const uploadPreview = document.querySelector('[data-upload-preview]');
        const uploadPlaceholder = document.querySelector('[data-upload-placeholder]');
        let selectedStatusRow = null;
        let toastTimer = null;
        let uploadObjectUrl = null;

        const readFinding = (row) => JSON.parse(row.dataset.finding);

        const refreshIcons = () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        const openDialog = (dialog) => {
            if (dialog && !dialog.open) {
                dialog.showModal();
            }
        };

        const closeDialog = (dialog) => {
            if (dialog?.open) {
                dialog.close();
            }
        };

        const showToast = (message) => {
            if (!toast) {
                return;
            }

            window.clearTimeout(toastTimer);
            toast.querySelector('span').textContent = message;
            toast.hidden = false;
            toast.classList.add('is-visible');
            toastTimer = window.setTimeout(() => {
                toast.classList.remove('is-visible');
                window.setTimeout(() => {
                    toast.hidden = true;
                }, 180);
            }, 3200);
        };

        const currentFilters = () => ({
            date: filterForm?.querySelector('[data-filter-date]').value ?? '',
            machine: filterForm?.querySelector('[data-filter-machine]').value ?? '',
            status: filterForm?.querySelector('[data-filter-status]').value ?? '',
            frequency: filterForm?.querySelector('[data-filter-frequency]').value ?? '',
            search: filterForm?.querySelector('[data-filter-search]').value ?? '',
        });

        const applyFilters = () => {
            const filters = currentFilters();
            let visibleCount = 0;

            rows.forEach((row) => {
                const finding = readFinding(row);
                const isVisible = matchesFindingFilters({
                    date: finding.date,
                    machine: finding.machine,
                    machineKey: finding.machine_key,
                    statusKey: finding.status_key,
                    frequencyKey: finding.frequency_key,
                    item: finding.item,
                }, filters);

                row.hidden = !isVisible;

                if (isVisible) {
                    visibleCount += 1;
                    row.querySelector('.findings-table__number').textContent = visibleCount;
                }
            });

            document.querySelectorAll('[data-visible-count], [data-footer-count]').forEach((element) => {
                element.textContent = visibleCount;
            });

            const emptyRow = document.querySelector('[data-findings-empty]');
            if (emptyRow) {
                emptyRow.hidden = visibleCount !== 0;
            }
        };

        const updateSummary = () => {
            const summary = summarizeFindingStatuses(rows.map((row) => readFinding(row).status_key));

            Object.entries(summary).forEach(([key, value]) => {
                const output = document.querySelector(`[data-summary-${key}]`);
                if (output) {
                    output.textContent = value;
                }
            });
        };

        const clearUploadPreview = () => {
            if (uploadObjectUrl) {
                URL.revokeObjectURL(uploadObjectUrl);
                uploadObjectUrl = null;
            }

            if (uploadPreview) {
                uploadPreview.src = '';
                uploadPreview.hidden = true;
            }

            if (uploadPlaceholder) {
                uploadPlaceholder.hidden = false;
            }
        };

        const configureFindingForm = (finding = null) => {
            findingForm.reset();
            clearUploadPreview();

            const formTitle = findingFormDialog.querySelector('[data-form-title]');
            const formDescription = findingFormDialog.querySelector('[data-form-description]');

            formTitle.textContent = finding ? 'Edit Temuan' : 'Tambah Temuan';
            formDescription.textContent = finding
                ? `Perbarui informasi ${finding.id}.`
                : 'Catat temuan baru dari hasil pemeriksaan.';

            findingForm.elements.finding_id.value = finding?.id ?? '';
            findingForm.elements.date.value = finding?.date ?? '2026-10-08';
            findingForm.elements.machine_key.value = finding?.machine_key ?? '';
            findingForm.elements.location.value = finding?.location ?? '';
            findingForm.elements.frequency_key.value = finding?.frequency_key ?? '';
            findingForm.elements.item.value = finding?.item ?? '';
            findingForm.elements.description.value = finding?.description ?? '';
            findingForm.elements.follow_up.value = finding?.follow_up ?? '';
            findingForm.elements.status_key.value = finding?.status_key ?? 'open';
        };

        const configureDetailDialog = (finding) => {
            detailDialog.querySelector('[data-detail-id]').textContent = finding.id;
            detailDialog.querySelectorAll('[data-detail-field]').forEach((element) => {
                element.textContent = finding[element.dataset.detailField] ?? '-';
            });

            const statusBadge = detailDialog.querySelector('[data-detail-status]');
            statusBadge.className = `status-badge status-badge--${finding.status_key}`;
            statusBadge.innerHTML = '<span aria-hidden="true"></span>';
            statusBadge.append(finding.status);

            const photo = detailDialog.querySelector('[data-detail-photo]');
            const emptyPhoto = detailDialog.querySelector('[data-detail-photo-empty]');

            photo.hidden = !finding.has_photo;
            emptyPhoto.hidden = finding.has_photo;

            if (finding.has_photo) {
                const illustration = photo.querySelector('.finding-photo');
                illustration.className = `finding-photo finding-photo--${finding.photo_variant}`;
                photo.querySelector('[data-detail-photo-label]').textContent = finding.photo_label;
            }
        };

        filterForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            applyFilters();
        });

        filterForm?.addEventListener('reset', () => {
            window.setTimeout(applyFilters, 0);
        });

        document.querySelector('[data-finding-create]')?.addEventListener('click', () => {
            configureFindingForm();
            openDialog(findingFormDialog);
        });

        rows.forEach((row) => {
            row.querySelector('[data-finding-detail]')?.addEventListener('click', () => {
                configureDetailDialog(readFinding(row));
                openDialog(detailDialog);
            });

            row.querySelector('[data-finding-edit]')?.addEventListener('click', () => {
                configureFindingForm(readFinding(row));
                openDialog(findingFormDialog);
            });

            row.querySelector('[data-finding-status]')?.addEventListener('click', () => {
                const finding = readFinding(row);
                selectedStatusRow = row;
                statusForm.elements.status.value = finding.status_key;
                statusDialog.querySelector('[data-status-finding-label]').textContent = `${finding.id} • ${finding.item}`;
                openDialog(statusDialog);
            });

            row.querySelector('[data-photo-preview]')?.addEventListener('click', () => {
                const finding = readFinding(row);
                const preview = photoDialog.querySelector('[data-photo-dialog-preview]');
                preview.className = `finding-photo-preview finding-photo--${finding.photo_variant}`;
                photoDialog.querySelector('[data-photo-dialog-label]').textContent = finding.photo_label;
                openDialog(photoDialog);
            });
        });

        findingForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const isEditing = Boolean(findingForm.elements.finding_id.value);
            closeDialog(findingFormDialog);
            showToast(isEditing ? 'Perubahan temuan berhasil disimulasikan.' : 'Temuan baru berhasil disimulasikan.');
        });

        statusForm?.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!selectedStatusRow) {
                return;
            }

            const statusKey = statusForm.elements.status.value;
            const statusLabels = {
                open: 'Open',
                review: 'Review',
                resolved: 'Resolved',
            };
            const finding = readFinding(selectedStatusRow);
            const statusBadge = selectedStatusRow.querySelector('[data-row-status]');

            finding.status_key = statusKey;
            finding.status = statusLabels[statusKey];
            selectedStatusRow.dataset.status = statusKey;
            selectedStatusRow.dataset.finding = JSON.stringify(finding);
            statusBadge.className = `status-badge status-badge--${statusKey}`;
            statusBadge.innerHTML = '<span aria-hidden="true"></span>';
            statusBadge.append(statusLabels[statusKey]);

            updateSummary();
            applyFilters();
            closeDialog(statusDialog);
            showToast(`Status ${finding.id} diubah menjadi ${statusLabels[statusKey]}.`);
            selectedStatusRow = null;
        });

        photoInput?.addEventListener('change', () => {
            clearUploadPreview();
            const [file] = photoInput.files;

            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            uploadObjectUrl = URL.createObjectURL(file);
            uploadPreview.src = uploadObjectUrl;
            uploadPreview.hidden = false;
            uploadPlaceholder.hidden = true;
        });

        document.querySelectorAll('[data-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => closeDialog(button.closest('dialog')));
        });

        document.querySelectorAll('.findings-dialog').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    closeDialog(dialog);
                }
            });
        });

        refreshIcons();
        updateSummary();
        applyFilters();
    });
}
