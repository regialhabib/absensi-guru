/**
 * Types mirroring the backend enums and models of the attendance app.
 * Dates travel as strings: `date` fields as 'YYYY-MM-DD', datetimes as ISO 8601,
 * time-of-day fields as 'HH:mm:ss'. Decimal columns may arrive as strings
 * (Eloquent `decimal:N` casts) or numbers, hence `number | string`.
 */

export type UserRole = 'admin' | 'guru';
export type AttendanceStatus = 'hadir' | 'terlambat';
export type LeaveType = 'izin' | 'sakit' | 'cuti';
export type LeaveStatus = 'pending' | 'approved' | 'rejected';
export type DayStatus =
    | 'hadir'
    | 'terlambat'
    | 'izin'
    | 'sakit'
    | 'cuti'
    | 'alpa'
    | 'libur'
    | 'belum_absen';

export type Decimal = number | string;

/** A teacher / admin row as listed by the admin area (same shape as `auth.user`). */
export type Teacher = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    nip: string | null;
    phone: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type Attendance = {
    id: number;
    user_id: number;
    date: string;

    check_in_at: string;
    check_in_latitude: Decimal;
    check_in_longitude: Decimal;
    check_in_accuracy: Decimal;
    check_in_distance: Decimal;
    check_in_photo: string;
    check_in_face_detected: boolean;
    status: AttendanceStatus;
    late_minutes: number;

    check_out_at: string | null;
    check_out_latitude: Decimal | null;
    check_out_longitude: Decimal | null;
    check_out_accuracy: Decimal | null;
    check_out_distance: Decimal | null;
    check_out_photo: string | null;
    check_out_face_detected: boolean | null;
    early_leave_minutes: number;

    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    updated_at: string;

    user?: Teacher;
};

export type LeaveRequest = {
    id: number;
    user_id: number;
    type: LeaveType;
    start_date: string;
    end_date: string;
    reason: string;
    attachment: string | null;
    status: LeaveStatus;
    reviewed_by: number | null;
    reviewed_at: string | null;
    review_note: string | null;
    created_at: string;
    updated_at: string;

    user?: Teacher;
    reviewer?: Teacher | null;
};

export type SchoolSetting = {
    id: number;
    name: string;
    latitude: number | null;
    longitude: number | null;
    radius_meters: number;
    max_accuracy_meters: number;
    late_tolerance_minutes: number;
    check_in_opens_at: string;
    check_out_closes_at: string;
    created_at: string;
    updated_at: string;
};

export type WorkSchedule = {
    id: number;
    /** 0 = Sunday .. 6 = Saturday (Carbon dayOfWeek). */
    day_of_week: number;
    is_workday: boolean;
    starts_at: string | null;
    ends_at: string | null;
    created_at: string;
    updated_at: string;
};

/** Output of DailyStatusResolver::summarize(). */
export type MonthlySummary = {
    hadir: number;
    terlambat: number;
    izin: number;
    sakit: number;
    cuti: number;
    alpa: number;
};

/** Output of DailyStatusResolver::resolveMonth(), keyed by 'YYYY-MM-DD'. */
export type MonthStatusMap = Record<string, DayStatus>;

/** Generic Laravel paginator payload as serialized for Inertia. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export const DAY_STATUS_LABELS: Record<DayStatus, string> = {
    hadir: 'Hadir',
    terlambat: 'Terlambat',
    izin: 'Izin',
    sakit: 'Sakit',
    cuti: 'Cuti',
    alpa: 'Alpa',
    libur: 'Libur',
    belum_absen: 'Belum Absen',
};

export const LEAVE_TYPE_LABELS: Record<LeaveType, string> = {
    izin: 'Izin',
    sakit: 'Sakit',
    cuti: 'Cuti',
};

export const LEAVE_STATUS_LABELS: Record<LeaveStatus, string> = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
};
