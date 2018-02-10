<?php
/**
 * @var modX $modx
 */
$modx->lexicon->load('login_unsplash:default');
$cacheManager = $modx->getCacheManager();
$cacheOptions = [
    xPDO::OPT_CACHE_KEY => 'login_unsplash',
];

$image = $cacheManager->get('today', $cacheOptions);
if (!is_array($image)) {
    $modx->loadClass('rest.modRest', '', false, true);

    $clientId = $modx->getOption('login_unsplash.application_id');
    $client = new modRest($modx, [
        'baseUrl' => 'https://api.unsplash.com/',
        'headers' => [
            'Authorization' => 'Client-ID ' . $clientId
        ],
        'userAgent' => 'MODX Login Unsplash/1.0.0',
    ]);


    $params = [
        'orientation' => 'landscape',
        'featured' => true,
    ];
    $result = $client->get('photos/random', $params);
    $image = $result->process();
    $cacheManager->set('today', $image, 24 * 60 * 60, $cacheOptions);
}

switch ($modx->event->name) {
    case 'OnManagerLoginFormRender':
        $url = $image['urls']['regular'];
        $modx->controller->setPlaceholder('background', $url);

        break;

    case 'OnManagerLoginFormPrerender':
        $photoLink = $image['links']['html'];
        $photographer = $image['user']['name'];
        $userLink = $image['user']['links']['html'];
        $attribution = $modx->lexicon('login_unsplash.attribution', [
            'author' => "<a href=\"{$userLink}?utm_source=modx_login_unsplash&utm_medium=referral\" target=\"_blank\" rel=\"noopener\">{$photographer}</a>",
            'unsplash' => "<a href=\"{$photoLink}?utm_source=modx_login_unsplash&utm_medium=referral\" target=\"_blank\" rel=\"noopener\">Unsplash</a>"
        ]);
        $attribution = "<div class=\"unsplash-attribution\" style=\"
    position: fixed;
    bottom: 0;
    right: 0;
    background: rgba(255,255,255,0.7);
    padding: 0.5rem 1rem;
    border-radius: 3px 0 0 0;\">{$attribution}</div>";
        $modx->event->output($attribution);

        break;
}