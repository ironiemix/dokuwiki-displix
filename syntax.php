<?php
/**
 * DokuWiki Plugin displix (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Frank Schiebel <frank@linuxmuster.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_displix extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 222;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{displix\}\}',$mode,'plugin_displix');
//        $this->Lexer->addEntryPattern('<FIXME>',$mode,'plugin_displix');
    }

//    public function postConnect() {
//        $this->Lexer->addExitPattern('</FIXME>','plugin_displix');
//    }

    public function handle($match, $state, $pos, &$handler){
        $data = array();

        return $data;
    }

    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
        if (!$myhf =& plugin_load('helper', 'displix')) return false;

        // disable caching
        $renderer->info['cache'] = false;

        $renderer->doc .= "Hallo Displix";
        $renderer->doc .= $myhf->untis2timesub("/home/linuxmuster-portfolio/data/media/untis.csv","/home/linuxmuster-portfolio/data/media/out.csv");

        return true;
    }
}

// vim:ts=4:sw=4:et:
