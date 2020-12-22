<?php

function recoleccion(Array $data)
    {   
        $curl = curl_init();

        $data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => SIENVIO_API_BASE_URL . '/recoleccion',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
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

		/*
			No parece haber solución más sencilla que des-habilitar chequeo de SSL
			
			https://cheapsslsecurity.com/blog/ssl-certificate-problem-unable-to-get-local-issuer-certificate/
		*/
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	
		/*
			Generar excepcion si algo sale mal
		*/
        //curl_setopt($curl, CURLOPT_FAILONERROR, true);
	
		/*
			TIMEOUT
		*/
	
		// Tell cURL that it should only spend X seconds
		// trying to connect to the URL in question.
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);

		// A given cURL operation should only take
		// X seconds max.
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($curl);

        //echo $response;
        
        $err_nro = curl_errno($curl);
        $err_msg = curl_error($curl);	
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($err_nro) {
			throw new \Exception("$err_msg ($http_code)");
		}

		if ($http_code >= 300){
			throw new \Exception("Unexpected http code ($http_code)");
		}

        curl_close($curl);     
	
		return $response;
    }


