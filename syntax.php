<?php
/**
 * Displix Plugin: Create pages for digital signage
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Frank Schiebel <frank@linuxmuster.net>  
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_displix extends DokuWiki_Syntax_Plugin {

    function getType(){ return 'protected';}
    function getAllowedTypes() { return array('container','substition','protected','disabled','formatting','paragraphs'); }
    function getPType(){ return 'block';}

    // must return a number lower than returned by native 'code' mode (200)
    function getSort(){ return 195; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {       
      $this->Lexer->addEntryPattern('<displix>(?=.*?</displix.*?>)',$mode,'plugin_displix');
      $this->Lexer->addEntryPattern('<displix\s[^\r\n\|]*?>(?=.*?</displix.*?>)',$mode,'plugin_displix');
      $this->Lexer->addEntryPattern('<displix\|(?=[^\r\n]*?\>.*?</displix.*?\>)',$mode,'plugin_displix');      
      $this->Lexer->addEntryPattern('<displix\s[^\r\n\|]*?\|(?=[^\r\n]*?>.*?</displix.*?>)',$mode,'plugin_displix');      
    }

    function postConnect() {
      #$this->Lexer->addPattern('<row>.+?</row>','plugin_displix');
      $this->Lexer->addPattern('<row\s[^\r\n\|]*?>.+?</row>','plugin_displix');
      $this->Lexer->addExitPattern('</displix.*?>', 'plugin_displix');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $data = substr($match, 8, -1);
                return array('displix_start',$data);

            case DOKU_LEXER_MATCHED:
		 # Optionen für die rows
		 preg_match("|<row(.*?)>|", $match, $rowoptions);
	         $rowoptions = $rowoptions[1];
		 preg_match("|split\s?=\s?\"(.*?)\"|", $rowoptions, $hit);
		 $options["split"] = $hit[1];
		 preg_match("|height\s?=\s?\"(.*?)\"|", $rowoptions, $hit);
		 $options["height"] = $hit[1];
		 preg_match("|align\s?=\s?\"(.*?)\"|", $rowoptions, $hit);
		 $options["align"] = $hit[1];
		 # DataString zwischen den row-tags
		 preg_match("|<row.*?>(.*?)</row>|", $match, $rowdata);
		 $rowdata = $rowdata[1];

                 return array('data', $rowdata, $options);

            case DOKU_LEXER_UNMATCHED:                
                $handler->_addCall('cdata',array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:
                $data = trim(substr($match, 9, -1));
                return array('displix_end', $data);

        }       
        return false;
    }

    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $indata) {

      if (empty($indata)) return false;
      list($todo, $data, $options) = $indata;

      if($mode == 'xhtml'){
          switch ($todo) {
          case 'displix_start' : 
            $renderer->doc .= '<div id="displix">';
            break;

          case 'data' :      
	    # Testen, ob die Optionen zu $data passen
	    $splitparts = preg_split("/:/", $options["split"]);
	    $dataparts = preg_split("/\|/", $data);
	    $numsplits = count($splitparts);
	    $numdatas = count($dataparts);
	    if ( $numsplits != $numdatas ) {
		$renderer->doc .= "<div>FEHLER: Zahl der Split-Angaben für die Horizontale  entspricht nicht der Zahl der Datenteile</div>";
		break;
	    }

	    for ($i = 1; $i <= $numsplits; $i++) {
		if ( isset($options["height"]) ) {
			$heightstyle="height:" . $options["height"] . "px;";
		} else { 
			$heightstyle="";
		}
		
		if ( isset($options["align"]) ) {
			$alignstyle="text-align:" . $options["align"] . ";";
		} else { 
			$alignstyle="";
		}

		# check if the contents is readable and exists...
		$pagestring = $dataparts[$i-1];
		# Platzhalter ersetzen...
		$dayofweek = date('D', strtotime("now"));
		$pagestring = str_replace("###weekday###", $dayofweek, $pagestring);
		# saubere SeitenID		
		$page_id = cleanID($pagestring);
		
      		if(auth_quickaclcheck($page_id) <= AUTH_READ) {
			$renderer->doc .= "<div id=\"displixrow\" style=\"float: left;" . $heightstyle . $alignstyle ."width: " . $splitparts[$i-1] . "%\"> ";
			$renderer->doc .= "Fehler: Ungenügende Zugriffsrechte!";
			$renderer->doc .= "</div>";
		} else {
			$renderer->doc .= "<div id=\"displixrow\" style=\"float: left;" . $heightstyle . $alignstyle . "width: " . $splitparts[$i-1] . "%\"> ";
			$renderer->doc .=  tpl_include_page($page_id, false);
			$renderer->doc .= "</div>";
		}
	    }
	    //$renderer->doc .= "<div class=\"clearer\">";
	

            //$renderer->doc .= "Data<br>" . $numsplits . " " .$numdatas . " " . $data;
            //$renderer->doc .= $options["height"];
            //$renderer->doc .= $renderer->_xmlEntities($data); 
            break;

          case 'displix_end' : 
            $renderer->doc .= '</div>';
            break;
        }
        return true;
      }
      return false;
    }


}

//Setup VIM: ex: et ts=4 enc=utf-8 :
