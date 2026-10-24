import { onBeforeUnmount, reactive, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { sendJson } from '@/lib/http';

export const cellKey = (enrollmentId, subjectId) => `${enrollmentId}-${subjectId}`;

/**
 * Autosave for a score sheet. Edits wait 500 ms, then go to the server together in one request;
 * each cell gets 'pending' → 'saving' → 'saved' or 'error' (with the server's message). A cell typed
 * again while its save is in flight keeps the newer value and is saved again.
 */
export function useScoreSaver({ examId, classroomId, initial }) {
    const values = reactive({ ...initial });
    const state = reactive({}); // key → pending | saving | saved | error
    const errors = reactive({}); // key → message
    const closed = ref(false); // the exam stopped taking scores (423)
    const pending = new Map();
    let timer = null;
    let inFlight = 0;

    function set(enrollmentId, subjectId, value) {
        const key = cellKey(enrollmentId, subjectId);
        values[key] = value;
        state[key] = 'pending';
        delete errors[key];
        pending.set(key, { enrollment_id: enrollmentId, subject_id: subjectId, value: value === '' ? null : value });
        clearTimeout(timer);
        timer = setTimeout(flush, 500);
    }

    async function flush() {
        clearTimeout(timer);
        if (pending.size === 0) return;
        const cells = [...pending.values()];
        const sent = new Map([...pending.entries()].map(([key, cell]) => [key, cell.value]));
        pending.clear();
        sent.forEach((_, key) => (state[key] = 'saving'));
        inFlight++;

        const fail = (message) =>
            sent.forEach((_, key) => {
                if (!pending.has(key)) {
                    state[key] = 'error';
                    errors[key] = message;
                }
            });

        try {
            const { status, body } = await sendJson('PUT', `/exams/${examId}/scores`, {
                classroom_id: classroomId,
                cells,
            });
            if (status === 423) {
                closed.value = true;
                fail(body.message || trans('exams.errors.not_open_for_scores'));
            } else if (status === 419) {
                fail(trans('exams.grid.session_expired'));
            } else if (status !== 200) {
                fail(body.message || trans('exams.grid.save_failed'));
            } else {
                for (const result of body.cells) {
                    const key = cellKey(result.enrollment_id, result.subject_id);
                    if (pending.has(key)) continue; // typed again meanwhile: the newer value wins
                    if (result.ok) {
                        state[key] = 'saved';
                        values[key] = result.value ?? ''; // the server's spelling: "12.50" → "12.5", "a" → "A"
                    } else {
                        state[key] = 'error';
                        errors[key] = result.error;
                    }
                }
            }
        } catch {
            fail(trans('exams.grid.offline'));
        } finally {
            inFlight--;
        }
    }

    const busy = () => pending.size > 0 || inFlight > 0;

    // Closing the tab with unsaved cells asks first.
    const guard = (event) => {
        if (busy()) {
            event.preventDefault();
            event.returnValue = '';
        }
    };
    window.addEventListener('beforeunload', guard);
    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', guard);
        flush();
    });

    return { values, state, errors, closed, set, flush, busy };
}
