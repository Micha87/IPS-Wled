<?php
	class WLed extends IPSModule{

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
			$this->createVariablenProfiles();
			$this->RegisterPropertyString('Topic', "");
        
        //Variablen anlegen
			$this->RegisterVariableInteger("Master_Brightness",$this->Translate("Master Brightness"), "~Intensity.255",11);
			$this->EnableAction('Master_Brightness');
			$this->RegisterVariableInteger("Primary_Color", $this->Translate("Primary Color"), "~HexColor",12);
			$this->EnableAction('Primary_Color');
			$this->RegisterVariableInteger("Secondary_Color", $this->Translate("Secondary color"), "~HexColor",12);
			$this->EnableAction('Secondary_Color');
			//$this->RegisterVariableInteger("Third_Color", $this->Translate("Third Color"), "~HexColor",12);
			//$this->EnableAction('Third_Color');
        	//$this->RegisterVariableInteger("Primary_Color_red", "Primary Color red", "");
        	//$this->RegisterVariableInteger("Primary_Color_green", "Primary Color green", "");
        	//$this->RegisterVariableInteger("Primary_Color_blue", "Primary Color blue", "");
			$this->RegisterVariableBoolean("Nightlight_active",$this->Translate("Nightlight active"),'~Switch',20);
			$this->EnableAction('Nightlight_active');	
       			$this->RegisterVariableBoolean("Nightlight_Fade_type", $this->Translate("Nightlight Fade type"), "~Switch",22);
			$this->RegisterVariableInteger("Nightlight_delay", $this->Translate("Nightlight delay"), "",21);
			$this->EnableAction('Nightlight_delay');	
			$this->RegisterVariableInteger("Nightlight_target_brightness",$this->Translate("Nightlight target brightness"), "",23);
			$this->EnableAction('Nightlight_target_brightness');
			$this->RegisterVariableInteger("Effect_index", $this->Translate("Effect index"), "Wled.Effects",30);
			$this->EnableAction('Effect_index');
			$this->RegisterVariableInteger("Effect_speed", $this->Translate("Effect speed"), "~Intensity.255",31);
			$this->EnableAction('Effect_speed');
			$this->RegisterVariableInteger("Effect_intensity", $this->Translate("Effect intensity"), "~Intensity.255",32);
			$this->EnableAction('Effect_intensity');
        		$this->RegisterVariableInteger("FastLED_palette", "FastLED palette", "Wled.FastLED_palette",50);
			$this->EnableAction('FastLED_palette');
        		$this->RegisterVariableBoolean("RGB_HSB", "RGB_HSB UI mode","",51);
			$this->RegisterVariableString("Server_description", "Server description","",52);
			$this->RegisterVariableBoolean('Wled_State', 'State', '~Switch',10);
			$this->EnableAction('Wled_State');
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			#		Filter setzen
			//$this->SetReceiveDataFilter(".*\"Topic\":\"".$this->ReadPropertyString("Topic")."/.*");
			
			
			$MQTTTopic = $this->ReadPropertyString('Topic');
        		$this->SetReceiveDataFilter('.*' . $MQTTTopic . '.*');
		#		Status holen
				//if($this->HasActiveParent())$this->Status();
		
		}

		
		public function ReceiveData($JSONString)
    	{
       	 $this->SendDebug('JSON', $JSONString, 0);
        	if (!empty($this->ReadPropertyString('Topic'))) {
            	$Buffer = json_decode($JSONString);
           	 	// Buffer decodieren und in eine Variable schreiben
           	 	$this->SendDebug('MQTT Topic', $Buffer->Topic, 0);
          	  	$this->SendDebug('MQTT Payload', $Buffer->Payload, 0);
            	if (property_exists($Buffer, 'Topic')) {
               	 if (fnmatch('*/g', $Buffer->Topic)) 		{
						SetValue($this->GetIDForIdent('Master_Brightness'), $Buffer->Payload);
						if($Buffer->Payload == 0){
							SetValue($this->GetIDForIdent('Wled_State'),false);
						}else{
							SetValue($this->GetIDForIdent('Wled_State'),true);
						}
                 }
					  
				 if (fnmatch('*/c', $Buffer->Topic)) 		{
						//$this->SendDebug('Receive Result: Color', $Buffer->Payload, 0);
						$color=$Buffer->Payload;
						$color_trimmed = trim($color, '#');
                    				SetValue($this->GetIDForIdent('Primary_Color'), hexdec(($color_trimmed)));
					}	   

				 if (fnmatch('*/v', $Buffer->Topic)) 		{
						$this->SendDebug('Receive Result: api', $Buffer->Payload, 0);
						$api=$Buffer->Payload;
						$daten=simplexml_load_string($api);
						
						SetValue($this->GetIDForIdent('Nightlight_delay'),intval($daten->nd));
						SetValue($this->GetIDForIdent('Nightlight_target_brightness'),intval($daten->nt));
						SetValue($this->GetIDForIdent('Effect_index'),intval($daten->fx));
						SetValue($this->GetIDForIdent('Effect_speed'),intval($daten->sx));
						SetValue($this->GetIDForIdent('Effect_intensity'),intval($daten->ix));
						SetValue($this->GetIDForIdent('FastLED_palette'),intval($daten->fp));											
						SetValue($this->GetIDForIdent('Server_description'),"$daten->ds");
						
						if ($daten->nl == "1") {
							SetValue($this->GetIDForIdent('Nightlight_active'), true);
						} else {
							SetValue($this->GetIDForIdent('Nightlight_active'), false);
						}

						if ($daten->nf == "1") {
							SetValue($this->GetIDForIdent('Nightlight_Fade_type'), true);
						} else {
							SetValue($this->GetIDForIdent('Nightlight_Fade_type'), false);
						}

						if ($daten->md == "1") {
							SetValue($this->GetIDForIdent('RGB_HSB'),true);
						} else {
							SetValue($this->GetIDForIdent('RGB_HSB'),false);
						}
						
					 
					 	$Col_Sec=RGB2Hex(255,0,255);
					 	$color_Sec_trimmed = trim($Col_Sec, '#');
                    				SetValue($this->GetIDForIdent('Secondary_Color'), hexdec(($color_Sec_trimmed)));
					 
					}	   

           		}
			}

		}	
		
		punlic function RGB2Hex($R,$G,$B){
 
 			 $R=dechex($R);
			 If (strlen($R)<2)
			 $R='0'.$R;

			  $G=dechex($G);
			 If (strlen($G)<2)
			 $G='0'.$G;

			 $B=dechex($B);
			 If (strlen($B)<2)
			 $B='0'.$B;
 
 			 return '#' . $R . $G . $B;
			}	
		
		
		private function setBrightness(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&A='."$msg");
		}
		private function setState(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&T='."$msg");
			SetValue($this->GetIDForIdent('Wled_State'),$value);
		}

		private function SetColor(int $value)
		{
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/col',"$msg");
			SetValue($this->GetIDForIdent('Primary_Color'),$value);
		}
		private function Effect_speed(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&SX='."$msg");
		}
		private function Effect_intensity(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&IX='."$msg");
		}
		private function Effect_index(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&FX='."$msg");
		}
		private function Nightlight_active(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&ND');
			SetValue($this->GetIDForIdent('Nightlight_active'),$value);
		}
		private function Nightlight_delay(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&NL='."$msg");
			SetValue($this->GetIDForIdent('Nightlight_delay'),$value);
		}
		private function FastLED_palette(int $value)
		{			
			$msg = strval($value);
			$this->sendMQTT($this->ReadPropertyString('Topic').'/api', '&FP='."$msg");
			SetValue($this->GetIDForIdent('FastLED_palette'),$value);
		}


		
		public function RequestAction($Ident, $Value)
		{
			switch ($Ident) {
				case 'Wled_State':
					$this->setState($Value);
					break;
				case 'Master_Brightness':
					$this->setBrightness($Value);
					break;				
				case 'Primary_Color':
					$this->SetColor($Value);
					break;
				case 'Effect_speed':
					$this->Effect_speed($Value);
					break;
				case 'Effect_intensity':
					$this->Effect_intensity($Value);
					break;
				case 'Effect_index':
					$this->Effect_index($Value);
					break;	
				case 'Nightlight_active':
					$this->Nightlight_active($Value);
					break;	
				case 'Nightlight_delay':
					$this->Nightlight_delay($Value);
					break;
				case 'FastLED_palette':
					$this->FastLED_palette($Value);
					break;	
				}
		}
		protected function sendMQTT($Topic, $Payload)
    {
        $resultServer = true;
        $resultClient = true;
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = $Topic;
        $Server['Payload'] = $Payload;
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . 'MQTT Server', $ServerJSON, 0);
        $resultServer = @$this->SendDataToParent($ServerJSON);

	}
	
	private function RGBtoHex($R, $G, $B){
 
 			 $R=dechex($R);
			 If (strlen($R)<2)
			 $R='0'.$R;

			  $G=dechex($G);
			 If (strlen($G)<2)
			 $G='0'.$G;

			 $B=dechex($B);
			 If (strlen($B)<2)
			 $B='0'.$B;
 
 			 return '#' . $R . $G . $B;
			}	
		
		
	private function createVariablenProfiles()
    {
        if (!IPS_VariableProfileExists('Wled.Effects')) {
            IPS_CreateVariableProfile('Wled.Effects', 1);
        }
        IPS_SetVariableProfileDigits('Wled.Effects', 0);
        IPS_SetVariableProfileText('Wled.Effects', '', '');
	IPS_SetVariableProfileAssociation('Wled.Effects', 0, "Solid", "", -1);
 	IPS_SetVariableProfileAssociation('Wled.Effects',0,"Solid", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',1,"Blink", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',2,"Breathe", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',3,"Wipe", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',4,"Wipe Random", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',5,"Random Colors", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',6,"Sweep", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',7,"Dynamic", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',8,"Colorloop", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',9,"Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',10,"Scan", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',11,"Dual Scan", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',12,"Fade", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',13,"Theater", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',14,"Theater Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',15,"Running", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',16,"Saw", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',17,"Twinkle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',18,"Dissolve", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',19,"Dissolve Rnd", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',20,"Sparkle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',21,"Dark Sparkle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',22,"Sparkle+", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',23,"Strobe", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',24,"Strobe Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',25,"Mega Strobe", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',26,"Blink Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',27,"Android", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',28,"Chase", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',29,"Chase Random", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',30,"Chase Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',31,"Chase Flash", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',32,"Chase Flash Rnd", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',33,"Rainbow Runner", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',34,"Colorful", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',35,"Traffic Light", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',36,"Sweep Random", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',37,"Running 2", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',38,"Red & Blue", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',39,"Stream", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',40,"Scanner", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',41,"Lighthouse", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',42,"Fireworks", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',43,"Rain", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',44,"Merry Christmas", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',45,"Fire Flicker", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',46,"Gradient", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',47,"Loading", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',48,"Police", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',49,"Police All", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',50,"Two Dots", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',51,"Two Areas", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',52,"Circus", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',53,"Halloween", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',54,"Tri Chase", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',55,"Tri Wipe", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',56,"Tri Fade", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',57,"Lightning", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',58,"ICU", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',59,"Multi Comet", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',60,"Dual Scanner", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',61,"Stream 2", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',62,"Oscillate", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',63,"Pride 2015", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',64,"Juggle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',65,"Palette", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',66,"Fire 2012", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',67,"Colorwaves", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',68,"BPM", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',69,"Fill Noise", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',70,"Noise 1", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',71,"Noise 2", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',72,"Noise 3", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',73,"Noise 4", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',74,"Colortwinkle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',75,"Lake", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',76,"Meteor", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',77,"Smooth Meteor", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',78,"Railway", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',79,"Ripple", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',80,"Twinklefox", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',81,"Twinklecat", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',82,"Halloween Eyes", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',83,"Solid Pattern", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',84,"Solid Pattern Tri", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',85,"Spots", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',86,"Spots Fade", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',87,"Glitter", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',88,"Candle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',89,"Fireworks Starburst", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',90,"Fireworks 1D", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',91,"Bouncing Balls", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',92,"Sinelon", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',93,"Sinelon Dual", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',94,"Sinelon Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',95,"Popcorn", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',96,"Drip", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',97,"Plasma", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',98,"Percent", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',99,"Ripple Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',100,"Heartbeat", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',101,"Pacifica", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',102,"Candle Multi", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',103,"Solid Glitter", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',104,"Sunrise", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',105,"Phased", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',106,"Twinkle Up", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',107,"Noise Pal", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',108,"Sinewave", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',109,"Phased Noise", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',110,"Flow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',111,"Chunchun", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',112,"Dancing Shadows", "", -1);
	IPS_SetVariableProfileAssociation('Wled.Effects',113,"Washing machine", "", -1);

	if (!IPS_VariableProfileExists('Wled.FastLED_palette')) {
            IPS_CreateVariableProfile('Wled.FastLED_palette', 1);
        }
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',0,"Default", "", -1);	
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',1,"Random Cycle", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',2,"Primary color", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',3,"Based on primary", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',4,"Set colors", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',5,"Based on set", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',6,"Party", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',7,"Cloud", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',8,"Lava", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',9,"Ocean", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',10,"Forest", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',11,"Rainbow", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',12,"Rainbow bands", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',13,"Sunset", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',14,"Rivendell", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',15,"Breeze", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',16,"Red & Blue", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',17,"Yellowout", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',18,"Analoguous", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',19,"Splash", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',20,"Pastel", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',21,"Sunset 2", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',22,"Beech", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',23,"Vintage", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',24,"Departure", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',25,"Landscape", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',26,"Beach", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',27,"Sherbet", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',28,"Hult", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',29,"Hult 64", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',30,"Drywet", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',31,"Jul", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',32,"Grintage", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',33,"Rewhi", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',34,"Tertiary", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',35,"Fire", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',36,"Icefire", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',37,"Cyane", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',38,"Light Pink", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',39,"Autumn", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',40,"Magenta", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',41,"Magred", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',42,"Yelmag", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',43,"Yelblu", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',44,"Orange & Teal", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',45,"Tiamat", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',46,"April Night", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',47,"Orangery", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',48,"C9", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',49,"Sakura", "", -1);
	IPS_SetVariableProfileAssociation('Wled.FastLED_palette',50,"Aurora", "", -1);
	
    }
		
		
}
