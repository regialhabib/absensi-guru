/**
 * Clock time in the school's timezone, so it never depends on the device setting.
 */
export function formatTime(iso: string | null, timeZone: string): string {
    if (iso === null) {
        return '-';
    }

    return new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone,
    });
}
