# Fleximport

Ein Plugin für Stud.IP, um alle möglichen Dinge zu importieren wie Veranstaltungen, Nutzer, Termine, Einrichtungen und so weiter.

Die Idee des Plugins ist einfach: Man importiert erst einmal Daten wie aus einer CSV-Datei oder einer externen Quelle wie einem Google-Doc-Spreadsheet als Rohdaten in die Stud.IP-Datenbank und anschließend werden die Daten Zeile für Zeile importiert. Da solche Rohdaten meist aus anderen Systemen kommen und ganz eigene Bezeichner haben, müssen die Daten entsprechend auf die Zieltabelle gemapped werden. Das macht man im Plugin über die Oberfläche. Man kann für jedes Attribut der Stud.IP-Tabelle (zum Beispiel das Feld Email der Tabelle auth_user_md5 für einen Nutzer) festlegen, welcher Wert aus der Datentabelle verwendet werden soll. Dadurch ist es unerheblich, ob in der Datentabelle die Emailadresse in der Spalte "email" oder "E-Mail" oder "Email-Adresse" steht. Wichtig ist nur, dass die Daten gemapped werden und dann kommen die richtigen Werte ins Stud.IP. Ändert sich am Ende ein Bezeichner, oder soll noch die Matrikelnummer zusätzlich importiert werden, muss man nur kurz das Mapping über die Oberfläche anpassen, anstatt das Plugin umzuprogrammieren.

## 1) Prozesse

Da Fleximport ein sehr mächtiges Importtool ist, möchte man vielleicht verschiedene Dinge gleichzeitig jeden Tag über einen Cronjob importieren, andere Dinge aber nur einmal pro Semester per Knopfdruck. Dazu gibt es in Fleximport Prozesse. Jeder Prozess ist im Grunde ein Importtool, das man frei konfigurieren kann. So kann ein Import aus einem Fremdsystem über drei Tabellen (Veranstaltungen, Nutzer und Teilnehmerdaten) ein einzelner Prozess mit drei Tabellen sein. Aber vielleicht will man die Freiheiten haben, Fleximport auch hin und wieder mit einer CSV-Datei zu befüttern, wodurch externe Accounts für eine spezielle Nutzerdomäne (zum Beispiel Alumni) angelegt werden. Das wäre dann ein zweiter Prozess.

Jeder dieser Prozesse bekommt einen eigenen Reiter, der frei benannt werden kann. Man kann also jedem Prozess einen Namen geben (den Namen des Reiters), einen Beschreibungstext, der erklärt, was mit dem Prozess gemacht werden soll. Und man kann noch definieren, ob der Prozess über den Stud.IP-Cronjob angestoßen werden soll oder nicht. Damit ist noch nicht geklärt, wann dieser Cronjob läuft. Diesen Zeitpunkt muss man in der Cronjobverwaltung festlegen, was auch über die Nutzeroberfläche von Stud.IP geht.

Jedem Prozess kann man mehrere Tabellen zuordnen, die in diesem Prozess nach und nach importiert werden.

Sowohl Prozesse als auch Tabellen innerhalb der Prozesse werden überdies in alphabetischer Reihenfolger abgearbeitet. Will man also die Reihenfolge verändern, muss man entweder den Prozess oder die Tabelle umbennen, was in der Regel kein Problem sein sollte.

## 2) Importtabellen

Einem Prozess kann man sodann die Tabellen zuordnen. Man fügt über die Sidebar-Aktion "Tabelle hinzufügen" eine neue Tabelle hinzu. Folgende Angaben sind dabei wichtig:

