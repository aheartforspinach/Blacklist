# Blacklist
Das Plugin erstellt am 01. eines jeden Monats eine Liste von allen Accounts, die im letzten Monat nicht gepostet haben. Zudem werden abwesende Charaktere herausgefiltert wie auch welche, die auf Eis sind, falls man das möchte. Admins und Mods haben die Möglichkeit User, die auf der Liste stehen, ohne Post zu streichen.


## Funktionen
* Charaktere, die seit einem Monat nicht gepostet haben und nicht abwesend sind, werden aufgelistet
* abwesende Charaktere werden gesondert gelistet
* Charaktere, die auf Eis liegen, werden gesondert gelistet (falls aktiviert)
* Bewerber können von der Liste ausgeschlossen werden
* es ist einstellbar, dass man nur seine eigenen Charaktere auf der Liste sieht
* nach einem gewissen Datum zählt der Post nicht mehr
* Hinweisbanner erscheint (wegklickbar)


## Voraussetzungen
* [Enhanced Account Switcher](http://doylecc.altervista.org/bb/downloads.php?dlid=26&cat=2) muss installiert sein 
* FontAwesome muss eingebunden sein, andernfalls muss man die Icons in den PHP-Datein ersetzen


## Template-Änderungen
__Neue globale Templates:__
* blacklist
* blacklistHeader
* blacklistHeaderChara
* blacklistIce
* blacklistUser
* blacklistUserGestrichen

__Veränderte Templates:__
* header (wird um die Variable {$blacklist_whitelist} erweitert)


## Auf Eis Profilfeld
Solltet ihr von der "Auf Eis"-Funktion Gebrauch machen wollen, müsst ihr händisch im Admin-CP ein neues Profilfeld anlegen, welches über Radiobuttons und den auswählbaren Funktionen "Ja" und "Nein" verfügt


## Vorschaubilder
__Einstellungen des Blacklist-Plugin__
![Blacklist Einstellungen](http://aheartforspinach.de/upload/plugins/BlacklistEinstellungen.png)

__Blacklistseite ohne "Auf Eis"__
![Blacklistseite ohne "Auf Eis"](http://aheartforspinach.de/upload/plugins/Blacklist.png)

__Blacklistseite mit "Auf Eis"__
![Blacklistseite mit "Auf Eis"](http://aheartforspinach.de/upload/plugins/BlacklistIce.png)

__Blacklistbanner__
![Blacklistbanner](http://aheartforspinach.de/upload/plugins/BlacklistBanner.png)
