import { ref } from 'vue';

export type ScheduleEntry = {
    id: number;
    title: string;
    type: 'Spiel' | 'Training';
    date: string;
    time: string;
    place: string;
    status: 'geplant' | 'verschoben' | 'abgesagt';
};

export function useSchedule() {
    const entries = ref<ScheduleEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchSchedule() {
        loading.value = true;
        error.value = null;
        try {
            entries.value = [
                { id: 1, title: 'Training Herren 1', type: 'Training', date: '02.09.2026', time: '18:30', place: 'Halle Nord', status: 'geplant' },
                { id: 2, title: 'Heimspiel gegen TSV West', type: 'Spiel', date: '05.09.2026', time: '15:00', place: 'Sportzentrum', status: 'abgesagt' },
                { id: 3, title: 'Training Herren 1', type: 'Training', date: '07.09.2026', time: '18:30', place: 'Halle Nord', status: 'geplant' },
                { id: 4, title: 'Auswärtsspiel gegen SV Blau-Weiß Eintracht 04', type: 'Spiel', date: '12.09.2026', time: '', place: 'Sportplatz an der Bahnhofstraße 22, 30161 Hannover', status: 'geplant' },
                { id: 5, title: 'Training Herren 1', type: 'Training', date: '14.09.2026', time: '18:30', place: 'Halle Nord', status: 'verschoben' },
];
        } catch (e) {
            error.value = 'Spielplan konnte nicht geladen werden.';
        } finally {
            loading.value = false;
        }
    }

    return { entries, loading, error, fetchSchedule };
}