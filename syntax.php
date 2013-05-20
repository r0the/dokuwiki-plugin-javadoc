<?php
/**
 * Add Javadoc link capability to dokuwiki.
 *
 * by Damien Coraboeuf <dcoraboeuf@yahoo.fr>
 * under the terms of the GNU GPL v2.
 *
 * @license    GNU_GPL_v2
 * @author     Damien Coraboeuf <dcoraboeuf@yahoo.fr>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');


class syntax_plugin_javadoc extends DokuWiki_Syntax_Plugin {

    function getInfo(){
        return array(
            'author' => 'Damien Coraboeuf',
            'email'  => 'dcoraboeuf@yahoo.fr',
            'date'   => '2007-09-19',
            'name'   => 'Javadoc Plugin 1.0.0 (Beta 01)',
            'desc'   => 'Add Javadoc link Capability',
            'url'    => 'http://doolin.x10hosting.com/wiki/doku.php?id=plugin:javadoc',
        );
    }

    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'normal';
    }

    function getAllowedTypes() {
        return array('container','substition','protected','disabled','formatting','paragraphs');
    }

    function getSort() {
        return 194;
    }

    // override default accepts() method to allow nesting
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) {
            return true;
        }
        else {
            return parent::accepts($mode);
        }
    }

    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<javadoc.*?>(?=.*?</javadoc>)',$mode,'plugin_javadoc');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</javadoc>','plugin_javadoc');
    }

    function handle($match, $state, $pos, &$handler){
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $site = trim(substr($match,8,-1));
                if (strlen($site) == 0) {
                    return array($state, "jdk6");
                }
                else {
                    return array($state, $site);
                }
            case DOKU_LEXER_UNMATCHED :
                return array($state, $match);
            default:
                return array($state);
        }
    }

    function render($mode, &$renderer, $indata) {
        $sites = array (
            'jdk6' => 'http://java.sun.com/javase/6/docs/api',
            'doolin' => 'http://doolin-guif.sourceforge.net/apidocs',
            'commons-beanutil' => 'http://commons.apache.org/beanutils/commons-beanutils-1.7.0/docs/api/'
        );
        if ($mode == 'xhtml'){
            list($state, $data) = $indata;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $prefix = $sites[$data];
                    $renderer->doc .= '<a class="javadoc" target="_blank" href="'.$prefix;
                    break;
                case DOKU_LEXER_UNMATCHED :
                    // Get the token and the text, separated by an optional "|"
                    $indexOfTextSeparator = strrpos($data,"|");
                    if ($indexOfTextSeparator === false) {
                        $token = $data;
                        $text = $renderer->_xmlEntities(str_replace("#", ".", $data));
                    }
                    else {
                        $token = substr($data, 0, $indexOfTextSeparator);
                        $text = substr($data, $indexOfTextSeparator + 1);
                    }
                    // Get the class name and the method
                    $indexOfMethodSep = strrpos($token,"#");
                    if ($indexOfMethodSep === false) {
                        $url = "/".str_replace(".", "/", $token).'.html';
                    }
                    else {
                        $className = substr($token, 0, $indexOfMethodSep);
                        $className = str_replace(".", "/", $className);
                        $methodName = substr($token, $indexOfMethodSep + 1);
                        $url = "/".$className.".html#".$methodName;
                    }
                    $renderer->doc .= $url.'"><code>'.$text.'</code>';
                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= "</a>";
                    break;
            }
            return true;
        }
        // unsupported $mode
        return false;
    }
}

?>
