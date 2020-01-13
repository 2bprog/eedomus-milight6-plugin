<?
// -----------------------------------------------------------------------------
// 2B_milight6 : interface de pilotage des ampoules milight v6
// -----------------------------------------------------------------------------
// basé sur   
// https://github.com/Basch3000/php-milight
// https://github.com/domoticz/domoticz/blob/development/hardware/Limitless.cpp
// -----------------------------------------------------------------------------
// Autres infos : 
// https://github.com/BKrajancic/LimitlessLED-DevAPI
// 
// &vars=[VAR1]
//   VAR1 :  [ip:ports:portr,group,type]
//   		- ip:ports:portr  : ip + port envoi + port reception du bridge v6
//   		- group    : Identifiant du groupe [0|1|2|3|4|5] 0 = tous 5 = iBox
//   		- type rgbw|rgbww
// &cmd=
//  on
//	off
//	bri : [0 à 100]
//	bridown
//	briup
//	towhite
//	tonight
//	color : [0 à 100],[0 à 100],[0 à 100]]
//  sat : [0 à 100]
//	mode : [1 à 9] 
//  modedown
//  modeup
// 	slower
//  temp : [0 à 100] 0 Froid - 100 Chaud
//  tempdown
//  tempup
// [&adjust = [0 a 100]]
// [&set=(on "0 à 1")]
// [&api=(onapi "Code API")]
//
// -----------------------------------------------------------------------------
// Obsolete [&bri= | &color= | &temp = &mode] = ] 
// -----------------------------------------------------------------------------
// 6B7E

sdk_header("text/xml");
echo "<milight6>\r\n";

// Lecture ip + port envoi + port reception
$vars = getArg('vars', false, ',0,');
$varsar = explode(',',$vars);
$hostp = $varsar[0];
$hostar = explode(":",$hostp); 

$host = '';  // ip 
$ports = '5987'; // port 5987 par defaut
$portr = '55054'; // port 55054 par defaut

if (count($hostar) > 0) $host=$hostar[0];
if (count($hostar) > 1) $ports=$hostar[1];
if (count($hostar) > 2) $portr=$hostar[2];

$group = $varsar[1]; // 0 - all ou 1,2,3,4  ou 5 pour la ibox
$type = strtolower($varsar[2]); // rgbw ou rgbww
$cmdar = strtolower(getArg('cmd',false, '')); // commande

$cmdar = explode(':', $cmdar);
$cmd = '';
$cmdparam = '';
if (count($cmdar) > 0) $cmd=$cmdar[0];
if (count($cmdar) > 1) $cmdparam=$cmdar[1];

$color = getArg('color',false, ''); 	
$temp = getArg('temp',false, '');  		
$mode = getArg('mode',false, 1); 

if ($cmd == 'bri' && $cmdparam !== '')			$bri = $cmdparam;  		else $bri = getArg('bri',false, -1);
if ($cmd == 'sat' && $cmdparam !== '')			$sat = $cmdparam;  		else $sat = getArg('sat',false, -1);
if ($cmd == 'color' && $cmdparam !== '') 		$color = $cmdparam;  	else $color = getArg('color',false, '');
if ($cmd == 'temp' && $cmdparam !== '')			$temp = $cmdparam;  	else $temp = getArg('temp',false, -1);
if ($cmd == 'mode' && $cmdparam !== '')	$mode = $cmdparam;	else $mode = getArg('mode',false, -1);

$adjust = getArg('adjust',false, 0);  // parametre : ajoutement de la couleur
$seton= getArg("set",false, 0); //on
$apion= getArg("api",false, 0); //onapi

$perreur = '';



if ($host =='') $perreur .= "[host vide] ";	// host vide
if ($group < 0 || $group > 5) $perreur .= "[groupe ($group) incorrect] ";		
if ( $cmd=='bri' && (($bri < 0) || ($bri > 100)))	$perreur .= "[bri ($bri) incorrect] ";	
if ( $cmd=='sat' && (($sat < 0) || ($sat > 100)))	$perreur .= "[sat ($sat) incorrect] ";	
if ( $cmd=='color' && (count(explode(',', $color)) != 3))	$perreur .= "[color ($color) incorrect] ";	
if ( $cmd=='mode' && (($mode < 1) || ($mode > 9)))	$perreur .= "[mode ($mode) incorrect] ";	

