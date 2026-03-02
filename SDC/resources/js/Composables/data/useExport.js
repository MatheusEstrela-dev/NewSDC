import { ref } from 'vue';
import { route } from 'ziggy-js';

export function useExport(routeName, defaultFilters = {}) {
    const showExportModal = ref(false);

    /**
     * Triggers the CSV export by redirecting to the backend route with filters.
     * 
     * @param {Object} params - Parameters from the Export modal (e.g. type, dates)
     * @param {Object} currentFilters - Current active filters in the listing (e.g. search, status)
     */
    const handleExport = (params, currentFilters = {}) => {
        // Combine current screen filters with modal parameters
        const exportFilters = {
            ...defaultFilters,
            ...currentFilters,
            ...params,
        };

        // Clean up null/undefined/empty values to keep URL clean
        Object.keys(exportFilters).forEach((key) => {
            if (
                exportFilters[key] === null ||
                exportFilters[key] === '' ||
                exportFilters[key] === undefined
            ) {
                delete exportFilters[key];
            }
        });

        // Generate URL and redirect
        const url = route(routeName, exportFilters);
        window.location.href = url;

        showExportModal.value = false;
    };

    const openExportModal = () => {
        showExportModal.value = true;
    };

    const closeExportModal = () => {
        showExportModal.value = false;
    };

    return {
        showExportModal,
        openExportModal,
        closeExportModal,
        handleExport,
    };
}
