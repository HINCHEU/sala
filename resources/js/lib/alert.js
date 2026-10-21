import Swal from 'sweetalert2';
import { trans } from 'laravel-vue-i18n';

// Built per call so button labels follow the current language.
const dialog = () =>
    Swal.mixin({
        buttonsStyling: false,
        reverseButtons: true,
        confirmButtonText: trans('app.save'),
        cancelButtonText: trans('app.cancel'),
        customClass: {
            popup: 's-swal',
            title: 's-swal-title',
            htmlContainer: 's-swal-text',
            actions: 's-swal-actions',
            confirmButton: 's-btn s-btn-primary',
            denyButton: 's-btn s-btn-danger',
            cancelButton: 's-btn',
        },
    });

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    timer: 3000,
    timerProgressBar: true,
    showConfirmButton: false,
    customClass: { popup: 's-swal-toast' },
});

// Destructive confirm: focus starts on Cancel, outside click does nothing.
export async function confirmDanger({ title, text, confirmText }) {
    const result = await dialog().fire({
        icon: 'warning',
        title,
        text,
        showCancelButton: true,
        focusCancel: true,
        allowOutsideClick: false,
        confirmButtonText: confirmText,
        customClass: { confirmButton: 's-btn s-btn-danger' },
    });
    return result.isConfirmed;
}

export const notifySuccess = (title) => toast.fire({ icon: 'success', title });
export const notifyError = (title, text) =>
    dialog().fire({ icon: 'error', title, text, confirmButtonText: trans('app.close') });
