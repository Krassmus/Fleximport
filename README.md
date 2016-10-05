# Fleximport

Ein Plugin für Stud.IP, um alle möglichen Dinge zu importieren wie Veranstaltungen, Nutzer, Termine, Einrichtungen und so weiter.

Die Idee des Plugins ist einfach: Man importiert erst einmal Daten wie aus einer CSV-Datei oder einer externen Quelle wie einem Google-Doc-Spreadsheet in die Stud.IP-Datenbank und anschließend werden die Daten Zeile für Zeile importiert. Da solche Rohdaten meist aus anderen Systemen kommen und ganz eigene Bezeichner haben, müssen die Daten entsprechend auf die Zieltabelle gemapped werden. Das macht man im Plugin über die Oberfläche. Man kann für jedes Attribut der Stud.IP-Tabelle (zum Beispiel auth_user_md5 für einen Nutzer) festlegen, welcher Wert aus der Datentabelle verwendet werden soll. Dadurch ist es unerheblich, ob in der Datentabelle die Emailadresse in der Spalte "email" oder "E-Mail" oder "Email-Adresse" steht. Wichtig ist nur, dass die Daten gemapped werden und dann kommen die richtigen Werte nach Stud.IP.

## Prozesse

Da Fleximport ein sehr mächtiges Importtool ist, möchte man vielleicht verschiedene Dinge gleichzeitig jeden Tag über einen Cronjob importieren, andere Dinge aber nur einmal pro Semester per Knopfdruck. Dazu gibt es in Fleximport Prozesse. Jeder Prozess ist im Grunde ein Importtool, das man frei konfigurieren kann. So kann ein Import aus einem Fremdsystem über drei Tabellen (Veranstaltungen, Nutzer und Teilnehmerdaten) ein einzelner Prozess mit drei Tabellen sein. Aber vielleicht will man die Freiheiten haben, Fleximport auch hin und wieder mit einer CSV-Datei zu befüttern, wodurch externe Accounts für eine spezielle Nutzerdomäne (zum Beispiel Alumni) angelegt werden. Das wäre dann ein zweiter Prozess.

Jeder dieser Prozesse bekommt einen eigenen Reiter, der frei benannt werden kann. Man kann also jedem Prozess einen Namen geben (den Namen des Reiters), einen Beschreibungstext, der erklärt, was mit dem Prozess gemacht werden soll. Und man kann noch definieren, ob der Prozess über den Stud.IP-Cronjob angestoßen werden soll oder nicht. Damit ist noch nicht geklärt, wann dieser Cronjob läuft. Diesen Zeitpunkt muss man in der Cronjobverwaltung festlegen, was auch über die Nutzeroberfläche von Stud.IP geht.

Jedem Prozess kann man mehrere Tabellen zuordnen, die in diesem Prozess nach und nach importiert werden.

Sowohl Prozesse als auch Tabellen innerhalb der Prozesse werden überdies in alphabetischer Reihenfolger abgearbeitet. Will man also die Reihenfolge verändern, muss man entweder den Prozess oder die Tabelle umbennen, was in der Regel kein Problem sein sollte.

## Importtabellen

Einem Prozess kann man sodann die Tabellen zuordnen. Man fügt über die Sidebar-Aktion "Tabelle hinzufügen" eine neue Tabelle hinzu. Folgende Angaben sind dabei wichtig:

`Name der Tabelle`: Das ist der Name, den die Tabelle in der Stud.IP-Datenbank einnimmt. Aufgepasst! Niemals sollte die Tabelle einen Namen haben wie "auth_user_md5" oder "Institute", weil das Tabellen sind, die in der Stud.IP-Datenbank schon auftauchen und dort eine wichtige Rolle spielen! Daher ist der Präfix "fleximport_" schon vorausgefüllt. Damit ist gewährleistet, dass Sie keine Namenskollisionen bekommen. Vermeiden Sie auch die Namen "fleximport_tables", "fleximport_configs", "fleximport_mapped_items" und "fleximport_processes", weil das Tabellen sind, die das Fleximport-Plugin selbst schon braucht. Falls ein Plugin (das ist kein Stud.IP-Plugin, sondern eine spezielle Klasse, die man als Plugin im Plugin bezeichnen könnte, siehe unten) Ihnen beim Fleximport helfen soll, wählen Sie auf jeden Fall den Namen der Pluginklasse.

`Zweck der Tabelle`: Hier wählen Sie aus, ob mit der Tabelle Nutzer oder Veranstaltungen oder wasauchimmer import werden sollen. Zwei besondere Werte sind `SORM-Objekt` und `Tabelle nicht importieren`. Mit SORM-Objekt ist es im Grunde möglich, völlig beliebige Dinge zu importieren, sofern es im Stud.IP-System eine dazugehörige Klasse gibt, die von der Klasse SimpleORMap (der OR-Mapper in Stud.IP) erbt. Damit könnte man auch DoIt-Aufgaben oder Schwarze-Brett-Einträge importieren. Theoretisch. Will man das? Ich denke, es gibt nichts, was man nicht irgendwann einmal doch importieren will. Und dann gibt es noch den Wert `Tabelle nicht importieren`. Dann werden nur die Daten erfasst, aber keine Objekte direkt in Stud.IP angelegt. Dann muss man sich diese Tabelle als Hilfstabelle vorstellen. Nicht immer liegen Daten aus externen Systemen genau so vor, wie die Stud.IP-Datenbank sie sich wünscht. Dann kann ein SQL-View (siehe unten) die Daten weiter verarbeiten oder eine andere Tabelle nutzt die Daten dieser Hilfstabelle, um bestimmte Dinge anzulegen.

