<?php
/**
 * @version $Revision: 862 $
 */

/**
 * Helper class (model) for page tags
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class PageTags
{
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $_database;

    /**
     * Name of the table for storing page tags
     *
     * @var string
     * @access protected
     */
    var $_table = 'tags';

    /**
     * Class constructor
     *
     * @param Database $database
     * @return PageTags
     */
    function PageTags(&$database)
    {
        $this->_database = &$database;
    }

    /**
     * Split tags string entered by user into array separating every tag
     *
     * Tags should be separated with space characters. Tags that contains several words should
     * be enclosed with quotes.
     *
     * @param string $tags
     * @return array
     * @static
     */
    function splitTags($tags)
    {
        $tags_array = array();
        preg_match_all('/(?:".{2,}")|(?:\S{2,})/', $tags, $tags_array);
        foreach ($tags_array[0] as $k => $v) {
            $tags_array[0][$k] = trim($v, '"');
        }
        return $tags_array[0];
    }

    /**
     * Set tags for page
     *
     * @param int $page_id
     * @param string $language 2 char language code (uppercased)
     * @param mixed $tags if is not array than it will be processed via
     *  PageTags::splitTags() to extract array of tags
     */
    function setTags($page_id, $language, $tags)
    {
        if (!is_array($tags)) {
            $tags = $this->splitTags($tags);
        }

        // remove previous tags
        $this->removeTags($page_id);

        if ($tags) {
            // insert current tags
            foreach ($tags as $tag) {
                $this->_database->query('INSERT INTO ?f VALUES (?@)', $this->_table
                    , array($page_id, $tag, $language));
            }
        }
    }

    /**
     * Remove all tags for specified page
     *
     * @param int $page_id
     * @return int count of removed tags
     */
    function removeTags($page_id)
    {
        $this->_database->query('DELETE FROM ?f WHERE `content` = ?', $this->_table
            , $page_id);
        return $this->_database->affected_rows();
    }
}