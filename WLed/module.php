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
        		$this->RegisterVariableInteger("FastLED_palette", "FastLED palette", "",50);
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
					
					}	   

           		}
			}

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
	
	private function createVariablenProfiles()
    {
        if (!IPS_VariableProfileExists('Wled.Effects')) {
            IPS_CreateVariableProfile('Wled.Effects', 1);
        }
        IPS_SetVariableProfileDigits('Wled.Effects', 0);
        IPS_SetVariableProfileText('Wled.Effects', '', '');
	IPS_SetVariableProfileAssociation("Wled.Effects", 0, "Solid", "", "", -1);
 
    }
		
		
}
