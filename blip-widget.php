<?php
/*
Plugin Name: Blip Widget
Plugin URI: http://blog.greenek.com/2010/01/04/blip-widget/
Description: Widget wyswietlajacy ostatnie wpisy uzytkownika z serwisu Blip.pl. Swietnie nadaje sie jako zamiennik minibloga.
Version: 0.5.1
Author: Greenek
Author URI: http://blog.greenek.com/
*/

// Tablica polskich znaków
$utf8_chars = array(
    "\xc4\x85", "\xc4\x99", "\xc5\x82", "\xc5\x84",
    "\xc3\xb3", "\xc5\x9b", "\xc5\xba", "\xc5\xbc",
    "\xc4\x84", "\xc4\x86", "\xc4\x98", "\xc5\x81",
    "\xc5\x83", "\xc3\x93", "\xc5\x9a", "\xc5\xb9",
    "\xc5\xbb", "\xc4\x87"
);

$utf8_chars = implode('', $utf8_chars);

// Domyślne ustawienia
$blip_widget_defaults = array(
    'username'  => 'blipinfo',
    'limit'     => 5,
    'cache_lifetime'    => 0,
    'type'      => 'html',
    'status_format' => '<li><em class="blip-date"><a href="{link}">{date[H:i, d.m]}</a></em> <span class="blip-entry">{status}</span></li>',
);

function get_recent_blips($options = array()) {
    global $blip_widget_defaults, $utf8_chars;

    // Sprawdź, czy allow_url_fopen lub cURL jest włączone.
	$url_fopen = is_allow_url_fopen_enabled();
	$curl = is_curl_enabled();

	$protocol = 'fopen';

    if ( ! $url_fopen && ! $curl)
    {
        echo '<p>Przepraszamy, ale ani <em>allow_url_fopen</em>, ani <em>cURL</em> nie są wyłączone na tym serwerze, w związku z czym Blip-Widget nie może działać. Skontaktuj się z administratorem i poproś go o uruchomienie jednej z tych opcji.</p>';

        return false;
    }
	else if ( ! $url_fopen && $curl)
	{
		$protocol = "curl";
	}

    // Przypisz opcje
    $username = (isset($options['username']))
        ? $options['username']
        : $blip_widget_defaults['username'];

    $limit = (isset($options['limit']))
        ? $options['limit']
        : $blip_widget_defaults['limit'];

    $cache_lifetime = (isset($options['cache_lifetime']))
        ? $options['cache_lifetime']
        : $blip_widget_defaults['cache_lifetime'];

    $html = (isset($options['type']) AND $options['type'] == 'html')
        ? TRUE
        : FALSE;

    $status_format = (isset($options['status_format']) AND $options['status_format'] != '')
        ? $options['status_format']
        : $blip_widget_defaults['status_format'];

    $blips = '';

    // Jeśli cache jest włączony
    if ($cache_lifetime > 0)
    {
        // Sprawdź datę
        $last_cache = get_option('blip_widget_last_cache');
        $next_cache = $last_cache + ($cache_lifetime * 60);

        // Pobierz statusy z cache'u
        $blips = get_option('blip_widget_cache');
    }

    if ($blips == '' OR ($cache_lifetime > 0 AND date('U') > $next_cache))
    {
	    $blips = '';

        // Pobierz feed
        $statuses = blip_api_get('users/'.$username.'/statuses?limit='.$limit, $protocol);

        // Jeśli nie udało się pobrać feed'a...
        if ($statuses === null)
        {
            // ... pobierz go z cachu.
            $statuses = get_option('blip_widget_feed');
        }

        // Na wypadek przeciążenia blip.pl zapisz feed w bazie
        update_option('blip_widget_feed', $statuses);

        // Jeśli mimo wszystko feed jest pusty...
        if ( ! count($statuses))
        {
            echo '<p>Wygląda na to, że Blip.pl jest w tym momencie przeciążony.</p>';
            return false;
        }

        // Parsowanie
        foreach ($statuses as $status)
        {
            $content = $status['body'];

            if ($html)
            {
                // Zamień tagi, obrazy i video na linki
                $patterns = array(
                    '/(http|https|ftp|news)(:\/\/[[:alnum:]'.$utf8_chars.'@#%\&_=?\/\.\-\+]+)/',
                    '/>(http:\/\/blip.pl\/s\/[0-9]+)</',
                    '/#([[:alnum:]'.$utf8_chars.'\-\_]+)/',
                    '/\^([[:alnum:]\-\_]+)/',
                    '/>(.+)youtube.com\/watch(.+)</s'
                );

                $replacements = array(
                    '<a href="\\1\\2">\\1\\2</a>',
                    '>[blip]<',
                    '<a href="http://blip.pl/tags/\\1" title="Statusy oznaczone tagiem: \\0" class="blip-tag">\\0</a>',
                    '<a href="http://blip.pl/users/\\1/dashboard" title="Kokpit użytkownika \\0" class="blip-user">\\0</a>',
                    '" class="blip-video">[YouTube]<'
                );

                $content = preg_replace($patterns, $replacements, $content);
            }

            if (isset($status['pictures_path']) AND ! empty($status['pictures_path']))
            {
                $pictures = blip_api_get($status['pictures_path'], $protocol);
                $content .= ' <a href="'.$pictures['url'].'" class="blip-photo">[Foto]</a>';
            }

            // Data aktualizacji
            $updated = get_date_from_gmt(date('Y-m-d H:i:s', strtotime($status['created_at'])));

            // Znajdź ID statusu
            $id = $status['id'];

            // Zamień znaczniki
            $patterns = array(
                '/{status}/',
                '/{date\[(.*)\]}/e',
                '/{link}/'
            );

            $replacements = array(
                $content,
                "date_i18n('$1', strtotime('$updated'))",
                'http://blip.pl/s/'.$id
            );

            $blips .= preg_replace($patterns, $replacements, $status_format);
        }

        // Jeśli cache jest włączony...
        if ($cache_lifetime > 0)
        {
            // ... zapisz do bazy.
            update_option('blip_widget_last_cache', date('U'));
            update_option('blip_widget_cache', $blips);
        }

        // Link do bliplogu użytkownika
        $blips .= '<li><a href="http://'.$username.'.blip.pl/">Zobacz mój Bliplog &raquo;</a></li>';
    }

	//$blips .= "<p>$protocol</p>"; // debug

    echo $blips;
}

