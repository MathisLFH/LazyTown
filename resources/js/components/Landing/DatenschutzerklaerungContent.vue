<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const policyVersion = String(page.props.privacyPolicyVersion ?? '1.0');
</script>

<template>
    <Head title="Datenschutzerklärung" />
    <main class="min-h-screen bg-muted/30 p-6">
        <article
            class="mx-auto max-w-4xl space-y-8 rounded-2xl border border-sidebar-border/70 bg-background p-8 shadow-sm sm:p-12"
        >
            <header class="space-y-3">
                <h1 class="text-3xl font-bold">Datenschutzerklärung</h1>
                <p class="text-sm text-muted-foreground">
                    Version {{ policyVersion }} · Stand: 14.10.2026
                </p>
                <p>
                    Diese Erklärung ist eine technische und inhaltliche Vorlage
                    für LazyTown. Sie ersetzt keine rechtliche Prüfung. Angaben
                    in eckigen Klammern müssen vor dem produktiven Einsatz
                    ergänzt und geprüft werden.
                </p>
            </header>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">1. Verantwortliche Stelle</h2>
                <p>
                    Verantwortlich für die Verarbeitung personenbezogener Daten
                    ist:
                </p>
                <address class="not-italic">
                    [Name des Betreibers oder Vereins]<br />
                    [Anschrift]<br />
                    [E-Mail-Adresse]
                </address>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    2. Welche Daten verarbeitet LazyTown?
                </h2>
                <p>
                    Für die Registrierung und den Betrieb der Vereinsverwaltung
                    können insbesondere folgende Angaben verarbeitet werden:
                    Name, E-Mail-Adresse, Passwort (ausschließlich geschützt
                    gespeichert), Vereins- und Teamzugehörigkeiten sowie
                    freiwillige Profilangaben wie Geburtsdatum, Geburtsort,
                    Nationalität, Anschrift, Ort und Telefonnummer.
                </p>
                <p>
                    Zusätzlich speichert LazyTown technische Angaben, die für
                    den sicheren Betrieb und die Zuordnung von Vorgängen
                    erforderlich sind, sowie Benachrichtigungen, Einladungen und
                    Einstellungen, die innerhalb der Anwendung genutzt werden.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    3. Zwecke und Rechtsgrundlagen
                </h2>
                <p>
                    Die Daten werden verwendet, um Benutzerkonten anzulegen,
                    Teams und Vereine zu verwalten, Einladungen und
                    Benachrichtigungen bereitzustellen, die Anwendung
                    abzusichern und angeforderte Funktionen auszuführen.
                </p>
                <p>
                    Die konkrete Rechtsgrundlage, die Interessenabwägung und
                    gegebenenfalls die Aufbewahrungsfristen sind durch die
                    verantwortliche Stelle rechtlich zu prüfen und zu ergänzen.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    4. Weitergabe und Dienstleister
                </h2>
                <p>
                    Eine Weitergabe erfolgt nur, soweit sie für den Betrieb, die
                    Sicherheit, die Abrechnung eines gewählten Abos oder
                    aufgrund gesetzlicher Pflichten erforderlich ist. Die
                    konkret eingesetzten Hosting-, E-Mail-, Zahlungs- und
                    sonstigen Dienstleister sowie deren Standorte und
                    Vereinbarungen sind vor Veröffentlichung zu ergänzen.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">5. Speicherdauer</h2>
                <p>
                    Personenbezogene Daten werden nur so lange gespeichert, wie
                    dies für die genannten Zwecke, gesetzliche Pflichten oder
                    die nachvollziehbare Dokumentation einer Einwilligung
                    erforderlich ist. Die konkreten Fristen müssen von der
                    verantwortlichen Stelle festgelegt werden.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    6. Rechte betroffener Personen
                </h2>
                <p>
                    Betroffene Personen können im Rahmen der gesetzlichen
                    Voraussetzungen Auskunft, Berichtigung, Löschung,
                    Einschränkung der Verarbeitung, Datenübertragbarkeit und
                    Widerspruch verlangen sowie eine erteilte Einwilligung für
                    die Zukunft widerrufen. Außerdem besteht ein Beschwerderecht
                    bei einer zuständigen Aufsichtsbehörde. Zuständige
                    Kontaktwege sind noch zu ergänzen.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    7. Änderungen dieser Erklärung
                </h2>
                <p>
                    Änderungen werden mit einer neuen Versionsnummer
                    veröffentlicht. Bei der Registrierung wird die akzeptierte
                    Version zusammen mit dem Zeitpunkt serverseitig am
                    Benutzerkonto gespeichert. Ob und wann bei einer Änderung
                    eine erneute Zustimmung erforderlich ist, wird für jede neue
                    Version geprüft.
                </p>
            </section>
        </article>
    </main>
</template>
