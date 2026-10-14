export type CsvMatchRow = {
    date: string;
    kickoff_time: string | null;
    home: string;
    away: string;
    city: string | null;
    season: string;
};

export type MatchRow = {
    id: number | null;
    team_id: number;
    season: string;
    date: string;
    kickoff_time: string | null;
    versus: string;
    at_home: boolean;
    city: string | null;
};

export type GroupedMatchMonth = {
    monthKey: string;
    monthLabel: string;
    rows: MatchRow[];
};

const monthFormatter = new Intl.DateTimeFormat('de-DE', {
    month: 'long',
    year: 'numeric',
});

const dayFormatter = new Intl.DateTimeFormat('de-DE', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
});

export function formatDate(value: string): string {
    const date = new Date(`${value}T00:00:00`);

    return dayFormatter.format(date).replace('.', '');
}

export function groupByMonth(rows: MatchRow[]): GroupedMatchMonth[] {
    const grouped = new Map<string, GroupedMatchMonth>();

    rows.forEach((row) => {
        const date = new Date(`${row.date}T00:00:00`);
        const monthKey = `${date.getFullYear()}-${date.getMonth()}`;
        const existing = grouped.get(monthKey);

        if (existing) {
            existing.rows.push(row);

            return;
        }

        grouped.set(monthKey, {
            monthKey,
            monthLabel: monthFormatter.format(date),
            rows: [row],
        });
    });

    return Array.from(grouped.values());
}

export function nextMatch(rows: MatchRow[]): MatchRow | null {
    const now = new Date();

    return (
        rows
            .filter((row) => {
                const kickoff = row.kickoff_time ? `${row.date}T${row.kickoff_time}:00` : `${row.date}T23:59:59`;

                return new Date(kickoff).getTime() >= now.getTime();
            })
            .sort((left, right) => {
                const leftValue = new Date(`${left.date}T${left.kickoff_time ?? '23:59'}:00`).getTime();
                const rightValue = new Date(`${right.date}T${right.kickoff_time ?? '23:59'}:00`).getTime();

                return leftValue - rightValue;
            })[0] ?? null
    );
}

export function mapCsvToMatches(rows: CsvMatchRow[], ownName: string, teamId: number, season: string): MatchRow[] {
    return rows
        .flatMap((row) => {
            if (row.home === ownName) {
                return [
                    {
                        id: null,
                        team_id: teamId,
                        season,
                        date: row.date,
                        kickoff_time: row.kickoff_time,
                        versus: row.away,
                        at_home: true,
                        city: row.city,
                    },
                ];
            }

            if (row.away === ownName) {
                return [
                    {
                        id: null,
                        team_id: teamId,
                        season,
                        date: row.date,
                        kickoff_time: row.kickoff_time,
                        versus: row.home,
                        at_home: false,
                        city: row.city,
                    },
                ];
            }

            return [];
        })
        .sort((left, right) => {
            if (left.date === right.date) {
                const leftTime = left.kickoff_time ?? '23:59';
                const rightTime = right.kickoff_time ?? '23:59';

                return leftTime.localeCompare(rightTime);
            }

            return left.date.localeCompare(right.date);
        });
}