if ($group == 5) $typen = 0;
else if ($type == 'rgbww') $typen = 1;
else if ($type == 'rgbw') $typen = 2;
else if ($type == 'rgb') $typen = 3;
else if ($type == 'white') $typen = 4;
else $perreur .= "[type ($type) incorrect] ";	// type incorrect


// 0 - bridge / 1 - rgbww / 2 rgbw / 3 rgb / 4 white
$v6Codes = array(
	'0on' => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x03, 0x00, 0x00, 0x00, 0x01 ),
	'1on' => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x01, 0x00, 0x00, 0x00, 0x00 ),
	'2on' => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x01, 0x00, 0x00, 0x00, 0x00 ),
	'3on' => array( 0x31, 0x00, 0x00, 0x05, 0x02, 0x09, 0x00, 0x00, 0x00, 0x00 ),
	'4on' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x07, 0x00, 0x00, 0x00, 0x00 ),

	'0off' => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x04, 0x00, 0x00, 0x00, 0x01 ),
	'1off' => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x02, 0x00, 0x00, 0x00, 0x00 ),
	'2off' => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x02, 0x00, 0x00, 0x00, 0x00 ),
	'3off' => array( 0x31, 0x00, 0x00, 0x05, 0x02, 0x0A, 0x00, 0x00, 0x00, 0x00 ),
	'4off' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x08, 0x00, 0x00, 0x00, 0x00 ),

	'0color' => array( 0x31, 0x00, 0x00, 0x00, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x01 ),
	'1color' => array( 0x31, 0x00, 0x00, 0x08, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00 ),
	'2color' => array( 0x31, 0x00, 0x00, 0x07, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00 ),
	'3color' => array( 0x31, 0x00, 0x00, 0x05, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00 ),
	// 4color : N/A
	
	'1temp' => array( 0x31, 0x00, 0x00, 0x08, 0x05, 0x00, 0x00, 0x00, 0x00, 0x00 ), //(6th hex values 0x00 to 0x64 : examples: 00 = 2700K (Warm White), 19 = 3650K, 32 = 4600K, 4B, = 5550K, 64 = 6500K (Cool White))

    '4tempdown' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x04, 0x00, 0x00, 0x00, 0x00 ),
    '4tempup' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x03, 0x00, 0x00, 0x00, 0x00 ),
	
	'0bri' => array( 0x31, 0x00, 0x00, 0x00, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x01 ),
	'1bri' => array( 0x31, 0x00, 0x00, 0x08, 0x03, 0xBE, 0x00, 0x00, 0x00, 0x00 ),
	'2bri' => array( 0x31, 0x00, 0x00, 0x07, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x00 ),

	'1sat' => array( 0x31, 0x00, 0x00, 0x08, 0x02, 0x64, 0x00, 0x00, 0x00, 0x00 ),
	
	'3bridown' => array( 0x31, 0x00, 0x00, 0x05, 0x02, 0x01, 0x00, 0x00, 0x00, 0x00 ),
	'4bridown' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x02, 0x00, 0x00, 0x00, 0x00 ),

	'3briup' => array( 0x31, 0x00, 0x00, 0x05, 0x02, 0x02, 0x00, 0x00, 0x00, 0x00 ),
	'4briup' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x01, 0x00, 0x00, 0x00, 0x00 ),

	'0towhite' => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x05, 0x00, 0x00, 0x00, 0x01 ),
	'1towhite' => array( 0x31, 0x00, 0x00, 0x08, 0x05, 0x64, 0x00, 0x00, 0x00, 0x00 ),
	'2towhite' => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x05, 0x00, 0x00, 0x00, 0x00 ),

	// 0tonight => a simuler avec bri a 0
	'1tonight' => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x05, 0x00, 0x00, 0x00, 0x00 ),
	'2tonight' => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x06, 0x00, 0x00, 0x00, 0x00 ),
	// 3tonight => N/A
	'4tonight' => array( 0x31, 0x00, 0x00, 0x01, 0x01, 0x06, 0x00, 0x00, 0x00, 0x00 ),
	 
	'0mode' => array( 0x31, 0x00, 0x00, 0x00, 0x04, 0x01, 0x00, 0x00, 0x00, 0x01 ), //(6th hex values 0x01 to 0x09 : examples: 01 = Mode1, 02 = Mode2, 03 = Mode3 .. 09 = Mode9)
	'1mode' => array( 0x31, 0x00, 0x00, 0x08, 0x06, 0x01, 0x00, 0x00, 0x00, 0x00 ),  //(6th hex values 0x01 to 0x09 : examples: 01 = Mode1, 02 = Mode2, 03 = Mode3 .. 09 = Mode9)
	'2mode' => array( 0x31, 0x00, 0x00, 0x07, 0x04, 0x01, 0x00, 0x00, 0x00, 0x00 ),   //(6th hex values 0x01 to 0x09 : examples: 01 = Mode1, 02 = Mode2, 03 = Mode3 .. 09 = Mode9)

	'3modedown' => array( 0x31, 0x00, 0x00, 0x05, 0x01, 0x05, 0x00, 0x00, 0x00, 0x00 ),
	'3modeup' => array( 0x31, 0x00, 0x00, 0x05, 0x01, 0x06, 0x00, 0x00, 0x00, 0x00 ),

	
	'0slower' => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x01, 0x00, 0x00, 0x00, 0x01 ),
	'1slower' => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x04, 0x00, 0x00, 0x00, 0x00 ),
	'2slower' => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x03, 0x00, 0x00, 0x00, 0x00 ),
	'3slower' => array( 0x31, 0x00, 0x00, 0x05, 0x01, 0x03, 0x00, 0x00, 0x00, 0x00 ),

	'0faster' => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x02, 0x00, 0x00, 0x00, 0x01 ),
	'1faster' => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x03, 0x00, 0x00, 0x00, 0x00 ),
	'2faster' => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x04, 0x00, 0x00, 0x00, 0x00 ),
	'3faster' => array( 0x31, 0x00, 0x00, 0x05, 0x01, 0x04, 0x00, 0x00, 0x00, 0x00 )
);

		