function blip_api_get($query, $protocol)
{
    $result = '';

	if ($protocol == 'fopen')
	{
		$handle = @fopen('http://api.blip.pl/'.ltrim($query, '/'), "r");

		if ($handle)
		{
			while ( ! feof($handle))
			{
				$result .= fgets($handle, 4096);
			}
			fclose($handle);
		}

	}
	else
	{
		$cu = curl_init();
		$curl_url = 'http://api.blip.pl/'.ltrim($query, '/');
		curl_setopt($cu, CURLOPT_URL, $curl_url);
		curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($cu);
		curl_close($cu);
	}

	$result = json_decode($result, true);

	return $result;
}

/**
 * Sprawdza, czy allow_url_fopen jest włączone w konfiguracji serwera.
 */
function is_allow_url_fopen_enabled()
{
    return (ini_get('allow_url_fopen') == 1) ? true : false;
}

/**
 * Sprawdza, czy cURL jest włączone w konfiguracji serwera.
 */
function is_curl_enabled()
{
	return (in_array('curl', get_loaded_extensions())) ? true : false;
}

class Blip_Widget extends WP_Widget {

    function Blip_Widget()
    {
        $widget_ops = array('classname' => 'widget_blip', 'description' => 'Ostatnie statusy z serwisu blip.pl');
        $control_ops = array();

        parent::WP_Widget(false, $name = 'Blip.pl', $widget_ops);
    }

    function widget($args, $instance)
    {
        // Sprawdź, czy allow_url_fopen lub cURL jest włączone.

		if ( ! is_allow_url_fopen_enabled() && ! is_curl_enabled())
			return false;

        extract($args);

        $title = esc_attr($instance['title']);

        $options = array(
            'username' => esc_attr($instance['username']),
            'limit' => esc_attr($instance['limit']),
            'cache_lifetime' => esc_attr($instance['cache_lifetime']),
            'type' => esc_attr($instance['type']),
            'status_format' => $instance['status_format'],
        );

        echo $before_widget;
        if ( !empty($title) ) { echo $before_title . $title . $after_title; }
            echo '<ul id="blip-widget">';
            get_recent_blips($options);
            echo '</ul>';
        echo $after_widget;
    }

