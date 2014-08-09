<?php
/**
 * Add Javadoc link capability to dokuwiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Damien Coraboeuf <dcoraboeuf@yahoo.fr>
 * @author     Stefan Rothe <info@stefan-rothe.ch>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_javadoc extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType() {
        return 'normal';
    }

    function getAllowedTypes() {
        return array('container','substition','protected','disabled','formatting','paragraphs');
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 194;
    }

    /**
     * Allow nesting, i.e. let the plugin accept its own entry syntax
     */
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) {
            return true;
        }
        else {
            return parent::accepts($mode);
        }
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<javadoc\b.*?>(?=.*?</javadoc>)',$mode,'plugin_javadoc');
    }

    /**
     * Add exit pattern to lexer
     */
    function postConnect() {
        $this->Lexer->addExitPattern('</javadoc>','plugin_javadoc');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $site = trim(substr($match,8,-1));
                if (strlen($site) == 0) {
                    return array($state, "jdk");
                }
                // Backwards compatibility
                else if ($site == 'jdk6') {
                    return array($state, 'jdk');
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

    /**
     * Create output
     */
    function render($mode, &$renderer, $indata) {
        $sites = array(
            'jdk' => 'http://docs.oracle.com/javase/7/docs/api',
            // Backwards compatibility
            'doolin' => 'http://www.doolin-guif.net/reports/apidocs',
            // Backwards compatibility
            'commons-beanutil' => 'commons.apache.org/proper/commons-beanutils/api'
        );
        // Add configured default Javadoc URL to sites
        $jdkurl = $this->getConf('jdk');
        if (!empty($jdkurl)) {
            $sites['jdk'] = $jdkurl;
        }

        // Add configured user Javadoc URLs to sites
        for ($i = 1; $i <= 5; $i++) {
            $key = 'user'.$i;
            $config = explode(' ', $this->getConf($key));
            if (count($config) == 1) {
                $url = $config[0];
            }
            else {
                // Alias defined, use it instead of key
                $key = $config[0];
                $url = $config[1];
            }

            if (!empty($url)) {
                $sites[$key] = $url;
            }
        }

        if ($this->getConf('show_icon')) {
            $icon = ' icon';
        }
        else {
            $icon = '';
        }

        if ($mode == 'xhtml'){
            list($state, $data) = $indata;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $prefix =  $sites[$data];
                    $renderer->doc .= '<a class="javadoc'.$icon.'" target="_blank" href="'.$prefix;
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
                    $renderer->doc .= $url.'">'.$text;
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
