import { usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    ClipboardList,
    FileBarChart,
    FileText,
    History,
    LayoutGrid,
    MapPinCheck,
    Settings,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { index as adminAttendances } from '@/routes/admin/attendances';
import { index as adminLeaves } from '@/routes/admin/leaves';
import { index as adminReports } from '@/routes/admin/reports';
import { edit as adminSettings } from '@/routes/admin/settings';
import { index as adminTeachers } from '@/routes/admin/teachers';
import { history, show as attendanceShow } from '@/routes/attendance';
import { index as leaves } from '@/routes/leaves';
import type { NavItem } from '@/types';

const guruNavItems: NavItem[] = [
    { title: 'Dasbor', href: dashboard(), icon: LayoutGrid },
    { title: 'Absen', href: attendanceShow(), icon: MapPinCheck },
    { title: 'Riwayat', href: history(), icon: History },
    { title: 'Izin', href: leaves(), icon: FileText },
];

const adminNavItems: NavItem[] = [
    { title: 'Dasbor', href: admin.dashboard(), icon: LayoutGrid },
    { title: 'Guru', href: adminTeachers(), icon: Users },
    { title: 'Absensi', href: adminAttendances(), icon: CalendarCheck },
    { title: 'Izin', href: adminLeaves(), icon: ClipboardList },
    { title: 'Laporan', href: adminReports(), icon: FileBarChart },
    { title: 'Pengaturan', href: adminSettings(), icon: Settings },
];

/** Main navigation items for the signed-in user, chosen by role. */
export function useNavItems(): NavItem[] {
    const { auth } = usePage().props;

    return auth.user?.role === 'admin' ? adminNavItems : guruNavItems;
}

/** Where the logo / home link should point for the signed-in user. */
export function useHomeHref(): NavItem['href'] {
    const { auth } = usePage().props;

    return auth.user?.role === 'admin' ? admin.dashboard() : dashboard();
}
