export type TrainingRow = {
    id: number | null;
    team_id: number;
    team_name: string;
    weekday: string;
    starts_at: string;
    ends_at: string;
    gym_id: number | null;
    gym_name: string | null;
};

export type TrainingConflict = {
    leftId: number | null;
    rightId: number | null;
    gym_name: string | null;
    weekday: string;
};

export const weekDays = [
    'Montag',
    'Dienstag',
    'Mittwoch',
    'Donnerstag',
    'Freitag',
    'Samstag',
    'Sonntag',
] as const;

export function groupByWeekday(
    rows: TrainingRow[],
): Record<string, TrainingRow[]> {
    return weekDays.reduce<Record<string, TrainingRow[]>>(
        (grouped, weekday) => {
            grouped[weekday] = rows
                .filter((row) => row.weekday === weekday)
                .sort((left, right) =>
                    left.starts_at.localeCompare(right.starts_at),
                );

            return grouped;
        },
        {},
    );
}

export function findConflicts(rows: TrainingRow[]): TrainingConflict[] {
    const conflicts: TrainingConflict[] = [];
    const groupedByDayAndGym = new Map<string, TrainingRow[]>();

    rows.forEach((row) => {
        const key = `${row.weekday}::${(row.gym_name ?? '').toLowerCase()}`;
        const group = groupedByDayAndGym.get(key) ?? [];
        group.push(row);
        groupedByDayAndGym.set(key, group);
    });

    groupedByDayAndGym.forEach((groupRows) => {
        const sorted = [...groupRows].sort((left, right) =>
            left.starts_at.localeCompare(right.starts_at),
        );

        for (let index = 0; index < sorted.length - 1; index++) {
            const current = sorted[index];
            const next = sorted[index + 1];

            if (current.ends_at > next.starts_at) {
                conflicts.push({
                    leftId: current.id,
                    rightId: next.id,
                    gym_name: current.gym_name,
                    weekday: current.weekday,
                });
            }
        }
    });

    return conflicts;
}
