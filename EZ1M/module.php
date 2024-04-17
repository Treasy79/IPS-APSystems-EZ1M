<?php

declare(strict_types=1);
	class EZ1M extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("IP", '');
			$this->RegisterPropertyInteger("Update_Output", '5');
			$this->RegisterPropertyInteger("Update_Info", '10');

			//--- Register Timer
			$this->RegisterTimer("UpdateTimerOutputData", 0, 'APSEZ_get_output_data($_IPS[\'TARGET\']);');
			$this->RegisterTimer("UpdateTimerInfo", 0, 'APSEZ_get_all_info($_IPS[\'TARGET\']);');
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

			$this->register_variables();
			$this->setstatus(102);
			if ($this->ReadPropertyString('IP') != ''){
				$this->get_device_info();
			}
		}

		public function get_device_info()
		{
			$this->call_api('getDeviceInfo', '');
		}
		
		public function get_output_data()
		{
			$this->call_api('getOutputData', '');
		}

		public function get_max_power()
		{
			$this->call_api('getMaxPower', '');
		}

		public function get_on_off_status()
		{
			$this->call_api('getOnOff', '');
		}

		public function get_alarm_info()
		{
			$this->call_api('getAlarm', '');
		}

		public function set_on_off_status(bool $value)
		{
			$this->call_api('setOnOff?status=', $value);
		}

		public function set_max_power(int $value)
		{
			$this->call_api('setMaxPower?p=', $value);
		}

		public function get_all_info()
		{
			$this->get_device_info();
			$this->get_max_power();
			$this->get_on_off_status();
			$this->get_alarm_info();
		}

		private function call_api(string $request, string $parameter)
		{
			$curl = curl_init();

			$url = 'http://'.$this->ReadPropertyString('IP').':8050/'.$request.$parameter;
			$this->SendDebug("URL", $url, 0);
			curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept:application/json','Content-Type:application/json']);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);


			$result = curl_exec($curl);   
			$this->SendDebug('Call_API', $result,0);
			//if ($HttpCode =curl_getinfo($curl, CURLINFO_HTTP_CODE)){
			//	$this->SetStatus(201);
			//	return;
			//}
            curl_close($curl);

			$ar = json_decode($result, true); 
			$this->SendDebug('Message', $ar['message'],0);
			if ($ar['message'] == 'SUCCESS'){
				$prefix = $this->derive_prefix($request);
				$this->SendDebug("PREFIX", $prefix, 0);
				$this->process_return($result, $prefix);
			}
			else
			{
				return false;
			}	
			
		}

		private function derive_prefix(string $request)
		{
			switch ($request){
				case "getDeviceInfo":
					return 'di_';
				case "getOnOff":
					return 'di_';
				case "setOnOff?status=":
					return 'di_';	
				case "getOutputData":
					return 'od_';
				case "getMaxPower":
					return 'po_';
				case "setMaxPower?p=":
					return 'po_';	
				case "getAlarm":
					return 'ai_';	
			}
		}

		private function process_return(string $result, string $prefix)
		{	
			$ar = json_decode($result, true); 

			foreach ($ar['data'] as $key => $ls_data){
				$this->SendDebug("KEY", $key, 0);
				$this->SetValue($prefix.$key , $ls_data);	
			}
		}

		private function register_variables()
		{
			// Output Data
			$this->RegisterVariableFloat("od_p1", $this->Translate('Power Channel 1'), '', 0);
			$this->RegisterVariableFloat("od_e1", $this->Translate('Energy Since Startup Channel 1'), '', 0);
			$this->RegisterVariableFloat("od_te1", $this->Translate('Energy Lifetime Channel 1'), '', 0);
			$this->RegisterVariableFloat("od_p2", $this->Translate('Power Channel 2'), '', 0);
			$this->RegisterVariableFloat("od_e2", $this->Translate('Energy Since Startup Channel 2'), '', 0);
			$this->RegisterVariableFloat("od_te2", $this->Translate('Energy Lifetime Channel 2'), '', 0);
			// Output Data (Additional calculated)
			$this->RegisterVariableFloat("od_pt", $this->Translate('Power Total'), '', 0);
			$this->RegisterVariableFloat("od_et", $this->Translate('Energy Since Startup Total'), '', 0);
			$this->RegisterVariableFloat("od_tet", $this->Translate('Energy Lifetime Total'), '', 0);

			//Device Info
			$this->RegisterVariableString("di_deviceId", $this->Translate('Device ID'), '', 0);
			$this->RegisterVariableString("di_devVer", $this->Translate('Device Version'), '', 0);
			$this->RegisterVariableString("di_ssid", $this->Translate('Connected SSID'), '', 0);
			$this->RegisterVariableString("di_ipAddr", $this->Translate('Device IP Address'), '', 0);
			$this->RegisterVariableString("di_minPower", $this->Translate('Minimum Power possible'), '', 0);
			$this->RegisterVariableString("di_maxPower", $this->Translate('Maximum Power possible'), '', 0);

			$this->RegisterVariableBoolean("di_status", $this->Translate('Status'), '', 0);

			//Max Power Output
			$this->RegisterVariableInteger("po_maxPower", $this->Translate('Maximum Output Power'), '', 0);

			//Alarm Information
			$this->RegisterVariableBoolean("ai_og", $this->Translate('Off Grid'), '', 0);
			$this->RegisterVariableBoolean("ai_isce1", $this->Translate('DC 1 Short Circuit'), '', 0);
			$this->RegisterVariableBoolean("ai_isce2", $this->Translate('DC 2 Short Circuit'), '', 0);
			$this->RegisterVariableBoolean("ai_oe", $this->Translate('Output Fault'), '', 0);
		}
	}