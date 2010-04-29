<?PHP
/*
Dieses PHP-Skript wurde von Jens Schejbal geschrieben und
ueber die Aktiven-ML der Piratenpartei geschickt.

Torsten Fehre hat dieses Skript leicht erweitert um die Moeglichkeit,
Arntragsart und User-Name ueber ein Webinterface eingegeben werden koennen
und das Ganze unter die No Whining Licence gestellt
Privacy hat ein paar Fehler beseitigt, Stimmzaehlung und Einfaerbung ergaenzt
*/
ini_set ('user_agent', "Antragsfabrikchecker"); 

// Beginn Contribution Torsten Fehre
switch($_REQUEST['cat']) {
case 'programm':
	$cat = "Programmantrag_AF_Bundesverband";
	break;
case 'satzung':
	$cat = "Satzungsänderungsantrag_AF_Bundesverband";
	break;
case 'sonstige':
	$cat = "Sonstiger_Antrag_AF_Bundesverband";
	break;
default:
	die("Kategorie {$_REQUEST['cat']} unbekannt!");
}
if ($_REQUEST['klar'] == "") {
  $username = "[[Benutzer:{$_REQUEST['user']}|{$_REQUEST['user']}]]";
}
else {
 $username = "[[Benutzer:{$_REQUEST['user']}|{$_REQUEST['klar']}]]";
}
// Ende Contribution Torsten Fehre + Privacy

$daten = file_get_contents("http://wiki.piratenpartei.de/api.php?action=query&list=categorymembers&cmtitle=Category:$cat&cmlimit=500&cmnamespace=0&format=php");
$echtdaten = unserialize($daten);

$anzahl = count($echtdaten['query']['categorymembers']);


?>
<html><head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<style>
  .err {background-color:#000;color:#fff;}
  .state0 {background-color:#ffa;}
  .state1 {background-color:#afa;}
  .state2 {background-color:#faa;}
  .state3 {background-color:#aaa;}
</style>
</head><body>
<?PHP
echo "$anzahl Treffer!\n";
echo '<table>';
echo "<tr><td>Wahl</td><td>ID</td><td>Titel</td><td>Stimmen</td><td>+</td><td>-</td><td>o</td></tr>\n";
for ($i = 0; $i<$anzahl; $i++) {
  $titel = $echtdaten['query']['categorymembers'][$i]['title'];
  //if (substr($titel,0,7)=='Archiv:') continue;
  $link = "http://wiki.piratenpartei.de/index.php?title=".urlencode($titel);
  $text = file($link."&action=raw");
  $state = 0;
  $found = 0;
  $aktzahl = 0;
  $svote=0;
  $id = '?????';
  for ($zeile = 0; $zeile < count($text); $zeile++) {
    if (trim($text[$zeile])=="==== Piraten, die vrstl. FÜR diesen Antrag stimmen ====") {
      if ( $state != 0 ) {echo "<tr><td class=\"err\" colspan=3>INVALID STATE $state AT <a href=\"$link\">$titel</a></td></tr>\n"; continue;}
      $state = 1; $aktzahl=0;
    }
    if (trim($text[$zeile])=="==== Piraten, die vrstl. GEGEN diesen Antrag stimmen ====") {
      if ( $state != 1 ) {echo "<tr><td class=\"err\" colspan=3>INVALID STATE $state AT <a href=\"$link\">$titel</a></td></tr>\n"; continue;}
      $state = 2;$pos=$aktzahl;$aktzahl=0;
    }
    if (trim($text[$zeile])=="==== Piraten, die sich vrstl. enthalten ====") {
      if ( $state != 2 ) {echo "<tr><td class=\"err\" colspan=3>INVALID STATE $state AT <a href=\"$link\">$titel</a></td></tr>\n"; continue;}
      $state = 3;$neg=$aktzahl;$aktzahl=0;
    }
    if (trim($text[$zeile])=="=== Diskussion ===") {
      if ( $state != 3 ) {echo "<tr><td class=\"err\" colspan=3>INVALID STATE $state AT <a href=\"$link\">$titel</a></td></tr>\n"; continue;}
      $state = 4;$ent=$aktzahl;$aktzahl=0;
    }
    if ( strstr($text[$zeile],"[[Benutzer:") && $state > 0 && $state < 4) $aktzahl++;
    if ( strstr($text[$zeile],$username) && $state > 0 && $state < 4) {
      //echo "$state\t$titel\n";
      if ($found) echo "<tr><td class=\"err\" colspan=3>DUPE AT <a href=\"$link\">$titel</a></td></tr>\n\n";
      $found = $state;
    }
    if (substr($text[$zeile],0,16)=='| Nummer      = ') {
      $id = substr(trim($text[$zeile]),16);
    }
  }
  if ( $state != 4 ) {echo "<tr><td class=\"err\" colspan=3>INVALID STATE $state AT <a href=\"$link\">$titel</a></td></tr>\n"; continue;}
  if (!$found) {$foundsig = "X";$found=0;}
  if ($found == 1) $foundsig = "+";
  if ($found == 2) $foundsig = "-";
  if ($found == 3) $foundsig = "o";
  $votes=$pos+$neg+$ent;
  if ($votes > 10) {
	  $svote = (($pos - $neg) > 0) ? 1 : (($pos -$neg < 0)) ? 2 : 3;
	}
  echo "<tr><td class=\"state$found\">$foundsig</td><td>$id</td><td class=\"state$svote\"><a href=\"$link\">$titel</a></td><td>$votes</td><td>$pos</td><td>$neg</td><td>$ent</td></tr>\n";
  sleep(10);
}

echo '</table></body></html>';
?>
