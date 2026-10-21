import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const STAFF_ROLES = ['director', 'academic_office', 'registrar', 'accountant'];

// Staff and platform admins get the sidebar; teachers, guardians and students the phone-first layout.
export function useRoleLayout() {
    const page = usePage();

    return computed(() =>
        page.props.can.platform || page.props.roles.some((role) => STAFF_ROLES.includes(role))
            ? AdminLayout
            : TeacherLayout,
    );
}
