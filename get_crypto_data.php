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

?>