**Name der Tabelle**: Das ist der Name, den die Tabelle in der Stud.IP-Datenbank einnimmt. Aufgepasst! Niemals sollte die Tabelle einen Namen haben wie "auth_user_md5" oder "Institute", weil das Tabellen sind, die in der Stud.IP-Datenbank schon auftauchen und dort eine wichtige Rolle spielen! Daher ist der Präfix "fleximport_" schon vorausgefüllt. Damit ist gewährleistet, dass Sie keine Namenskollisionen bekommen. Vermeiden Sie auch die Namen "fleximport_tables", "fleximport_configs", "fleximport_mapped_items" und "fleximport_processes", weil das Tabellen sind, die das Fleximport-Plugin selbst schon braucht. Falls ein Plugin (das ist kein Stud.IP-Plugin, sondern eine spezielle Klasse, die man als Plugin im Plugin bezeichnen könnte, siehe unten) Ihnen beim Fleximport helfen soll, wählen Sie auf jeden Fall den Namen der Pluginklasse.

**Zweck der Tabelle**: Hier wählen Sie aus, ob mit der Tabelle Nutzer oder Veranstaltungen oder wasauchimmer import werden sollen. Zwei besondere Werte sind `SORM-Objekt` und `Tabelle nicht importieren`. Mit SORM-Objekt ist es im Grunde möglich, völlig beliebige Dinge zu importieren, sofern es im Stud.IP-System eine dazugehörige Klasse gibt, die von der Klasse SimpleORMap (der OR-Mapper in Stud.IP) erbt. Damit könnte man auch DoIt-Aufgaben oder Schwarze-Brett-Einträge importieren. Theoretisch. Will man das? Ich denke, es gibt nichts, was man nicht irgendwann einmal doch importieren will. Und dann gibt es noch den Wert `Tabelle nicht importieren`. Dann werden nur die Daten erfasst, aber keine Objekte direkt in Stud.IP angelegt. Dann muss man sich diese Tabelle als Hilfstabelle vorstellen. Nicht immer liegen Daten aus externen Systemen genau so vor, wie die Stud.IP-Datenbank sie sich wünscht. Dann kann ein SQL-View (siehe unten) die Daten weiter verarbeiten oder eine andere Tabelle nutzt die Daten dieser Hilfstabelle, um bestimmte Dinge anzulegen.

**Import über**: Wo kommen die Daten her? Die Daten kommen oft aus einer CSV-Datei, die man per Hand aus Excel heraus exportiert und im Fleximport hochlädt. Es gibt auch die Möglichkeit, diese CSV-Datei direkt aus Stud.IP zu beziehen zum Beispiel aus einer Veranstaltung heraus. Warum das? Nun, man möchte vielleicht, dass jemand externes die Daten einpflegt, der/die aber keinen Root-Zugriff auf Stud.IP bekommen soll. Dafür ist dieser Umweg über eine Stud.IP-Datei gut. Man kann auch eine CSV-Datei aus dem Internet oder Intranet ziehen lassen. Man muss dazu nur die Adresse eingeben. Dadurch lassen sich selbst Google-Doc-Spreadsheets, an denen mehrere Personen zusammen arbeiten, in Echtzeit in Stud.IP importieren. Lach nicht, sowas wurde schon gemacht. Im Bereich Import gibt es meiner Erfahrung nach nichts, was es nicht gibt. Man kann auch eine externe Datenbank abfragen. Wichtig ist dabei, dass der Stud.IP-Server Zugang zu der Datenbank hat. Möglich ist die Extraktion aus MySQL/MariaDB oder MSSQL-Datenbanken. Schließlich kann man sagen, dass ein "externes Tool" sich darum kümmert, dass die Rohdaten in die Tabelle in Stud.IP kommen. In dem Fall muss Fleximport nichts selbst machen, um die Rohdaten zu bekommen und geht einfach davon aus, dass sie da sind. Yeah! Die letzte Option ist "SQL-View". Damit kann man einen SQL-Select definieren, aus dem ein View in der Datenbank angelegt wird. Manchmal will man Rohdaten nämlich erst einmal gruppieren und verändern und umbenennen oder formalisieren, bevor man sie importiert. Da gibt einem ein SQL-View schon viele Möglichkeiten mit auf den Weg.