    function update($new_instance, $old_instance)
    {
        update_option('blip_widget_cache', '');
        return $new_instance;
    }

    function form($instance)
    {
        global $blip_widget_defaults;

        $instance = wp_parse_args( (array) $instance, array(
            'title' => 'Blip.pl',
            'username' => '',
            'limit' => $blip_widget_defaults['limit'],
            'cache_lifetime' => $blip_widget_defaults['cache_lifetime'],
            'type' => $blip_widget_defaults['type'],
            'status_format' => ''
        ));

        // Sprawdź, czy allow_url_fopen lub cURL jest włączone.
        if ( ! is_allow_url_fopen_enabled() && ! is_curl_enabled())
        {
            echo '<p>Przepraszamy, ale ani <em>allow_url_fopen</em>, ani <em>cURL</em> nie są wyłączone na tym serwerze, w związku z czym Blip-Widget nie może działać. Skontaktuj się z administratorem i poproś go o włączenie jednej z tych opcji.</p>';

            return false;
        }

        $title = esc_attr($instance['title']);
        $username = esc_attr($instance['username']);
        $limit = esc_attr($instance['limit']);
        $cache_lifetime = esc_attr($instance['cache_lifetime']);
        $type = esc_attr($instance['type']);
        $status_format = esc_attr($instance['status_format']);

        ?>
            <p><label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:') ?> <input class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" type="text" value="<?php echo $title ?>" /></label></p>

            <p><label for="<?php echo $this->get_field_id('username') ?>">Nazwa użytkownika: <input class="widefat" id="<?php echo $this->get_field_id('username') ?>" name="<?php echo $this->get_field_name('username') ?>" type="text" value="<?php echo $username ?>" /></label></p>

            <p><label for="<?php echo $this->get_field_id('limit') ?>">Limit wpisów: <select class="widefat" id="<?php echo $this->get_field_id('limit') ?>" name="<?php echo $this->get_field_name('limit') ?>" >
            <?php for($i = 1; $i <= 20; $i++): ?>
                <?php $selected = ($i == $limit) ? ' selected="selected"' : '' ?>
                <option value="<?php echo $i ?>"<?php echo $selected ?>><?php echo $i ?></option>
            <?php endfor ?>
            </select></p>

            <p><label for="<?php echo $this->get_field_id('cache_lifetime') ?>">Długość przechowywania cachu w pamięci (w min.). 0 - brak cachu: <input class="widefat" id="<?php echo $this->get_field_id('cache_lifetime') ?>" name="<?php echo $this->get_field_name('cache_lifetime') ?>" type="text" value="<?php echo $cache_lifetime ?>" /></label></p>

            <p><label for="<?php echo $this->get_field_id('type') ?>">Wyświetlaj jako: <select class="widefat" id="<?php echo $this->get_field_id('type') ?>" name="<?php echo $this->get_field_name('type') ?>" >
                <option value="html" <?php if ($type == 'html') echo 'selected="selected"' ?>>HTML</option>
                <option value="plain" <?php if ($type == 'plain') echo 'selected="selected"' ?>>Tekst</option>
            </select></p>

            <p><label for="<?php echo $this->get_field_id('status_format') ?>">Format wyświetlania statusu <em>(opcjonalnie)</em>: <input class="widefat" id="<?php echo $this->get_field_id('status_format') ?>" name="<?php echo $this->get_field_name('status_format') ?>" type="text" value="<?php echo $status_format ?>" /></label>
            <small>Znaczniki: <em>{date[format]}</em>, <em>{status}</em>, domyślnie <code><?php echo htmlspecialchars($blip_widget_defaults['status_format']) ?></code>. <a href="http://wordpress.org/extend/plugins/blip-widget/installation/">Zobacz pełną pomoc</a>.</small></p>
        <?php
    }

}

add_action('widgets_init', create_function('', 'return register_widget("Blip_Widget");'));

?>
