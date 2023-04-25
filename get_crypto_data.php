<?php

function save_data_to_file($data, $filename) {
    file_put_contents($filename, json_encode($data));
}

function load_data_from_file($filename) {
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }

    return null;
}

function get_crypto_historical_data($coin_id, $interval, $count) {
    $interval_minutes = intval($interval);
    $total_minutes_needed = $count * $interval_minutes;

    // Determinar o número de pedaços de 12 horas necessários para coletar os dados
    $total_half_days_needed = ceil($total_minutes_needed / (12 * 60));

    echo "total: " . $total_half_days_needed . "<br>";

    //$interval_minutes = intval($interval);
    //$total_minutes_needed = $count * $interval_minutes;
    //$days_needed = ceil($total_minutes_needed / (24 * 60));
    $api_intervals = array();

    for ($i = $total_half_days_needed; $i >= 1; $i--) {
        // Determinar a data e hora de início e fim para o pedaço atual de 12 horas
        $start_datetime = new DateTime('now', new DateTimeZone('UTC'));
        $start_datetime->modify('-' . ($i * 12) . ' hours');
        $start_timestamp = $start_datetime->getTimestamp();
    
        $end_datetime = new DateTime('now', new DateTimeZone('UTC'));
        $end_datetime->modify('-' . (($i - 1) * 12) . ' hours');
        $end_timestamp = $end_datetime->getTimestamp();
    
        // Subtrair 240 segundos (4 minutos) do from_timestamp
        $start_timestamp -= 240;
    
        $api_intervals[] = array(
            'from' => $start_timestamp,
            'to' => $end_timestamp
        );
    }

    $historical_data = array('prices' => array(), 'market_caps' => array(), 'total_volumes' => array());

    foreach ($api_intervals as $interval) {
        $url = "https://api.coingecko.com/api/v3/coins/{$coin_id}/market_chart/range?vs_currency=usd&from={$interval['from']}&to={$interval['to']}";
        $response = file_get_contents($url);

        if ($response === false) {
            sleep(5); // Adicionar atraso de 1 segundo entre as solicitações
            $response = file_get_contents($url);
        }

        $response_data = json_decode($response, true);

        if (isset($response_data['prices']) && isset($response_data['market_caps']) && isset($response_data['total_volumes'])) {
            $historical_data['prices'] = array_merge($historical_data['prices'], $response_data['prices']);
            $historical_data['market_caps'] = array_merge($historical_data['market_caps'], $response_data['market_caps']);
            $historical_data['total_volumes'] = array_merge($historical_data['total_volumes'], $response_data['total_volumes']);
        }
    }

    $filtered_data = array('prices' => array(), 'market_caps' => array(), 'total_volumes' => array());
    $data_count = 0;
    $filter_index = floor($interval_minutes / 5);
    
    if ($filter_index < 1) {
        $filter_index = 1;
    }

    echo "historical_data: " . count($historical_data['prices']) . "<br>";
    //echo "<pre>"; print_r($historical_data); echo "</data>";

    for ($i = 0; $i < count($historical_data['prices']); $i++) {
        if ($i % $filter_index == 0) {
            $filtered_data['prices'][] = $historical_data['prices'][$i];
            $filtered_data['market_caps'][] = $historical_data['market_caps'][$i];
            $filtered_data['total_volumes'][] = $historical_data['total_volumes'][$i];
            $data_count++;

            if ($data_count >= $count) {
                break;
            }
        }
    }

    echo "filtered_data: " . count($filtered_data['prices']) . "<br>";

    return $filtered_data;
}





/*
function get_crypto_historical_data($coin_id, $interval, $count) {
    // URL base da CoinGecko API gratuita
    $base_url = "https://api.coingecko.com/api/v3/";

    // Configuração das opções do cURL
    $curl_options = array(
        CURLOPT_RETURNTRANSFER => 1,
    );

    // Inicia o cURL e configura as opções
    $curl = curl_init();
    curl_setopt_array($curl, $curl_options);

    $historical_data = array('prices' => array(), 'market_caps' => array(), 'total_volumes' => array());

    while ($count > 0) {
        $days = min($count, 90); // Limita a quantidade de registros por solicitação a 90
        $count -= $days;

        // Endpoint de dados históricos da criptomoeda
        $historical_data_endpoint = "coins/{$coin_id}/market_chart?vs_currency=usd&interval={$interval}&days={$days}";

        // Coleta os dados históricos
        curl_setopt($curl, CURLOPT_URL, $base_url . $historical_data_endpoint);
        $response = curl_exec($curl);

        if ($response === false) {
            die('Erro ao coletar dados históricos: ' . curl_error($curl));
        }

        // Decodifica os dados e combina os resultados
        $partial_data = json_decode($response, true);
        $historical_data['prices'] = array_merge($historical_data['prices'], $partial_data['prices']);
        $historical_data['market_caps'] = array_merge($historical_data['market_caps'], $partial_data['market_caps']);
        $historical_data['total_volumes'] = array_merge($historical_data['total_volumes'], $partial_data['total_volumes']);

        if ($count > 0) {
            sleep(1); // Adiciona uma pausa entre as solicitações para evitar atingir os limites da API
        }
    }

    // Fecha o cURL
    curl_close($curl);

    // Retorna o resultado
    return $historical_data;
}
*/
?>