**Synchronisierung**: Man kann hier definieren, ob die importierten Objekte ausschließlich importiert und aktualisiert werden oder ob es zudem auch eine Löschfunktion geben soll. Beispiel: Es wird in Excel eine Liste von Alumni gepflegt. Fällt einer weg, weil der Alumnus/die Alumna einfach nicht länger in der Datenbank geführt werden möchte, so streicht man die Person aus der Excel-Tabelle. Bei dem nächsten Import kann Fleximport dann feststellen, dass ein Datensatz fehlt und geht davon aus, dass dieser Datensatz gelöscht werden soll und tut das dann auch. Wer diese Löschfunktion haben möchte, muss nur das Häkchen hier ankreuzen. Dabei sollte gesagt werden, dass die Lösch- bzw. Synchronisationsfunktion gefährlich ist, wenn man CSV-Dateien importiert. Allzu leichtfertig könnte man auf den Gedanken kommen, dass man eben mal schnell nur diese drei Personen updaten will, bei denen sich etwas geändert hat. Blöd, wenn dann alle anderen 597 Personen, die zuvor importiert worden sind, plötzlich gelöscht wurden. Fleximport wird einen an der Stelle nicht warnen, falls man einen menschlichen Fehler macht!
Setzt man den Haken für die Synchronisation, so erscheint darunter die Textbox `Synchronisationsbedingung`. Mit dieser Box kann man eine SQL-Bedingung an geben. Und nur Objekte, die dieser Bedingung genügen, werden auch tatsächlich gelöscht. So kann man die Synchronisationsfunktion nutzen für alle Termine, die in der Zukunft liegen (und man setzt die Bedingung, dass die Synchronisation nur für Termine gilt, die in der Zukunft liegen), oder für alle Veranstaltungen des aktuellen Semesters (mit einem Subselect auf die aktuelle semester_id).

## 3) Mapping der Tabellen

