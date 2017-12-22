<?php
/**
 * @version $Revision: 257 $
 */

/**
 * Static translator helper methods
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @static
 */
class TranslatorHelpers
{
    /**
     * Convert gettext plural expression to php code usable in eval()
     *
     * Input expression should be in C format ('n==1 ? 0 : n==2 ? 1 : 2' or
     * 'n>1' or 'n != 1'). All references to 'n' are replaced with '$n'.
     * '$plural = ' is prepended to expression and trailing semicolumn is added.
     *
     * @param string $expr
     * @return string|false php plural expression, or FALSE if failed to
     *  convert expression (invalid expression)
     */
    function gtpexpr_to_php($expr)
    {
        $expr = trim($expr);
        // validate expression
        if (!TranslatorHelpers::is_valid_gtpexpr($expr)) return false;

        $expr = str_replace('n', '$n', $expr);
        return '$plural = ' . $expr . ';';
    }

    /**
     * Check if gettext plural expression is valid
     *
     * @param string $expr
     * @return bool
     * @todo better expression validation
     */
    function is_valid_gtpexpr($expr)
    {
        return '' !== $expr && preg_match('/^[\-+\/%?&!():*><=|n0-9 \t\n\r]+$/'
            , $expr);
    }

    /**
     * Return array with languages grouped by their plural form expressions used in gettext
     *
     * @return array
     */
    function plural_lang_groups()
    {
        return array(
            // one plural form
            1   => array('hu' => 'Hungarian', 'ja' => 'Japanese', 'ko' => 'Korean'
                , 'tr' => 'Turkish'),

            // two plural forms
            21  => array('da' => 'Danish', 'nl' => 'Dutch', 'en' => 'English', 'de' => 'German'
                , 'no' => 'Norwegian', 'sv' => 'Swedish', 'et' => 'Estonian', 'he' => 'Hebrew'
                , 'it' => 'Italian', 'pt' => 'Portuguese', 'es' => 'Spanish', 'eo' => 'Esperanto'),

            22  => array('fr' => 'French', 'pt' => 'Portuguese'),

            // three plural forms
            31  => array('lv' => 'Latvian'),
            32  => array('ga' => 'Irish'),
            33  => array('lt' => 'Lithuanian'),
            34  => array('hr' => 'Croatian', 'ru' => 'Russian', 'sk' => 'Slovak', 'uk' => 'Ukrainian'),
            35  => array('pl' => 'Polish'),

            // four plural forms
            41  => array('sl' => 'Slovenian'),
        );
    }

    /**
     * Get array with plural gettext information by language group code
     *
     * @param int $code
     * @return array|FALSE
     * @see TranslatorHelpers::plural_lang_groups()
     */
    function pform_data_by_code($code)
    {
        $pforms = array(
            1   => array(1, '0'),
            21  => array(2, 'n != 1'),
            22  => array(2, 'n > 1'),
            31  => array(3, '(n%10==1 && (n%100!=11)) ? 0 : (n != 0 ? 1 : 2)'),
            32  => array(3, 'n==1 ? 0 : (n==2 ? 1 : 2)'),
            33  => array(3, '((n%10==1) && (n%100!=11)) ? 0 : ((n%10>=2 && (n%100<10 ||'
                . ' n%100>=20)) ? 1 : 2)'),
            34  => array(3, '((n%10==1) && (n%100!=11)) ? 0 : ((n%10>=2 && n%10<=4 &&'
                . ' (n%100<10 || n%100>=20)) ? 1 : 2)'),
            35  => array(3, 'n==1 ? 0 : ((n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20))'
                . ' ? 1 : 2)'),
            41  => array(4, 'n%100==1 ? 0 : (n%100==2 ? 1 : ((n%100==3 || n%100==4) ? 2 : 3))'),
        );

        if (array_key_exists($code, $pforms)) {
            return $pforms[$code];
        } else {
            return false;
        }
    }

    /**
     * Get array with plural gettext information by language code
     *
     * @param string $language two letter language code (ISO 639)
     * @return array|FALSE
     */
    function pform_data_by_lang($language)
    {
        $language = strtolower($language);
        foreach (TranslatorHelpers::plural_lang_groups() as $group_id => $group) {
            if (array_key_exists($language, $group)) {
                return TranslatorHelpers::pform_data_by_code($group_id);
            }
        }

        return false;
    }
}
