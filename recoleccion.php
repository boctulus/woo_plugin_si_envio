<?php

function recoleccion(Array $data)
    {        
		/*
        $data = '
			{
				"nombre": "Juan Perez",
				"calle": "calle 58",
				"ciudad": "La Plata, Buenos Aires, Argentina",
				"notas": "Prueba completa con versión XXX",
				"boxes": [
					{
						"box_id": 4
					}
				]
			}'
		*/

        $curl = curl_init();

        $data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => SIENVIO_API_BASE_URL . '/recoleccion',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "X-API-KEY: ". API_KEY_SIENVIO,
                'Content-Type: text/plain',
                "Content-Length: ". strlen($data),
                "cache-control: no-cache"
            ),
        ));

        //curl_setopt($curl, CURLOPT_FAILONERROR, true);

        $response = curl_exec($curl);

        //echo $response;
        
        $err    = curl_error($curl);
        $err_no = curl_errno($curl);

        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        try {
			if (curl_errno($curl)) {
				$error_msg = curl_error($curl);
				throw new \Exception("$error_msg ($http_code)");
			}

			if ($http_code >= 300){
				throw new \Exception("Unexpected http code ($http_code)");
			}
		} catch (\Exception $e){
			return null;
		}

        curl_close($curl);     
	
		return $response;
    }


