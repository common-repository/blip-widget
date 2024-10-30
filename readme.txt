=== Plugin Name ===
Contributors: greenek
Tags: blip, social, widget
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 0.5.1

Widget wyświetlający ostatnie wpisy użytkownika z serwisu [Blip.pl](http://blip.pl "Blip.pl"). Świetnie nadaje sie jako zamiennik minibloga.

== Description ==

Plugin działa w oparciu o kanały Atom, a nie API, dlatego nie wymaga zainstalowanego na serwerze cURL'a (w przeciwieństwie do widgetu [WP-Blip!](http://wordpress.org/extend/plugins/wp-blip/ "WP-Blip!") autorstwa ^MySZy).

== Installation ==

1. Wgraj `blip-widget.php` do katalogu `/wp-content/plugins/`
1. Aktywuj plugin w zakładce 'Wtyczki' > 'Zainstalowane'
1. Dodaj widget na panel w zakładce 'Wygląd' > 'Widgety'
1. Skonfiguruj widget - podaj swój login z serwisu blip.pl, określ ile i w jakiej formie mają być wyświetlane statusy

= Formatowanie statusów =

Od wersji 0.3 możliwa jest konfiguracja i formatowanie wyświetlanych statusów. Jeśli używasz dynamicznego sidebaru możesz tego dokonać w ustawieniach widgetu (Format wyświetlania statusu).

Dostępne znaczniki:

* *{status}* - status.
* *{date[format]}* - formatowanie daty i czasu jest opisane w [tym dokumencie](http://codex.wordpress.org/Formatting_Date_and_Time "Formatting Date and Time").
* *{link}* - bezpośredni link do statusu na blip.pl [od wersji 0.4].

== Frequently Asked Questions ==

= Pod przeciągnięciu widgetu na panel pokazuje się informacja, że allow_url_fopen jest wyłączony na moim serwerze. Co mogę zrobić? =

allow_url_fopen jest domyślnie włączony w konfiguracji PHP. Może być jednak tak, że administrator tę opcję wyłączył. Masz teraz dwie możliwości:

* skontaktuj się z administratorem serwera i poproś o włączenie allow_url_fopen
* jeśli Twój serwer posiada włączony cURL możesz skorzystać z pluginu [WP-Blip!](http://wordpress.org/extend/plugins/wp-blip/ "WP-Blip!")

== Changelog ==

= 0.5.1 =
* W razie wyłączonego allow_url_fopen skrypt korzysta z cURL (dzięki ^paulpela).

= 0.5 =
* Od tej wersji plugin pobiera statusy za pomocą metody GET, nie poprzez kanał Atom.
* Poprawki i optymalizacja kodu.
* Linkowanie do kokpitów użytkowników wspominanych w statusie.

= 0.4 =
* Pod statusami pojawił się link prowadzący do bliploga użytkownika.
* Dodany nowy znacznik {link}, który wyświetla link do statusu na blip.pl.
* Sprawdzanie, czy allow_url_fopen jest włączone na serwerze.
* Pobrany feed jest zapisywany w bazie na wypadek przeciążenia serwera blip.pl.
* Uzupełnione komentarze w kodzie.
* Zalążek FAQ.

= 0.3 =
* Możliwość formatowania wyświetlanych statusów.
* Poprawki związane z wyświetlaniem daty.

= 0.2.1 =
* Poprawione wyświetlanie cache'u.

= 0.2 =
* Pierwsze publiczne wydanie blip-widget.
* Zastąpienie file_get_contents funkcją fopen, allow_url_include jest teraz domyślnie wyłączane w PHP.
* Inne drobne zmiany.

= 0.1 =
* Pierwsze wydanie blip-widget.
