import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { notifyError, notifySuccess } from './alert';

// Shared by every layout: flash messages become toasts, and the school's brand colour follows the page.
export function useAppShell() {
    const page = usePage();

    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) notifySuccess(flash.success);
            if (flash?.error) notifyError(flash.error);
        },
        { immediate: true },
    );

    watch(
        () => page.props.school,
        (school) => {
            const style = document.documentElement.style;
            if (school?.brand_color) {
                style.setProperty('--primary', school.brand_color);
                style.setProperty('--primary-soft', school.brand_color_soft);
            } else {
                style.removeProperty('--primary');
                style.removeProperty('--primary-soft');
            }
        },
    );

    return page;
}
