<?php
/**
 * @version $Revision: 262 $
 */

/**
 * Gettext translation driver
 *
 * Relies on {@link http://ee.php.net/manual/en/ref.gettext.php gettext} php
 * extension. All translated strings are returned in UTF-8 encoding.
 * There is no way to check if there is translation for specified token since
 * gettext() returns unmodified token in case of not founded translation, but
 * sometimes translation and token are equal. Gettext translations are cached
 * in process memory, that's why after updating .mo files web server has to be
 * restarted in case php is running as web server module.
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 */
class Translator_gettext extends Translator
{
    /**
     * Path directory with translation data
     *
     * Used as second parameter for {@link http://www.php.net/manual/en/function.bindtextdomain.php
     * bindtextdomain()} function
     *
     * @var string
     * @access private
     */
    var $_directory;

    /*Constructor**************************************************************/

    /**
     * @param Locale $locale
     * @param string $domain
     * @param array $params
     * @return Translator_gettext
     */
    function Translator_gettext(&$locale, $domain, $params)
    {
        parent::Translator($locale, $domain);

        $this->_directory = $params['directory'];

        // save primary locale and set locale only for first translator instance
        if (is_null(Translator_gettext::_primary_lang())) {
            Translator_gettext::_primary_lang($locale->lang_code());
            setlocale(LC_MESSAGES, $locale->get_locale());
        }

        // save primary domain and execute some code only for first translator instance
        if (is_null(Translator_gettext::_primary_domain())) {
            Translator_gettext::_primary_domain($domain);
            $this->_primary_mode = true;
            $primary_domain = $domain;
            $primary_locale =& $locale;

            Translator_gettext::_binddomain($domain, $this->_directory);

            // textdomain is executed only once
            textdomain($domain);

        } else {
            $this->_primary_mode = false;
        }
    }

    /*Protected****************************************************************/

    /**
     * @param string $token
     * @param int|NULL $n
     * @param string $domain
     * @param string $lang
     * @return string
     * @access protected
     */
    function _raw_translate($token, $n, $domain, $lang)
    {
        // this driver does not care about locale saved in parent
        // but only on language saved in static method _primary_lang()
        if ($lang != Translator_gettext::_primary_lang()) {
            // change locale for a while
            $locale = new Locale($lang);

            if (setlocale(LC_MESSAGES, 0) != $locale->get_locale()) {
                $old_locale = setlocale(LC_MESSAGES, 0);
                setlocale(LC_MESSAGES, $locale->get_locale());
            }
        }

        // this driver does not case about domain saved in parent
        // but only depends on domain set by _primary_domain();
        if ($domain == Translator_gettext::_primary_domain()) {
            Translator_gettext::_binddomain($domain, $this->_directory);

            if (is_null($n)) {
                $translation = gettext($token);
            } else {
                $translation = ngettext($token, $token, $n);
            }

        } else {
            Translator_gettext::_binddomain($domain, $this->_directory);

            if (is_null($n)) {
                $translation = dgettext($domain, $token);
            } else {
                $translation = dngettext($domain, $token, $token, $n);
            }
        }

        // restore locale if needed
        if (isset($old_locale)) setlocale(LC_MESSAGES, $old_locale);

        return $translation;
    }

    /**
     * Setter/Getter for domain
     *
     * When executed first time with not null parameter it will statically save domain. Next time
     * it will return saved domain.
     *
     * @param string|NULL $domain
     * @return string|NULL
     * @access private
     * @static
     * @staticvar string $saved_domain
     */
    function _primary_domain($domain = null)
    {
        static $saved_domain = null;

        if (!is_null($domain) && is_null($saved_domain)) {
            $saved_domain = $domain;
        }

        return $saved_domain;
    }

    /**
     * Setter/Getter for locale
     *
     * When executed first time with not null parameter it will statically save locale. Next time
     * it will return saved locale.
     *
     * @param string|NULL $lang
     * @return Locale|NULL
     * @access private
     * @static
     * @staticvar string $saved_lang
     */
    function _primary_lang($lang = null)
    {
        static $saved_lang = null;

        if (!is_null($lang) && is_null($saved_lang)) {
            $saved_lang = $lang;
        }

        return $saved_lang;
    }

    /**
     * Binds domain to directory
     *
     * Method remembers which bindings was performed and do not run bindtextdomain second time
     * when it's not needed
     *
     * @param string $domain
     * @param string $directory
     * @static
     * @staticvar array $bindmap
     */
    function _binddomain($domain, $directory)
    {
        static $bindmap = array();

        if (!isset($bindmap[$domain]) || $bindmap[$domain] != $directory) {
            $bindmap[$domain] = $directory;
            bindtextdomain($domain, $this->_directory);
            if (function_exists('bind_textdomain_codeset')) bind_textdomain_codeset($domain, 'UTF-8');
        }
    }
}