echo "<input>\r\n";
echo "<host>".$host."</host>\r\n";
echo "<ports>".$ports."</ports>\r\n";
echo "<portr>".$portr."</portr>\r\n";
echo "<group>".$group."</group>\r\n";
echo "<type>".$type."</type>\r\n";
echo "<cmd>".$cmd."</cmd>\r\n";
echo "<bri>".$bri."</bri>\r\n";
echo "<sat>".$sat."</sat>\r\n";
echo "<color>".$color."</color>\r\n";
echo "<temp>".$temp."</temp>\r\n";
echo "<adjust>".$adjust."</adjust>\r\n";
echo "<mode>".$mode."</mode>\r\n";
echo "<seton>".$seton."</seton>\r\n";
echo "<apion>".$apion."</apion>\r\n";
echo "</input>\r\n";

echo "<tmt>\r\n";


if ($perreur == '')
{	
	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if (socket != false) 
	{
		$ssIDs = sdk_milight6_getSessionIDs($socket, $host, $ports, $portr);
		if (!($ssIDs[0] == 0 && $ssIDs[1] == 0))
		{
			switch  ($cmd)
			{
				case 'on':
				case 'off': // cmd
				     
					sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.$cmd], $group);
					break;
					
				case 'bri':	 // on , sleep, bri 
					sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'on'], $group);
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))
					{
						$briCodes = $v6Codes[$typen.$cmd];
						$briCodes[0x05] = $bri;
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $briCodes , $group);
					}
					break;
					
				case 'modedown':
				case 'modeup':
				case 'tempdown':
				case 'tempup':
				case 'bridown':
				case 'briup':
				case 'towhite': // on, sleep, cmd
					sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'on'], $group);
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))					
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.$cmd], $group);
					break;
								
				case 'tonight':
					if ($group == 5)
					{
						// on, sleep, white,  sleep, bri a 1
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'on'], $group);						
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'towhite'], $group);						
						$briCodes = $v6Codes[$typen.'bri'];
						$briCodes[0x05] = 1;
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $briCodes , $group);
					}
					else
					{
						// cmd		
						if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))							
							sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.$cmd], $group);
					}
					break;
				case 'color': // on, color
					sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'on'], $group);						
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))	
					{
						$rgb = explode(',', $color);
						$hsl = sdk_rgbToHsl(floor($rgb[0] * 2.54), floor($rgb[1] * 2.54), floor($rgb[2] * 2.54));
						
						if (ntype == 1)
						{	
							if (sdk_checkcodes($v6Codes, $typen.'$sat', $perreur))
							{
								$satCodes =  $v6Codes[$typen.'$sat'];
								$sat = round($hsl[1] * 100);;
								$satCodes[0x5] = $sat; 
								sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $satCodes , $group);
							}
						}
						$hue = floor((($hsl[0] + $adjust) / 360) * 255); 			
						$colCodes =  $v6Codes[$typen.$cmd];
						$colCodes[0x05] = $hue;
						$colCodes[0x06] = $hue;
						$colCodes[0x07] = $hue;
						$colCodes[0x08] = $hue;
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $colCodes , $group);
					}
					break;
				case 'temp':
					if ($temp < 0) $temp = 0; // froid
					if ($temp > 100) $temp = 100; // chaud
					
					// seulement pour RGBWW
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))		
					{
						$tempCodes =  $v6Codes[$typen.$cmd];
						$temp = abs($temp - 100);
						$tempCodes[0x5] = $temp; 
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $tempCodes , $group);
					}
					break;
					
				case 'sat':
					if ($sat < 0) $sat = 0; 
					if ($sat > 100) $sat = 100; 
					
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))
					{
						$satCodes =  $v6Codes[$typen.$cmd];
						$satCodes[0x5] = $sat; 
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $satCodes , $group);
					}
					break;
				
				case 'mode':
					// on , cmd * x
					sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'on'], $group);						
					
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))
					{
							$discoCodes =  $v6Codes[$typen.$cmd];
						if ($mode >= 0 && $mode <= 9) $discoCodes[0X05] = $mode - 1 ; 
						sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $discoCodes , $group);
					}
					break;
					
				
				case 'slowest':	
				case 'slower':	
				case 'faster':				
				case 'fastest':	
					// on , cmd * x
					sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.'on'], $group);						
					if (sdk_checkcodes($v6Codes, $typen.$cmd, $perreur))		
					{
						$imax = 1;
						$cmdtmp = $cmd;
						if ($cmd == 'slowest' || $cmd == 'fastest')  $imax = 8;
						if ($cmd == 'slowest')  $cmdtmp = 'slower';
						if ($cmd == 'fastest')  $cmdtmp = 'faster';
						
						for ($i=0 ; $i < $imax ; $i++)
						{
							sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, $v6Codes[$typen.$cmdtmp], $group);						
						}
					}
					break;
				default:
					$perreur .= "[commande $cmd inconnu] ";	
			
			}
		}
		else
		{
			$perreur .= "[Probleme de recuperation ssID1 et  ssID2] ";	
		}	
		socket_close($socket);
	}
	else
	{
		$perreur .= "[Erreur creation socket] ";			
	}
}

if ($perreur != '') 
{
	echo "<error>$perreur</error>\r\n";	
}
else
{
	if ($seton != 0)
	{
		echo "<seton>\r\n";
		$onitem=getValue($apion);
		if ($onitem["value"] == 0)	
		{
			echo "<setValue>-1</setValue>\r\n";
			setValue($apion, -1, false, true);		
		}
		echo "</seton>\r\n";
	}
}
echo "</tmt>\r\n";
echo "</milight6>\r\n";



function sdk_checkcodes(&$v6Codes, $id , &$erreur)
{
	$ret = isset($v6Codes[$id]);
	if ($ret != true)
	{
		$erreur.= "[Erreur code $id introuvable] ";	
	}
	return $ret;
}
//Fin

// -----------------------------------------------------------------------------
// Functions
function sdk_milight6_getSessionIDs($socket, $host, $ports, $portr)
{
	echo "<session>\r\n";

	$ssIDs = array(0,0);
	$v6GetSessionID = array( 0x20, 0x00, 0x00, 0x00, 0x16, 0x02, 0x62, 0x3A, 0xD5, 0xED, 0xA3, 0x01, 0xAE, 0x08, 0x2D, 0x46, 0x61, 0x41, 0xA7, 0xF6, 0xDC, 0xAF, 0xD3, 0xE6, 0x00, 0x00, 0x1E );
	
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 1, "usec" => 0));
	
	$trame = vsprintf (str_repeat('%c', count($v6GetSessionID)), $v6GetSessionID);	
	
	echo "<params>$host,$ports,$portr</params>\r\n";	
    echo "<trame>".bin2hex($trame)."</trame>\r\n";	
 
    socket_sendto($socket, $trame, strlen($trame), 0, $host, $ports);
	$rlen = socket_recvfrom($socket, $buffer, 1024, 0, $host, $portr);
	echo "<receivelen>".$rlen."</receivelen>\r\n";	
	echo "<receiveddump>".bin2hex($buffer)."</receiveddump>\r\n";		
	$output =  array_merge(unpack('C*', $buffer));
   

	$retry = 1;
	if (count($output) == 0x16)
	{
	    if ($output[0x13] == 0 &&	$output[0x14] == 0)
	    {
	        echo "<raz>1</raz>\r\n";	
	        $output = array();
	        socket_sendto($socket, $trame, strlen($trame), 0, $host, $ports);
	    }
	}
	while ((count($output) < 1) && (count($output) != 0x16))
	{
		echo "<retry>".$retry."</retry>\r\n";	
		$retry++;
		$rlen = socket_recvfrom($socket, $buffer, 1024, 0, $host, $portr);
		echo "<receivelen>".$rlen."</receivelen>\r\n";	
		echo "<receiveddump>".bin2hex($buffer)."</receiveddump>\r\n";		
		$output = array_merge(unpack('C*', $buffer));
		if ($retry > 5) break;				
	}
	

	if ( (count($output) == 0x16) && ($output[0x00] == 0x28) && ($output[0x15] == 0x00))
	{
		$ssIDs[0] = $output[0x13];
		$ssIDs[1] = $output[0x14];
		echo "<ssID0>".$ssIDs[0]."</ssID0>\r\n";	
		echo "<ssID1>".$ssIDs[1]."</ssID1>\r\n";	
	}			
	echo "</session>\r\n";

	return $ssIDs;
}


