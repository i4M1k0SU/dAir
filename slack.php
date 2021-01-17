<?php

function sendSlack(
    string $url,
    string $seat,
    int $timestamp,
    ?float $co2,
    ?float $temperature,
    ?float $humidity,
    ?float $pressure,
    ?float $illuminance,
    ?float $noise,
    ?float $uv,
    ?float $discomfortIndex,
    ?float $heatstrokeRiskIndicator) {

    $color = 'good';
    $iconEmoji = ':thermometer:';
    if ($co2 !== null) {
        if ($co2 >= 1000) {
            $color = 'danger';
            $iconEmoji = ':ihou_kuukan:';
        }
        elseif ($co2 >= 700) {
            $color = 'warning';
        }
    }

    $value = [
        'icon_emoji' => $iconEmoji,
        'username' => 'dAir',
        'attachments' => [
            [
                'fallback' => '',
                'color' => $color,
                'title' => $seat,
                'title_link' => 'https://example.com/dAir/?seat=' . $seat,
                'fields' => [],
                'ts' => $timestamp
            ]
        ]
    ];
    $fallback = [];

    if ($temperature !== null) {
        $data = [
            'title' => '室温',
            'value' => sprintf('%.1f ℃', $temperature),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($humidity !== null) {
        $data = [
            'title' => '湿度',
            'value' => sprintf('%.1f %%', $humidity),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($pressure !== null) {
        $data = [
            'title' => '気圧',
            'value' => sprintf('%.1f hPa', $pressure),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($illuminance !== null) {
        $data = [
            'title' => '照度',
            'value' => sprintf('%.0f lx', $illuminance),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($noise !== null) {
        $data = [
            'title' => '騒音',
            'value' => sprintf('%.0f dB', $noise),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($uv !== null) {
        $data = [
            'title' => '紫外線指数',
            'value' => sprintf('%.1f', $uv),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($discomfortIndex !== null) {
        $data = [
            'title' => '不快指数',
            'value' => sprintf('%.1f', $discomfortIndex),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($heatstrokeRiskIndicator !== null) {
        $data = [
            'title' => '熱中症危険度',
            'value' => sprintf('%.1f', $heatstrokeRiskIndicator),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    if ($co2 !== null) {
        $data = [
            'title' => 'CO₂濃度',
            'value' => sprintf('%.0f ppm', $co2),
            'short' => true
        ];
        $value['attachments'][0]['fields'][] = $data;
        $fallback[] = $data['title'] . ': ' . $data['value'];
    }

    $value['attachments'][0]['fallback'] = implode(' / ', $fallback);

    $json = json_encode($value);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/json\r\n",
            'content' => $json
        ]
    ];
    $options = stream_context_create($options);
    $contents = file_get_contents($url, false, $options);

    return true;
}
