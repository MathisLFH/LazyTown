<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const policyVersion = String(page.props.privacyPolicyVersion ?? '1.1');
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
                    Version {{ policyVersion }} · Stand: 15.10.2026
                </p>
            </header>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    1. Einleitung und Überblick
                </h2>
                <p>
                    Wir nehmen den Schutz Ihrer persönlichen Daten sehr ernst.
                    Wir behandeln Ihre personenbezogenen Daten vertraulich und
                    entsprechend der gesetzlichen Datenschutzvorschriften
                    (insbesondere der europäischen Datenschutz-Grundverordnung,
                    DSGVO) sowie dieser Datenschutzerklärung.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    2. Verantwortlicher für die Datenverarbeitung
                </h2>
                <p>
                    Die Datenverarbeitung auf dieser Website erfolgt durch den
                    Websitebetreiber:
                </p>
                <address class="not-italic">
                    Lazytown GMBH<br />
                    Expoplaza 10<br />
                    30179 Hannover<br />
                    E-Mail:
                    <a
                        href="mailto:info@leibniz-fh.de"
                        class="underline underline-offset-4"
                        >info@leibniz-fh.de</a
                    >
                </address>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    3. Erfassung und Verarbeitung personenbezogener Daten
                </h2>
                <p>
                    Wir erheben und verarbeiten personenbezogene Daten nur,
                    soweit dies zur Bereitstellung einer funktionsfähigen
                    Website sowie unserer Inhalte und Leistungen erforderlich
                    ist.
                </p>
                <ul class="list-disc space-y-2 pl-6">
                    <li>
                        <strong>Registrierung und Login:</strong> Wenn Sie sich
                        auf unserer Website registrieren, erheben wir die von
                        Ihnen eingegebenen Daten (z. B. Name, E-Mail-Adresse).
                        Diese Daten werden verarbeitet, um Ihnen das
                        Nutzerprofil und die damit verbundenen Dienste zur
                        Verfügung zu stellen. Rechtsgrundlage für diese
                        Verarbeitung ist die Erfüllung eines Vertrags oder die
                        Durchführung vorvertraglicher Maßnahmen (Art. 6 Abs. 1
                        lit. b DSGVO).
                    </li>
                    <li>
                        <strong>Kontakt:</strong> Treten Sie per E-Mail oder
                        Kontaktformular mit uns in Kontakt, werden Ihre Angaben
                        zur Bearbeitung der Anfrage und für mögliche
                        Anschlussfragen gespeichert. Rechtsgrundlage hierfür ist
                        unser berechtigtes Interesse an der Beantwortung Ihres
                        Anliegens (Art. 6 Abs. 1 lit. f DSGVO) oder, falls die
                        Anfrage auf den Abschluss eines Vertrages abzielt, Art.
                        6 Abs. 1 lit. b DSGVO.
                    </li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    4. E-Mail-Newsfeed / Newsletter
                </h2>
                <p>
                    Wenn Sie den auf der Website angebotenen Newsfeed beziehen
                    möchten, benötigen wir von Ihnen eine E-Mail-Adresse sowie
                    Informationen, welche uns die Überprüfung gestatten, dass
                    Sie der Inhaber der angegebenen E-Mail-Adresse sind und mit
                    dem Empfang des Newsfeeds einverstanden sind
                    (Double-Opt-In-Verfahren).
                </p>
                <p>
                    Die Verarbeitung dieser Daten erfolgt ausschließlich auf
                    Grundlage Ihrer aktiven Einwilligung (Art. 6 Abs. 1 lit. a
                    DSGVO). Sie können diese Einwilligung zur Speicherung der
                    Daten, der E-Mail-Adresse sowie deren Nutzung zum Versand
                    des Newsfeeds jederzeit widerrufen, etwa über den
                    „Austragen“-Link in der E-Mail.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    5. Weitergabe von Daten an Dritte und Partner
                </h2>
                <p>
                    Eine Übermittlung Ihrer persönlichen Daten an Dritte zu
                    anderen als den im Folgenden aufgeführten Zwecken findet
                    nicht statt. Wir geben Ihre persönlichen Daten nur an Dritte
                    weiter, wenn:
                </p>
                <ul class="list-disc space-y-2 pl-6">
                    <li>
                        Sie Ihre ausdrückliche Einwilligung dazu erteilt haben
                        (Art. 6 Abs. 1 lit. a DSGVO),
                    </li>
                    <li>
                        dies gesetzlich zulässig und für die Abwicklung von
                        Vertragsverhältnissen mit Ihnen erforderlich ist (Art. 6
                        Abs. 1 lit. b DSGVO),
                    </li>
                    <li>
                        für die Weitergabe eine gesetzliche Verpflichtung
                        besteht (Art. 6 Abs. 1 lit. c DSGVO).
                    </li>
                </ul>
                <p>
                    Soweit wir für die Bereitstellung unserer Dienste externe
                    Dienstleister (z. B. Hosting-Provider,
                    E-Mail-Versand-Dienstleister) einsetzen, arbeiten diese als
                    sogenannte Auftragsverarbeiter für uns. Diese sind
                    vertraglich streng an unsere Weisungen gebunden und dürfen
                    die Daten nicht für eigene Zwecke nutzen (Vertrag zur
                    Auftragsverarbeitung gemäß Art. 28 DSGVO).
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">6. Speicherdauer</h2>
                <p>
                    Soweit innerhalb dieser Datenschutzerklärung keine
                    speziellere Speicherdauer genannt wurde, verbleiben Ihre
                    personenbezogenen Daten bei uns, bis der Zweck für die
                    Datenverarbeitung entfällt. Wenn Sie ein berechtigtes
                    Löschersuchen geltend machen oder eine Einwilligung zur
                    Datenverarbeitung widerrufen, werden Ihre Daten gelöscht,
                    sofern wir keine anderen rechtlich zulässigen Gründe für die
                    Speicherung Ihrer personenbezogenen Daten haben (z. B.
                    steuer- oder handelsrechtliche Aufbewahrungsfristen).
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">
                    7. Ihre Rechte als betroffene Person
                </h2>
                <p>
                    Sie haben im Rahmen der geltenden gesetzlichen Bestimmungen
                    jederzeit das Recht auf:
                </p>
                <ul class="list-disc space-y-2 pl-6">
                    <li>
                        Unentgeltliche <strong>Auskunft</strong> über Ihre
                        gespeicherten personenbezogenen Daten, deren Herkunft
                        und Empfänger und den Zweck der Datenverarbeitung (Art.
                        15 DSGVO).
                    </li>
                    <li>
                        <strong>Berichtigung</strong> unrichtiger oder
                        unvollständiger Daten (Art. 16 DSGVO).
                    </li>
                    <li>
                        <strong>Löschung</strong> Ihrer bei uns gespeicherten
                        Daten (Art. 17 DSGVO).
                    </li>
                    <li>
                        <strong>Einschränkung der Datenverarbeitung</strong>
                        (Art. 18 DSGVO).
                    </li>
                    <li>
                        <strong>Datenübertragbarkeit</strong> (Art. 20 DSGVO).
                    </li>
                    <li>
                        <strong>Widerruf</strong> erteilter Einwilligungen für
                        die Zukunft (Art. 7 Abs. 3 DSGVO).
                    </li>
                </ul>
                <p>
                    Des Weiteren steht Ihnen ein
                    <strong
                        >Beschwerderecht bei einer zuständigen
                        Datenschutzaufsichtsbehörde</strong
                    >
                    zu (Art. 77 DSGVO). In der Regel können Sie sich hierfür an
                    die Aufsichtsbehörde Ihres üblichen Aufenthaltsortes oder
                    Arbeitsplatzes oder unseres Unternehmenssitzes wenden.
                </p>
            </section>
        </article>
    </main>
</template>