`Import über`: Wo kommen die Daten her? Die Daten kommen oft aus einer CSV-Datei, die man per Hand aus Excel heraus exportiert und im Fleximport hochlädt. Es gibt auch die Möglichkeit, diese CSV-Datei direkt aus Stud.IP zu beziehen zum Beispiel aus einer Veranstaltung heraus. Warum das? Nun, man möchte vielleicht, dass jemand externes die Daten einpflegt, der/die aber keinen Root-Zugriff auf Stud.IP bekommen soll. Dafür ist dieser Umweg über eine Stud.IP-Datei gut. Man kann auch eine CSV-Datei aus dem Internet oder Intranet ziehen lassen. Man muss dazu nur die Adresse eingeben. Dadurch lassen sich selbst Google-Doc-Spreadsheets, an denen mehrere Personen zusammen arbeiten, in Echtzeit in Stud.IP importieren. Lach nicht, sowas wurde schon gemacht. Im Bereich Import gibt es meiner Erfahrung nach nichts, was es nicht gibt. Man kann auch eine externe Datenbank abfragen. Wichtig ist dabei, dass der Stud.IP-Server Zugang zu der Datenbank hat. Möglich ist die Extraktion aus MySQL/MariaDB oder MSSQL-Datenbanken. Schließlich kann man sagen, dass ein "externes Tool" sich darum kümmert, dass die Rohdaten in die Tabelle in Stud.IP kommen. In dem Fall muss Fleximport nichts selbst machen, um die Rohdaten zu bekommen und geht einfach davon aus, dass sie da sind. Yeah! Die letzte Option ist "SQL-View". Damit kann man einen SQL-Select definieren, aus dem ein View in der Datenbank angelegt wird. Manchmal will man Rohdaten nämlich erst einmal gruppieren und verändern und umbenennen oder formalisieren, bevor man sie importiert. Da gibt einem ein SQL-View schon viele Möglichkeiten mit auf den Weg.

`Synchronisierung`: Man kann sagen, ob die importierten Objekte ausschließlich importiert werden oder ob es auch eine Löschfunktion geben soll. Beispiel: Es wird in Excel eine Liste von Alumni gepflegt. Fällt einer weg, weil der Alumnus/die Alumna einfach nicht länger in der Datenbank geführt werden möchte, so streicht man die Person aus der Excel-Tabelle. Bei dem nächsten Import kann Fleximport dann feststellen, dass ein Datensatz fehlt und geht davon aus, dass dieser Datensatz gelöscht werden soll und tut das dann auch. Wer diese Löschfunktion haben möchte, muss nur das Häkchen hier ankreuzen. Dabei sollte gesagt werden, dass die Lösch- bzw. Synchronisationsfunktion gefährlich ist, wenn man CSV-Dateien importiert. Allzu leichtfertig könnte man auf den Gedanken kommen, dass man eben mal schnell nur diese drei Personen updaten will, bei denen sich etwas geändert hat. Blöd, wenn dann alle anderen 597 Personen, die zuvor importiert worden sind, plötzlich gelöscht wurden. Fleximport wird einen an der Stelle nicht warnen, falls man einen menschlichen Fehler macht!


## Konfigurationen

Es gibt immer den Reiter "Konfiguration", mit dem man frei Variablen definieren kann, die im Fleximport vielleicht Verwendung finden. Welche Variablen es gibt, wird hier dokumentiert.

Variablenname | Bedeutung
--------------|-----------
`DISPLAY_AT_HEADER` | Soll Fleximport in der Kopfzeile von Stud.IP auftauchen? 1 für ja und 0 (oder keine Angabe) für nein. Man kann auch eine URL eines Bildes angeben, um das Icon in der Kopfzeile zu definieren. Sieht vielleicht manchmal besser aus. Sollte ein SVG-Icon sein.
Willkommensnachricht | Eine Willkommensnachricht für neue Nutzer. Normalerweise werden neu importierte Nutzer in Stud.IP eine Nachricht bekommen, in der ihr Nutzername und Passwort stehen und ein Link, um sich das erste Mal anzumelden. Diese Nachricht kann aber auch verändert werden. Das passiert über das Mapping des Feldes `fleximport_welcome_message` in der Nutzerimporttabelle. Dort kann man sagen, dass entweder die Standardnachricht verwendet werden soll oder gar keine Nachricht oder eben eine Textnachricht aus der Fleximport-Konfiguration. Dazu muss erst einmal eine Konfigurationsvariable angelegt werden. Wie die heißt, ist dabei völlig egal, aber vermutlich wäre `fleximport_welcome_message` ein sinniger Name. Danach kann man im Mapping der Tabelle bzw. des Feldes `fleximport_welcome_message` die Konfiguration auswählen. Der Text, der in der Konfiguration hinterlegt wird, kann überdies Template-Variablen enthalten. So wäre `{{password}}` das Passwort, das der Nutzer sieht oder `{{email}}` seine Emailadresse `{{vorname}}` oder `{{nachname}}` können benutzt werden, um ihn direkt anzusprechen. Oder man schreibt zwischen den beiden geschweiften Klammern ein Feld aus der Datentabelle (der CSV-Quelle), um ganz andere Dinge in die Willkommensnachricht zu schreiben.

