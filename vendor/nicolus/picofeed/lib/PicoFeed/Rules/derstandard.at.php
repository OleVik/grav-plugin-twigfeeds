<?php
return [
    'grabber' => [
        '%.*%' => [
            'test_url' => 'http://derstandard.at/2000010267354/The-Witcher-3-Hohe-Hardware-Anforderungen-fuer-PC-Spieler?ref=rss',
            'body' => [
                '//div[@class="copytext"]',
                '//ul[@id="media-list"]',
            ],
            'strip' => [
            ],
        ],
    ],
];
