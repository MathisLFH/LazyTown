import { ref } from 'vue';
import { useAuth } from './useAuth';

export type ScheduleEntry = {
    id: number;
    title: string;
    type: 'Spiel' | 'Training';
    date: string;
    time: string;
    place: string;
    status: 'geplant' | 'verschoben' | 'abgesagt';
};

const bookings: Record<string, string> = {
    'Montag-17:30': 'Training Jugend',
    'Dienstag-19:00': 'Training Herren 1',
    'Donnerstag-17:30': 'Training Damen 1',
    'Freitag-19:00': 'Freies Spiel',
};

function bookingFor(day: string, time: string): string | undefined {
    return bookings[`${day}-${time}`];
}

export function useGymnasiumPlan() {
    const { activeRole } = useAuth();
    const entries = ref<ScheduleEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const selectedSlot = ref<string | null>(null);
    const weekDays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'];
    const timeSlots = ['16:00', '17:30', '19:00'];

    return { activeRole, entries, loading, error, selectedSlot, weekDays, timeSlots, bookingFor };
}