Sind die Rohdaten in Stud.IP drin (zum Beispiel nach dem ersten Upload der CSV-Datei), so kann man mit dem Mapping der Daten auf die Zieltabelle beginnen. Oben rechts der Datentabelle taucht das Symbol ![Kettenglied](https://develop.studip.de/studip/assets/images/icons/blue/group.svg) auf. Klickt man auf dieses Kettengliedicon, öffnet sich ein Dialogfenster, in dem man das Mapping durchführen kann. Tabellarisch sieht man jedes Feld der Zieltabelle und kann auf der rechten Seite der Tabelle einstellen, welchen Wert dieses Feld annehmen soll. Dabei kann man auswählen aus *nicht* (also es wird nichts gemappt, was meistens okay ist), einem festen Eintrag, den man darunter noch genauer angibt, einem Feld aus der Datentabelle und vielleicht noch Spezialmapping, sofern für dieses Feld welche verfügbar sind.

### Mapping eines festen Werts

Oft erscheinen bestimmte Dinge selbstverständlich. Alumni, die man importieren möchte, sollten zum Beispiel immer den Status "autor" haben und nicht "dozent". Das kann man mappen, indem man in der Rohdatentabelle eine Spalte einbaut, die "Status" heißt und in jeder Zeile "autor" stehen hat. Aber das ist nervig, weil man ja die CSV-Datei nicht unnötig groß werden lassen möchte. Stattdessen kann man im Mapping `[Fester Eintrag]` auswählen und den Wert "autor" definieren.

### Mapping eines Feldes mit besonderer Formatierung

Manche Felder (in der Regel sind das Spezialfelder, siehe unten) kann man ganz normal mit einem festen Wert oder einer Tabellenspalte mappen, muss aber noch das Format angeben. Das liegt daran, dass die wenigsten wissen, welche `institut_id` die Heimateinrichtung hat. Stattdessen will man wohl eher den Namen der Einrichtung eingeben und geht davon aus, dass es keine Namensdoppelungen gibt. In dem Fall gibt man im Format an "Name der Einrichtung" statt "Institut_id". Aber beides würde gehen.

### Mapping mit Templates

Manchmal muss man Beschreibungsfelder mappen, in denen mehrere Angaben stehen. So zum Beispiel für eine Veranstaltung "Findet am xxx zum ersten Mal statt und am yyy zum letzten Mal".

Um das zu bauen, kann man in Fleximport im Reiter Konfiguration eine Konfigurationsvariable anlegen, die später als Template fungiert. Innerhalb des Templates kann man Platzhalter einsetzen, die etwa so aussehen: ``{{Spalte aus Tabelle}}`` Also immer zwei geschweifte Klammern, den Namen eines Feldes aus der Datentabelle oder der Zieltabelle und dann wieder zwei geschweifte Klammern.

Zurück zum Mapping der Zieltabellenfelder: Dort erscheint nun "Konfiguration: Templatename" als mögliches Mapping in dem Auswahlfeld. Die Werte der Rohdatentabelle werden in das Template eingesetzt und der erzeugte Endtext dann in das Feld der Zieltabelle eingetragen.

### Mapping mit Key-Value-Mappern

In manchen Fällen hat man Werte in den Rohdaten, die auf andere Werte in Stud.IP zugeordnet werden sollen. Das Geschlecht bei Personendaten ist häufig so ein Fall. In den Rohdaten steht dann meist "w" für weiblich, "m" für männlich und "d" für diverses Geschlecht. Stud.IP braucht in dem Feld `geschlecht` aber eine Zahl (1, 2 oder 3 oder 0 für nicht-zugeordnet). Dazu kann man unter den Konfigurationen ein Template anlegen, das so aussieht:

    m=1
    w=2
    d=3

Beim Mapping wählt man dann den Key-Value-Mapper mit dem entsprechenden Namen der Konfiguration auswählen. Fleximport schaut dann beim Mappen in diese Konfiguration, sucht dann nach "W" und findet den Wert "2", der dann in das Feld von Stud.IP eingetragen wird.

### Mapping durch "Von XYZ ermitteln"

Dieses ist ein Spezialmapping, das sehr *sehr* wichtig ist, um Objekte nicht nur anzulegen, sondern auch durch einen mehrmaligen Import updaten zu können. Dies betrifft in der Regel Felder wie `Seminar_id` oder `user_id`, also oft den Primärschlüsseln von Tabellen.

Beispiel Veranstaltungsimport: Wenn eine Veranstaltung des erste Mal angelegt wird, wird die `Seminar_id` neu erstellt. Das passiert magisch, ohne dass man etwas dazu tun muss. Danach ist die `Seminar_id` eine kryptische Zahlenbuchstabenfolge wie `9844ed33137d1aaed615fe650cd2921e`. Es ist superumständlich, wenn in der CSV-Datei ebendiese `Seminar_id` eingetragen werden soll, damit beim nächsten Import der Daten nicht das gleiche Seminar noch einmal eingetragen wird.

Stattdessen überlegt man sich einen besonderen Schlüssel, mit dem man die Veranstaltung identifizieren kann, auch wenn man die `Seminar_id` nicht kennt. Das könnte die Veranstaltungsnummer sein oder ein Datenfeldeintrag wie "lsf_id" - nur so als Beispiel. Jetzt muss man ein Spezialmapping "Von Veranstaltungsnummer ermitteln" für das Feld `Seminar_id` auswählen und definiert dann noch, welches Feld aus der Rohdatentabelle der Veranstaltungsnummer entspricht.

Hat man das gemacht, werden die Veranstaltungen bei einem erneuten Import immer geupdated anstatt neu angelegt zu werden. So kann man natürlich auch den Namen der Veranstaltung ändern, solange die Veranstaltungsnummer gleich bleibt. Und genau für diese Updateprozesse ist dieses Spezialmapping so enorm wichtig. Theoretisch kann man dieses Spezialmapping aber auch für andere Dinge setzen, wie wenn bei einem Terminimport die `Seminar_id`, die dort nicht der Primärschlüssel ist, gesetzt werden soll. Auch da könnte man einfach die Veranstaltungsnummer in die Rohdatentabelle packen und dann entsprechend mappen.

### Mapping von Datenfeldern

Datenfelder spielen eine wichtige Rolle in Stud.IP. Sie können ganz normal gemapped werden, als wären sie Felder der Zieltabelle. Zudem kann man in Spezialmappings Objekte anhand ihrer Einträge in einem Datenfeld wie der Matrikelnummer identifizieren.

### Mapping mit Fleximport-Fremdschlüsseln

Es gibt einige Objekte, die keine Möglichkeit mit sich bringen, einen Fremdschlüssel abzulegen. Termine sind so ein Fall oder Statusgruppen. Fleximport bringt aber eine eigene Datenstruktur mit, in der man Fremdschlüssel speichern kann. Diese Datenstruktur nennt sich `fleximport_foreign_key`. Jedes Objekt, das man mit Fleximport importieren kann, hat im Mapping den Wert `fleximport_foreign_key`, den man mit den Daten einer Tabelle befüttern kann.

Zudem kann man bei den anderen Feldern wie `user_id` die ID mappen über den *Fleximport-Fremdschlüssel*, und dahinter verbirgt sich der Wert von `fleximport_foreign_key`. Bei dem Mapping der Felder über den Fremdschlüssel kann man auch eine SORM-Klasse des Schlüssels angeben. Diese Klassen heißen *User* oder *CourseDate* und sind Unterklassen von `SimpleORMap`. Mit dieser Klasse sagt man nicht nur, welchen Fremdschlüssel wir haben (der Schlüssel selbst kommt aus den Daten), sondern auch für welche Tabelle in Stud.IP der Fremdschlüssel gilt.

Beispiel: Ein CourseExDate Objekt wird importiert. Wenn man das Feld `resource_id` mappen will, macht man das mit einer ID eines Raumes. Das Fremdsystem kennt aber nicht die IDs in Stud.IP und schreibt dort den Wert 3 vor, was die ID des Raumes im Fremdsystem ist. 3 ist aber keine eindeutige ID, sondern es gibt eine ID 3 in der Räume-Tabelle und eine ID 3 in der Veranstaltungs-Tabelle. Zu welcher Entität gehört nun also diese 3? Dazu schreibt man ins Mapping, dass es zur SORM-Klasse `Resource` gilt. Dann weiß Flexiport auch, welches Objekt sich tatsächlich hinter der ID 3 verbirgt und kann sich die ID holen.

Mit dieser Fremdschlüsseltabelle kann man praktisch alle Objekte importieren und updaten, auch wenn es in Stud.IP keine Datenfelder und kein anderes geeignetes Feld in der Datenbank gibt, das man zum Speichern der Fremdschlüssel nutzen kann.

### Spezialfelder mappen

Manche Felder sind nicht wirklich Felder der Zieltabelle. Ihre Feldnamen beginnen stets mit `fleximport_...`, damit man sie unterscheiden kann. Sie haben aber eine besondere Bedeutung. Zum Beispiel kann man damit die importierten Veranstaltungen gleich sperren. Wenn das so ist, wird Fleximport automatisch die Veranstaltung mit einem speziellen Anmeldeset, der gesperrten Anmeldung, verknüpfen. Da diese Verknüpfung kein einfacher Eintrag in der Tabelle `seminare` ist, sondern eine weitere Tabelle, wird das der Einfachheit halber über so ein Spezialmapping behandelt. Theoretisch könnte man auch einen zweiten Import nur für die Verknüpfungstabelle starten. Das wäre aber arg kompliziert für diesen häufigen Anwendungsfall. Die Spezialfelder machen die Importe daher sehr viel einfacher (*hüstel*).

Manche dieser Spezialfelder ermöglichen den Eintrag mehrerer werde wie `fleximport_dozenten` als die Dozenten einer Veranstaltung. Fleximport fragt Sie, welches Trennzeichen innerhalb der Tabellenzelle die einzelnen Identifizierer dieser mehreren Datensätze verwendet werden soll. Sinnvolle Zeichen dafür sind das Semikolon `;`, das Komma `,`, die Pipe `|` oder das Leerzeichen.

## 4) Konfigurationen

Es gibt immer den Reiter "Konfiguration", mit dem man frei Variablen definieren kann, die im Fleximport vielleicht Verwendung finden. Welche Variablen es gibt, wird hier dokumentiert.

Variablenname | Bedeutung
--------------|-----------
`FLEXIMPORT_NAME` | Der Name von Fleximport, der angezeigt wird.
`DISPLAY_AT_HEADER` | Soll Fleximport in der Kopfzeile von Stud.IP auftauchen? 1 für ja und 0 (oder keine Angabe) für nein. Man kann auch eine URL eines Bildes angeben, um das Icon in der Kopfzeile zu definieren. Sieht vielleicht manchmal besser aus. Sollte ein SVG-Icon sein.
`FLEXIMPORT_DISPLAY_LINES` | Wieviele Zeilen einer Tabelle sollen beim Laden der Seite angezeigt werden? Meistens will man nur 20 Zeilen exemplarisch sehen. Den Rest kann man bei Bedarf nachladen.
`MAXIMUM_EXECUTION_TIME` | Hier kann man die Anzahl der Sekunden angeben, die der Import maximal dauern darf. Wenn nichts angegeben ist, ist die Dauer meist auf 5 Minuten beschränkt. Dies gilt nur für den händischen Aufruf über die Stud.IP-Oberfläche. Cronjobs sind von dieser Dauer ausgenommen. Deren Dauer wird über den Parameter `CRONJOBS_ESCALATION` der Stud.IP-Konfiguration bestimmt.
`REPORT_CRONJOB_ERRORS` | Falls Fleximport per Cronjob ausgeführt wird, fallen Fehler eventuell nicht so leicht auf, weil es ja niemanden mehr gibt, der/die aktiv die Datensätze und Fehlermeldungen durchgeht. In dem Fall kann man mit dieser Konfiguration definieren, welche Personen einen Fehlerbericht per Email bekommen sollen. Der Wert kann eine oder mehrere mit Komma oder Semikolon oder einfach nur einem Space oder Enter getrennte Emailadressen sein. Alle diese Personen bekommen eine Email mit allen Fehlern zugeschickt, falls es denn Fehler gegeben hat. Falls keine Fehler aufgetreten sind, wird keine Email versendet.
Willkommensnachricht | Eine Willkommensnachricht für neue Nutzer. Normalerweise werden neu importierte Nutzer in Stud.IP eine Nachricht bekommen, in der ihr Nutzername und Passwort stehen und ein Link, um sich das erste Mal anzumelden. Diese Nachricht kann aber auch verändert werden. Das passiert über das Mapping des Feldes `fleximport_welcome_message` in der Nutzerimporttabelle. Dort kann man sagen, dass entweder die Standardnachricht verwendet werden soll oder gar keine Nachricht oder eben eine Textnachricht aus der Fleximport-Konfiguration. Dazu muss erst einmal eine Konfigurationsvariable angelegt werden. Wie die heißt, ist dabei völlig egal, aber vermutlich wäre `fleximport_welcome_message` ein sinniger Name. Danach kann man im Mapping der Tabelle bzw. des Feldes `fleximport_welcome_message` die Konfiguration auswählen. Der Text, der in der Konfiguration hinterlegt wird, kann überdies Template-Variablen enthalten. So wäre `{{password}}` das Passwort, das der Nutzer sieht oder `{{email}}` seine Emailadresse `{{vorname}}` oder `{{nachname}}` können benutzt werden, um ihn direkt anzusprechen. Oder man schreibt zwischen den beiden geschweiften Klammern ein Feld aus der Datentabelle (der CSV-Quelle), um ganz andere Dinge in die Willkommensnachricht zu schreiben.
Templates zum Mappen | Wie oben bei *Mapping mit Templates* beschrieben, kann man Konfigurationsvariablen anlegen, um deren Inhalt als Mappingwert für die Zieltabelle festzulegen. So kann zum Beispiel die Beschreibung einer Veranstaltung anstatt eines feste Wertes den Wert einer Konfigurationsvariablen bekommen. Der Clue dabei ist, dass die Konfigurationsvariable wie ein Template funktioniert. Das heißt, man kann wie bei der Willkommensnachricht Felder der Datentabelle oder auch der Zieltabelle referenzieren, indem man ihn in zwei geschweiften Klammern einschließt. Zum Beispiel könnte im Template stehen: "Diese Veranstaltung gibt {{ects}} Punkte und ist im Modul {{Modul1}} verfügbar." So würden bei jeder importierten Veranstaltung die ECTS-Punkte und das korrekte Modul eingefügt werden, sodass die Beschreibungstexte auf jede Veranstaltung individuell angepasst sind. Eine weitere Besonderheit der Templates ist, dass man die PHP-Funktion [MD5](http://php.net/manual/de/function.md5.php) und [URLENCODE](http://php.net/manual/de/function.urlencode.php) verwenden kann. Dazu muss man im Template einfach `MD5({{veranstaltungsname}}_suffix)` eingeben und es wird ein MD5-Hash des Veranstaltungsnamens und des Suffixes gebildet und ebenda im Template eingefügt. Man kann diese Funktionen nicht verschachteln.
`USERDOMAIN_WELCOME_` + DOMAIN_ID | Wenn durch einen Userimport ein Nutzer in eine neue Domäne eingetragen wird, kann man diesem Nutzer eine Willkommensnachricht schreiben. Dazu muss eine Konfigurationsvariable angelegt werden, die exakt den Namen hat `USERDOMAIN_WELCOME_ALUMNI` für die beispielhafte Domäne mit der ID "ALUMNI". Also hinter dem `USERDOMAIN_WELCOME_` kommt immer die ID der Domäne. Auch hier kann man Textbausteine einsetzen mit geschweiften Klammern.

## 7) Profitipp: SQL-Views

Viel zu oft liegen die Rohdaten in ungünstigen Formaten vor. Klar, irgendwie kann man alles mappen, aber selbst die vielfältigen Mappingmöglichkeiten mit dynamischem Mapping *aus anderen Werten ermitteln*, mit Spezialmappings oder mit Mappings über Templates reicht viel zu oft immer noch nicht aus. In vielen Fällen hilft einem ein SQL-View weiter.

Im Fleximport haben Sie die Möglichkeit, selbst SQL-Views anzulegen und deren "Inhalt" anschließend zu importieren. Das lässt sich am besten über ein Beispiel erklären.

Angenommen, Sie haben eine Rohdatentabelle für den Import von Veranstaltungen. Damit lassen sich auch prima Veranstaltungen neu anlegen. Aber wenn es zum Update kommt, passen die Rohdaten nicht, um die Seminar_id der bereits importierten Veranstaltungen zu berechnen. Das liegt ganz konkret daran, dass als eindeutiger Schlüssel bei den Rohdaten zwei Werte fungieren müssen, zum Beispiel `v_nr`, was der Veranstaltungsnummer entspricht, und `fach_nr`, was in einem Datenfeld gespeichert wird und sowas wie eine Modulbezeichnung sein könnte. Es ist bisher unmöglich, diese beiden Werte auf eine Seminar_id zu mappen. Unten stellen wir noch Plugin in Plugins vor, womit es gehen würde. Aber dazu müsste man programmieren. In diesem Fall kommen wir ohne Programmierung aus und können alles über die Oberfläche von Stud.IP erledigen, wenn wir SQL-Views anlegen. Klicken Sie in der Sidebar auf "Neue Tabelle anlegen" und wählen Sie als Datenquelle "SQL-View" aus. Darunter müssen Sie noch ein SELECT-Statement angeben, das wie folgt aussieht:

    SELECT fleximport_kurse_rohdaten.*, (
            SELECT seminare.Seminar_id
            FROM seminare
                INNER JOIN datafields_entries AS de ON (de.range_id = seminare.Seminar_id)
                INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id)
            WHERE seminare.VeranstaltungsNummer = fleximport_kurse_rohdaten.v_nr
                AND d.name = 'fach_nr'
                AND d.object_type = 'sem'
                AND de.content = fleximport_kurse_rohdaten.fach_nr
            LIMIT 1
        ) AS Seminar_id
    FROM fleximport_kurse_rohdaten

Mit dieser View bekommt man die Rohdatentabelle plus eine weitere Spalte mit der Seminar_id (oder Null, wenn keine Seminar_id gefunden werden kann). Damit muss man die Spalte Seminar_id nicht mehr besonders mappen, sondern einfach aus dem View übernehmen. Das View hat in dem Fall das Mapping übernommen.

Der Trick ist am Ende nur noch, dass die Rohdatentabelle gar nicht importiert wird, sondern nur noch das View (das natürlich aus den Rohdaten berechnet wird).

## 6) Plugins im Plugin

Gelegentlich reichen die Möglichkeiten des Fleximportplugins immer noch nicht aus. Glauben Sie mir: Importe sind tückisch und jeder Import hat seine eigenen Fallstricke, die kein anderer Import vorher hatte. Für diese hartnäckigen Fälle gibt es die Möglichkeit, Plugins für das Fleximportplugin zu programmieren. Plugins im Plugin sozusagen.

Diese Plugins liegen alle im plugins-Ordner und sind Klassen, die von `FleximportPluginFleximportPlugin` erben. Sie müssen zudem exakt den Klassennamen haben, die auch die Tabelle trägt, die von dem Plugin betroffen sein soll.

## 7) Prozesse und Cronjobs

Schon durch die Installation von Fleximport wird ein Stud.IP-Cronjob angelegt, der einen oder mehrere Prozesse automatisch ausführen kann. Beim Bearbeitungsdialog des Prozesses kann man anklicken, dass der Prozess durch einen Cronjob ausgelöst werden soll. Das sollte in den meisten Fällen schon völlig genügen. Was ist aber, wenn man mehrere Prozesse erstellt hat, die zu unterschiedlicher Zeit laufen sollen? Zum Beispiel könnte ein Prozess eine Stunde dauern und am besten in der Nacht laufen und ein anderer Prozess läuft in 10 Sekunden durch und sollte mehrmals pro Stunde abgehandelt werden.

Für diesen Fall kann man seine Prozesse sogenannten *Chargen* zuordnen. Direkt beim Klick auf die Checkbox "Durch Cronjob starten" erscheint durch Geisterhand ein Auswahlfeld, mit dem man die Charge definieren kann. Das ist schon richtig, aber dazu kommen wir gleich erst. Zuerst muss man dazu unter Admin -> System -> Cronjobs in der Sidebar einen *neuen* Cronjob erstellen (ein in Stud.IP wenig genutztes Feature, das aber gut funktioniert). Dabei kann man einen eigenen Namen eingeben, wählt `FleximportJob` als Typ aus und kann den Zeitraum nach gusto auswählen. Als Parameter gibt man dann noch einen beliebigen Bezeichner an wie "NightlySync". Danach geht man zum Fleximport zurück und bearbeitet die Prozesse. Beim Bearbeiten-Dialog wählt man in dem oben erwähnten Auswahlfeld die Charge "NightlySync" aus und speichert.

Die Spezialcharge "cli" ist dabei eine Besonderheit. Sie gilt nur für das Skript import.cli.php, das im Ordner des Fleximport-Plugins liegt und ist losgelöst von den typischen Stud.IP-Cronjobs. Dieses Skript ist dazu da, dass ein dicker Importprozess vielleicht seeehr lange dauert und aber dennoch nicht die normalen Stud.IP-Cronjobs unterbrechen soll. Dann kann man den einfach als System-Cronjob auf dem Stud.IP-Server einstellen. Er führt dann aber immer die Charge "cli" aus und keine anderen Prozesse.

Auf diese Weise kann man beliebig viele Chargen von Prozessen definieren, die alle zu ganz unterschiedlichen Zeitpunkten regelmäßig oder einmalig automatisch ausgeführt werden.
