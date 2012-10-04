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

class helper_plugin_displix extends DokuWiki_Plugin {


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
function untis2timesub ($infile, $outfile) {
    $outhandle = fopen("$outfile", "w");
    fwrite($outhandle,"ID,Datum,Datumkurz,F1,F2,F3,F4,F5,F6,F7,F8,Version\n");

    // FIXME to config
    $fs=";";

    if (($handle = fopen("$infile", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, $fs)) !== FALSE) {

            $tsID           = $data[0]; # ID
            $tsDatum        = $data[1]; # Datum 20120912
            # Datum 12.09.2012 
            $dpts = str_split($data[1], 2);
            $tsDatumkurz    = $dpts[3] . '.' .$dpts[2] . '.' . $dpts[0] . $dpts[1];
            $tsKlassen      = str_replace("~",",",$data[14]); # F1
            $tsStunde       = $data[2] . ".";  # F2
            $tsLeFa         = "$data[5] / $data[7]"; # Lehrer / Fach F3
            $tsVertretung   = "$data[6]" ; # Vertretender Lehrer F4
            # Vertretungsfach F5
            $tsVFach        = $data[19] == "L" || $data[19] == "C" ? "entfällt" : "$data[9]" ;
            $tsRaum         = $data[12];
            $tsBemerkung    = $data[16];
            $tsKuerzel      = $data[5];

            # Build csv output
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
            $outline .= $tsKuerzel .'","';     # F8
            $outline .= "0\n";
            #Write to file
            fwrite($outhandle, $outline);
        }
        fclose($handle);
    }

    fclose($outhandle);
    return "";
}


}

// vim:ts=4:sw=4:et:
