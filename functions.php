<?php
function stationparse($frame) //function for parsing station information
{
	global $receivedstations;
	global $staticstations;
	global $movingstations;
	global $otherstations;
	global $viastations; //stations received via digi
	global $directstations; //stations received directly
	global $callraw;
	global $time;
	global $cntalias;
	$fg = 0;
	if(strpos($frame, $callraw." R")) //if frame received by RF
	{
		$uu = substr($frame, 0, 19);  //take only the part of the line, where date and time is
		$uu = strtotime($uu) + date('Z'); //convert string with date and time to the Unix timestamp and add a timezone shift
		if($uu > $time) //if frame was received in out time range
		{
			$aa = explode(">", $frame); //divide frame from > separator to get station's callsign
			$stationcall = substr($aa[0], strpos($aa[0], $callraw." R ") + strlen($callraw." R ")); //remove date and time, interface call up to the received station's call, so that we get only station's call
			
			if(array_key_exists($stationcall, $receivedstations)) //if this callsign is already on stations list
			{
				$receivedstations[$stationcall]++; //increment the number of frames from this station
			} else //if this callsign is not on the list
			{
				$receivedstations[$stationcall] = 1; //add callsign to the list
			}
			
			$bb = substr($frame, 46); //let's cut temporarily some part of a frame to make sure, that there is no : character, because we want it only as a separator between frame path and info field
			//------DEBUG-----^^^^^^ this can make some problems, beacuse it's very primitive
			$bb = substr($bb, strpos($bb, ":") + 1); //get whole date from the frame after a : character, to get info field
			if(($bb[0] === "@") or ($bb[0] === "!") or ($bb[0] === "=") or ($bb[0] === "/") or (ord($bb[0]) === 96) or (ord($bb[0]) === 39)) //if it's a frame with position or Mic-E position			
			{
				if($bb[7] === 'z') //if the positions contains timestamp shift reading symbol data by 7 characters
				{
					$fg = 26;
				} else 
				{
					$fg = 19;
				}
				if((ord($bb[0]) === 96) or (ord($bb[0]) === 39)) //special case - if Mic-E postion
				{
					$bb = str_replace("<0x1c>", chr(28), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x1d>", chr(29), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x1e>", chr(30), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x1f>", chr(31), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x7f>", chr(127), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$fg = 7; //set symbol place to 7
				}
					if(in_array($bb[$fg], array('!', '#', '%', '&', '+', ',', '-', '.', '/', ':', ';', '?', '@', 'A', 'B', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'T', 'V', 'W', 'Z', '\\', ']', '_', '`', 'c', 'd', 'h', 'i', 'l', 'm', 'n', 'o', 'q', 'r', 't', 'w', 'x', 'y', 'z', '}')))
					{
						if(!in_array($stationcall, $staticstations))
						{
							$staticstations[] = $stationcall;
						}
					}	
					elseif(in_array($bb[$fg], array('$', '\'', '(', ')', '*', '<', '=', '>', 'C', 'F', 'P', 'R', 'U', 'X', 'Y', '[', '^', 'a', 'b', 'f', 'g', 'j', 'k', 'p', 's', 'u', 'v')))
					{
						if(!in_array($stationcall, $movingstations))
						{
							$movingstations[] = $stationcall;
						}
					}
					else
					{
						if(!in_array($stationcall, $otherstations))
						{
							$otherstations[] = $stationcall;
						}
					}	
			}
			
			$cc = substr($frame, strpos($frame, ">")); //temporarily get everything after > symbol (after received station callsign)
			$cc = substr($cc, 0, strpos($cc, ":")); //and then everything before info field separator, so that we have only frame path right now
			if(strpos($cc, '*') !== false) //if there is a * the frame was definitely not heard directly
			{
				if(!in_array($stationcall, $viastations))
				{
					$viastations[] = $stationcall;
				}
			} else //if there is no *
			{
				if($cntalias == "") //if no national alias selected, take frame as not direct
				{
						if(!in_array($stationcall, $viastations))
						{
							$viastations[] = $stationcall;
						}	
						return;	
				}
				$cntpos = strpos($cc, $cntalias);
				if((strpos($cc, $cntalias) !== false) and ($cc[$cntpos + 3] == "-")) //if there is national untraced alias without *, the frame still can be heard indirectly
				{
					if($cc[$cntpos + 2] == $cc[$cntpos + 4]) //if this path element has n=N, for example SP2-2, it was heard directly
					{
						if(!in_array($stationcall, $directstations))
						{
							$directstations[] = $stationcall;
						}
					} else //else if n!=N, for example SP2-1, the frame was PROBABLY heard via digi
					{
						if(!in_array($stationcall, $viastations))
						{
							$viastations[] = $stationcall;
						}
					}
				} else //if there is no national alias, it was heard directly
				{
					if(!in_array($stationcall, $directstations))
					{
						$directstations[] = $stationcall;
					}
				}
			}
		}
	}
	
}

function load($frame, $end)
{
	global $framespermin;
	global $time1;
	global $time2;
	if($end === 0)
	{
		$time1 = substr($frame, 0, 19);
		$time1 = strtotime($time1);
	} elseif($end === 1)
	{
		$time2 = substr($frame, 0, 19);
		$time2 = strtotime($time2);
		$framespermin = 20 / (($time2 - $time1) / 60);
	}
	
}

function nmeatodec($data, $shift)
{
	$dec = 0;
	$dec += ($data[$shift] * 10);
	$dec += $data[1 + $shift];

	$temp = 0;

	$temp += ($data[2 + $shift] * 10);
	$temp += $data[3 + $shift];

	$temp += ($data[5 + $shift] / 10);
	$temp += ($data[6 + $shift] / 100);

	$temp /= 60;

	$dec += $temp;

	return $dec;
}

function mice_decode($dest, $info)
{
   //conversion of Mic-E posistion to DDMMmm format
	global $declat;
	global $declon;
	$declat = 0;
	$declon = 0;
	$ghf = ord($dest[0]);

    if($ghf <= 57) $declat += ($ghf - 48) * 100000;
    else if(($ghf >= 65) && ($ghf <= 74)) $declat += ($ghf - 65) * 100000;
    else if(($ghf >= 80) && ($ghf <= 89)) $declat += ($ghf - 80) * 100000;

    $ghf = ord($dest[1]);
    if($ghf <= 57) $declat += ($ghf - 48) * 10000;
    else if(($ghf >= 65) && ($ghf <= 74)) $declat += ($ghf - 65) * 10000;
    else if(($ghf >= 80) && ($ghf <= 89)) $declat += ($ghf - 80) * 10000;

    $ghf = ord($dest[2]);
    if($ghf <= 57) $declat += ($ghf - 48) * 1000;
    else if(($ghf >= 65) && ($ghf <= 74)) $declat += ($ghf - 65) * 1000;
    else if(($ghf >= 80) && ($ghf <= 89)) $declat += ($ghf - 80) * 1000;

    $ghf = ord($dest[3]);
    if($ghf <= 57)
    {
        $declat += ($ghf - 48) * 100;
        $declat = $declat * (-1);
    }
    else if(($ghf >= 80) && ($ghf <= 89))
    {
        $declat += ($ghf - 80) * 100;
    }

    $looff = 0;

    $ghf = ord($dest[4]);
    if($ghf <= 57)
    {
        $declat += ($ghf - 48) * 10;
        $looff = 0;
    }
    else if(($ghf >= 80) && ($ghf <= 89))
    {
        $declat += ($ghf - 80) * 10;
        $looff = 100;
    }

    $lonneg = 0;

    $ghf = ord($dest[5]);
    if($ghf <= 57)
    {
        $declat += $ghf - 48;
        $lonneg = 1;
    }
    else if(($ghf >= 80) && ($ghf <= 89))
    {
        $declat += $ghf - 80;
        $lonneg = -1;
    }

    $ghf = ord($info[1]);
    $ghf -= 28;
    $ghf += $looff;
    if(($ghf <= 189) && ($ghf >= 180)) $ghf -= 80;
    else if(($ghf <= 199) && ($ghf >= 190)) $ghf -= 190;

    $declon += ($ghf * 10000);

    $ghf = ord($info[2]);
    $ghf -= 28;
    if($ghf >= 60) $ghf -= 60;

    $declon += ($ghf * 100);

    $ghf = ord($info[3]);
    $ghf -= 28;
    $declon += $ghf;

	
    $declon = $declon * $lonneg;
	

	//converting DDMMmm to DDdddddd
	//latitude
	$tt = 0;
	$tt += (int)($declat / 10000);
	
	$temp = ($declat % 10000) / 100;
	$temp /= 60;
	
	$declat = $tt + $temp;
	
	
	//longtitude
	$tt = 0;
	$tt += (int)($declon / 10000);
	
	
	$temp = ($declon % 10000) / 100;
	$temp /= 60;
	
	$declon = $tt + $temp;

}


function frameparse($frame)
{
	global $callraw;
	global $scall;
	global $posframefound;
	global $otherframefound;
	global $posframe;
	global $otherframe;
	global $lastpath;
	global $noofframes;
	global $symbol;
	global $symboltab;
	global $stationlat;
	global $stationlon;
	global $distance;
	global $declat;
	global $declon;
	global $posdate;
	global $postime;
	global $comment;
	global $status;
	global $otherdate;
	global $othertime;
	global $mice;
	global $bearing;

		$packet = substr($frame, 36); //get only frame, without interface call, date etc.
		$aa = explode(">", $packet); //get the callsign
		
		if($aa[0] == $scall)
		{
			
			$noofframes++;
			if($posframefound and $otherframefound) return;
			$bb = explode(":", $packet); //get only info field, so everything after : separator
			
			$bb = str_replace("<0x1c>", chr(28), $bb); //replace unprintable characters written as <0xAA> with it's real value
			$bb = str_replace("<0x1d>", chr(29), $bb); //replace unprintable characters written as <0xAA> with it's real value
			$bb = str_replace("<0x1e>", chr(30), $bb); //replace unprintable characters written as <0xAA> with it's real value
			$bb = str_replace("<0x1f>", chr(31), $bb); //replace unprintable characters written as <0xAA> with it's real value
			$bb = str_replace("<0x7f>", chr(127), $bb); //replace unprintable characters written as <0xAA> with it's real value
			
			$dd = substr($bb[1], 0); //i have no idea, but i must do this, because without this there are some problems
							
			if(($dd[0] === "@") or ($dd[0] === "!") or ($dd[0] === "=") or ($dd[0] === "/") or (ord($dd[0]) === 96) or (ord($dd[0]) === 39)) //if it's a frame with position or Mic-E position	
			{
				
				if($posframefound) return; //if we have already position frame parsed, just skip it
			
				
			
				$posframe = $packet; //save whole posistion frame
				
				$posdate = substr($frame, 0, 10); //extract date
				$postime = substr($frame, 11, 8); //extract time
				
				$path = explode(">", $bb[0]); //take everything after station callsign and before info field (see bb[0])
				$lastpath = $path[1]; //take only path part
				
				$posframefound = 1; //newest position frame found
				
				if((ord($dd[0]) === 96) or (ord($dd[0]) === 39)) //if it's a Mic-E frame
				{
					$mice = 1;
					
					$symboltab = $dd[8];
					$symbol = $dd[7];
					
					$destaddr = explode(",", $aa[1]); //get destination address, which encodes latitude
					
					mice_decode($destaddr[0], $bb[1]);
					
					$comment = substr($bb[1], 9);
					
				}
				else //if it's a standard frame
				{
					$mice = 0;
					if($dd[7] === 'z') //if the positions contains timestamp
					{
						$symboltab = $dd[16];
						$symbol = $dd[26];
						$comment = substr($dd, 27);
						
						$shft = 7;

					} else 
					{
						$symboltab = $dd[9];
						$symbol = $dd[19];
						$comment = substr($dd, 20);
						$shft = 0;
					}	

					//convert NMEA to decimal degrees
					$declat = nmeatodec($dd, 1 + $shft);
					if($dd[8 + $shft] == 'S') $declat *= -1;
					$declon = nmeatodec($dd, 11 + $shft);
					if($dd[18 + $shft] == 'W') $declon *= -1;
						

				}
				
				//haversine formula for distance calculation	
				$latFrom = deg2rad($stationlat);
				$lonFrom = deg2rad($stationlon);
				$latTo = deg2rad($declat);
				$lonTo = deg2rad($declon);

				$latDelta = $latTo - $latFrom;
				$lonDelta = $lonTo - $lonFrom;
				
				$bearing = rad2deg(atan2(sin($lonDelta)*cos($latTo), cos($latFrom)*sin($latTo)-sin($latFrom)*cos($latTo)*cos($latDelta)));
				if($bearing < 0) $bearing += 360;

				$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
				cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
				$distance = round($angle * 6371, 2); //gives result in km rounded to 2 digits after comma
				
				$declat = round($declat, 5);
				$declon = round($declon, 5);
				$bearing = round($bearing, 1);
									
			}
			else if(($dd[0] === ">") or ($dd[0] === "<") or ($dd[0] === "{")) //if it's a status or beacon frame
			{
				if($otherframefound) return; //if we have already status frame parsed, just skip it
			
				$otherframe = $packet; //save whole status frame
				
				$status = substr($dd, 1);
				
				$otherdate = substr($frame, 0, 10); //extract date
				$othertime = substr($frame, 11, 8); //extract time
				
				$otherframefound = 1; //newest beacon frame found
			}
		}

}

//function for load calc

function rxload() {
global $logfile;
global $callraw;
global $lines;
global $rxframespermin;

$count=0;
$index1=1;
//find the time of last rx packet in log
while (($index1<$lines)AND(!((strpos($logfile[$lines - $index1],$callraw." R"))OR(strpos($logfile[$lines - $index1],$callraw." d"))))) {
        $index1++;
        }
$time1 = strtotime(substr($logfile[$lines - $index1], 0, 19));
$index2=$index1+1;

//go back to last-20  received packets and take time
while (($index2<$lines)AND($count<19)) {
        if((strpos($logfile[$lines - $index2],$callraw." R"))OR(strpos($logfile[$lines - $index2],$callraw." d"))) {
                $time2 = strtotime(substr($logfile[$lines - $index2], 0, 19));
                $count++;
                }
        $index2++;
}
$rxframespermin = $count / (($time1 - $time2) / 60);
//echo $count."<br>";//debug line
//echo $index1."<br>";//debug line
//echo $index2."<br>";//debug line
return(rxframespermin);
}

//maybe it's possible to merge these two functions...

function txload() {
global $logfile;
global $callraw;
global $lines;
global $txframespermin;

$count=0;
$index1=1;
//find the time of last tx packet in log
while (($index1<$lines)AND(!(strpos($logfile[$lines - $index1],$callraw." T")))) {
        $index1++;
        }
$time1 = strtotime(substr($logfile[$lines - $index1], 0, 19));

$index2=$index1+1;

//go back to last-20  tx packets and take time
while (($index2<$lines)AND($count<19)) {
        if(strpos($logfile[$lines - $index2],$callraw." T")) {
                $time2 = strtotime(substr($logfile[$lines - $index2], 0, 19));
                $count++;
                }
        $index2++;
}
$txframespermin = $count / (($time1 - $time2) / 60);
//echo $count."<br>"; // debug line
//echo $index1."<br>"; //debug line
//echo $index2."<br>"; //debug line
return(txframespermin);
}

?>
