<?php
/**
 * DokuWiki Plugin displix (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Frank Schiebel <frank@linuxmuster.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_DISPLIX_TEMPLATES')) define('DOKU_PLUGIN_DISPLIX_TEMPLATES', DOKU_PLUGIN.'displix/tpl');

class  helper_plugin_displix extends DokuWiki_Plugin {

function getMethods(){
    $result = array();
    $result[] = array(
      'name'   => 'untis2timesub',
      'desc'   => 'Converts untis csv to timesub',
      'params' => array(
        'infile' => 'string',
        'outfile' => 'string',
        'number (optional)' => 'integer'),
      'return' => array('pages' => 'array'),
    );
    // and more supported methods...
    return $result;
  }

/*
 * Convert untis csv to timesub aula/lehrer format
 *
 * Untis 21 Felder, hier nur die relevanten
 * Feld 0  -> VertretungsID
 * Feld 1  -> Datum in der Form 20120912
 * Feld 2  -> Stunde
 * Feld 5  -> Absenter Lehrer
 * Feld 6  -> Vertretender Lehrer
 * Feld 7  -> Reguläres Fach
 * Feld 9  -> Vertretungsfach
 * Feld 11 -> Regulärer Raum
 * Feld 12 -> Vertretungsraum
 * Feld 14 -> Reguläre Klassen mit ~ getrennt
 * Feld 16 -> Bemerkung
 * Feld 18 -> Vertretungsklassen durch ~ getrennt
 * Feld 19 -> Vertretungsart
 *
 * Timesub Aula 12 Felder:
 * ID       -> VertretungsID
 * Datum    -> 20120912
 * Datumkurz-> 20.09.2012
 * F1       -> Klasse z.B. "8b" oder "7a,b,e"
 * F2       -> Stunde z.B. "5."
 * F3       -> Regulärer "Lehrer / Fach" z.B. "Müller / Ph"
 * F4       -> Vertretender Lehrer
 * F5       -> Inhalt der Vertretungsstunde (Fach) z.B. "Ch" oder "Vertr."
 * F6       -> Raumnummer
 * F7       -> Bemerkung z.B. "Raumänderung" oder "AA liegt vor" oder "Vorgeholt am..."
 * F8       -> Lehrerkürzel z.B. "Mue"
 * Version  -> ?? am Qg stets "0"
 */
