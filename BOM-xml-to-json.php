<?php
	
	// download xml file if not already downloaded
	if (!file_exists('precis_weather_forecast_NSW.xml')) {
		file_put_contents("precis_weather_forecast_NSW.xml", fopen("ftp://ftp.bom.gov.au/anon/gen/fwo/IDN11060.xml", 'r'));
	}
	
	$xml = simplexml_load_file("precis_weather_forecast_NSW.xml");
	
	$regions = array();
	$publicDistricts = array();
	$locales = array();
	$forecasts = array();
	
	// loop through all the areas
	foreach($xml->forecast->area as $area) {
		
		//print_r($area['description']);
		if ((string) $area['type'] == 'region') {
			// store the region
			$region = (object)[
				'name' => (string)$area['description']
			];
			array_push($regions, $region);
		}
		
		if ((string) $area['type'] == 'public-district') {
			// store the public-district with the region
			$publicDistrict = (object) [
				'name' => (string)$area['description'],
				'region' => end($regions)
			];
			array_push($publicDistricts, $publicDistrict);
		}
		
		if ((string) $area['type'] == 'location') {
			$locale = (object) [
				'name' => (string)$area['description'],
				'publicDistrict' => end($publicDistricts)
			];
			// store the location
			array_push($locales, $locale);
			
			// each location loop through each forecast-
			foreach($area->{'forecast-period'} as $forecast) {
				// create the forecast
				$forecast = (object) [
					'locale' => end($locale),
					'startDate' => (string)$forecast['start-time-local'],
					'endDate' => (string)$forecast['end-time-local'],
					'iconCode' => (string) current($forecast->xpath('element[@type="forecast_icon_code"]')),
					'minTemp' => (string) current($forecast->xpath('element[@type="air_temperature_minimum"]')),
					'maxTemp' => (string) current($forecast->xpath('element[@type="air_temperature_maximum"]')),
					'precis' => (string) current($forecast->xpath('text[@type="precis"]')),
					'precipitation' => (string) current($forecast->xpath('text[@type="probability_of_precipitation"]'))
				];
				// store the forecast
				array_push($forecasts, $forecast);
			}
		
		}
		
	}
	
	// compile each object into an array for json response
	$response = (object) [
		'regions' => $regions,
		'publicDistricts' => $publicDistricts,
		'locales' => $locales,
		'forecasts' => $forecasts
	];
	
	// create json file
	$fp = fopen('weather_forecast.json', 'w');
	fwrite($fp, json_encode($response));
	fclose($fp);
	
?>