function sdk_milight6_send($socket, $host, $ports, $portr, $ssIDs, Array $command, $group)
{	
	
	// verification socket et sessionid 1 et 2
	if ($socket == false) return ;
	if ($ssIDs[0] == 0 && $ssIDs[1] == 0) return ;
	
	echo "<send>\r\n";
	$retrynb = 3;
	
	// construction de la trame + calcul crc
	$v6PreAmble = array( 0x80, 0x00, 0x00, 0x00, 0x11 );
	$v6PreData = array( $ssIDs[0], $ssIDs[1], 0x00, 0x00, 0x00);
	
	if ($group != 5) $command[0x09] = $group;
			
	$command = array_merge($command, array(0x00));
	$commandsize = count($command);	
	$crc = 0;
	for ($i = 0; $i < $commandsize ; $i++)
	{ $crc = ($crc + $command[$i]) & 0xFF; }

	// commande complete
	$commandfull = array_merge($v6PreAmble, $v6PreData, $command, array($crc));
	$trame = vsprintf (str_repeat('%c', count($commandfull)), $commandfull);	
	
	for ($i = 0; $i < $retrynb; $i++)
	{
	    echo "<trame>".bin2hex($trame)."</trame>\r\n";;	
	    
	    socket_sendto($socket, $trame, strlen($trame), 0, $host, $ports);
	    $rlen = socket_recvfrom($socket, $buffer, 1024, 0, $host, $portr);
		echo "<receivelen>".$rlen."</receivelen>\r\n";	
		echo "<receiveddump>".bin2hex($buffer)."</receiveddump>\r\n";		

		$output = array_merge(unpack('C*', $buffer));
		sdk_sleepms(100);
		if (count($output) > 0 ) 
			break;
	}	
	echo "</send>\r\n";

}


function sdk_sleepms($ms)
{
	$ms = abs($ms);
	if( $ms > 5000) $ms = 5000;
	for ($i = 0; $i < $ms ; $i++)
	{
		usleep(1000); //wait 1ms 
	}
}

function sdk_rgbToHsl($r, $g, $b)
{
	echo "<rgbToHsl>";
	echo "<rgb>".$r.",".$g.",".$b."</rgb>";
	$r = $r / 255;
	$g = $g / 255;
	$b = $b / 255;
	$max = max($r, $g, $b);
	$min = min($r, $g, $b);
	$l = ($max + $min) / 2;
	$d = $max - $min;
	$h = '';
	if ($d == 0) {
		$h = $s = 0;
	} else {
		$s = $d / (1 - abs(2 * $l - 1));
		switch ($max) {
			case $r:
				$h = 60 * fmod((($g - $b) / $d), 6);
				if ($b > $g) {
					$h += 360;
				}
				break;
			case $g:
				$h = 60 * (($b - $r) / $d + 2);
				break;
			case $b:
				$h = 60 * (($r - $g) / $d + 4);
				break;
		}
	}
	echo "<hsl>".$h.",".$s.",".$l."</hsl>";
	echo "</rgbToHsl>\r\n";
	return array($h, $s, $l);
}
?>