function untis2timesub ($input_basename) {

    global $conf;
    $input_basename = str_replace(":","/",$input_basename);
    $input_basename  = str_replace("//","/", $conf['savedir'] . "/media/" . $input_basename);
    $outhandle_aula = fopen("${input_basename}-aula.csv", "w");
    $outhandle_lehrer = fopen("${input_basename}-lehrer.csv", "w");
    fwrite($outhandle_aula,"ID,Datum,Datumkurz,F1,F2,F3,F4,F5,F6,F7,F8,Version\n");
    fwrite($outhandle_lehrer,"ID,Datum,Datumkurz,F1,F2,F3,F4,F5,F6,F7,F8,Version\n");

    // FIXME to config
    $fs=";";

    if (($handle = fopen("${input_basename}-untis.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, $fs)) !== FALSE) {
            $outline="";

            $tsID           = $data[0]; # ID
            $tsDatum        = $data[1]; # Datum 20120912
            # Datum 12.09.2012 
            $dpts = str_split($data[1], 2);
            $tsDatumkurz    = $dpts[3] . '.' .$dpts[2] . '.' . $dpts[0] . $dpts[1];
            $tsKlassen      = str_replace("~",",",$data[14]); # F1
            $tsStunde       = $data[2] . ".";  # F2
            $tsLeFa         = "$data[5] / $data[7]"; # Lehrer / Fach F3
            $tsLehrer         = $data[5]; # Lehrer
            $tsVertretung   = "$data[6]" ; # Vertretender Lehrer F4
            # Vertretungsfach F5
            $tsVFach        = $data[19] == "L" || $data[19] == "C" ? "entfällt" : "$data[9]" ;
            $tsRaum         = $data[12];
            $tsBemerkung    = $data[16];
            $tsKuerzel      = $data[5];

            # Build csv output for aula file
            $outline  = $tsID .',';
            $outline .= '"' . $tsDatum . '","';
            $outline .= $tsDatumkurz .'","';
            $outline .= $tsKlassen .'","'; # F1
            $outline .= $tsStunde .'","';  # F2
            $outline .= $tsLeFa .'","';      # F3
            $outline .= $tsVertretung .'","';# F4
            $outline .= $tsVFach .'","';     # F5
            $outline .= $tsRaum .'","';     # F6
            $outline .= $tsBemerkung .'","';     # F7
            $outline .= $tsKuerzel .'",';     # F8
            $outline .= "0";
            #Write to file
            fwrite($outhandle_aula, $outline);

            # Build csv output for lehrerzimmer file
            $outline  = $tsID .',';
            $outline .= '"' . $tsDatum . '","';
            $outline .= $tsDatumkurz .'","';
            $outline .= $tsLehrer .'","';
            $outline .= $tsStunde .'","';  
            $outline .= $tsKlassen .'","';
            $outline .= $tsVFach .'","'; 
            $outline .= $tsRaum .'","'; 
            $outline .= $tsKuerzel .'","';
            $outline .= $tsVertretung .'","';
            $outline .= $tsBemerkung .'",';
            $outline .= "0\n";
            #Write to file
            fwrite($outhandle_lehrer, $outline);
        }
        fclose($handle);
    }

    fclose($outhandle_aula);
    fclose($outhandle_lehrer);
    return "";
}


function get_teachertable($input_basename, $showdate="") {
    global $conf;

    
    $input_basename = str_replace(":","/",$input_basename);
    $lehrersub_csv  = str_replace("//","/", $conf['savedir'] . "/media/" . $input_basename ."-lehrer.csv");
    $lehrerinfo_csv = str_replace("//","/", $conf['savedir'] . "/media/" . $input_basename ."-lehrer-info.csv");



    $showdate = "20110912";
    $dpts = str_split($showdate, 2);
    $contentArray["DATUMLANG"]  = $dpts[3] . '.' .$dpts[2] . '.' . $dpts[0] . $dpts[1];

    $substitutions = $this->_file2array($lehrersub_csv, $showdate);
    $infos = $this->_file2array($lehrerinfo_csv, $showdate);

    $actualTeacher = "";
    $cssclass = "zwei";
    foreach ($substitutions as $subst) {
        if ($subst["F1"] != "$actualTeacher" ) {
            $subst["CLASS"] = $cssclass == "eins" ? "zwei" : "eins";
            $cssclass = $subst["CLASS"];
            $actualTeacher = $subst["F1"];
        }
        $subst["CLASS"] = $cssclass;

        $contentArray["CONTENT"] .= $this->_parse_template("lehrer_table_row", $subst);
    }

    $contentArray["ABWKLASSEN"] = $infos[0]["AbwKlassen"];
    $contentArray["ABWKURSE"] = $infos[0]["AbwKurse"];
    $contentArray["ABWLEHRER"] = $infos[0]["AbwLehrer"];
    $contentArray["BLOCKRAUM"] = $infos[0]["FehlRäume"];
    $contentArray["BITTEBEACHTEN"] = $infos[0]["BitteBeachten"];
    $contentArray["VERSION"] = $infos[0]["Version"];
    $contentArray["LETZTEAEND"] = $infos[0]["Druckdatum"];
    $contentArray["UEBERSCHRIFT"] = $infos[0]["Ueberschrift"];
    $contentArray["SCHULNAME"] = $infos[0]["Schulname"];

    $returnContent .= $this->_parse_template("lehrer_main", $contentArray, "file");
    return $returnContent;

}

/*
 *  First line has to be the fieldnames
 */
function _file2array($filename, $showdate="") {

    $line = 0;
    $fs =",";
    if (($handle = fopen("$filename", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, $fs)) !== FALSE) {
            if ( $line == 0 ) {
                $correct_fieldcount = count($data);
                $fields = $data;
                $line++;
                continue;
            }

            $fieldcount = count($data);
            if ( $fieldcount != $correct_fieldcount ) continue;

            for ($i=0; $i<=$fieldcount-1; $i++) {
                $returnarray[$line-1][$fields[$i]] = $data[$i];
            }

            if ( $showdate != "" && $returnarray[$line-1]["Datum"] != $showdate ){
                array_pop($returnarray);
            } else {
                $line++;
            }
        }
    fclose($handle);
    }

    return $returnarray;

}

/**
  *  Parses the arry in the givven template
  *
  *  @params template file to use, array to parse, type (file, string)
  *  @return $html
 **/
function _parse_template($template, $array, $type="file") {

    $html = "";
    if ($type == "file" ) {
        $templateFile = DOKU_PLUGIN_DISPLIX_TEMPLATES . "/$template" . ".tpl";
        if ( ! file_exists($templateFile)) {
            #print "$templateFile not found";
            return $html;
        }
        #print "Parsing into $templateFile";
        $fileCont = file_get_contents("$templateFile");
        $html = $fileCont;
    } else {
        $html = $template;
    }

    foreach(array_keys($array) as $key) {
         $search = "###" . $key . "###";
         $value = $array["$key"];
         #print "$search --- $value <br>";
         $html = str_replace("$search","$value", "$html");
    }
    return $html;
}




}

// vim:ts=4:sw=4:et:
