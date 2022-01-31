<?php
/**
 * @var modX $modx
 */

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

$modx->lexicon->load('daily_photo:default');
$cacheManager = $modx->getCacheManager();
$cacheOptions = [
    xPDO::OPT_CACHE_KEY => 'daily_photo',
];

$today = date('Ymd');
$image = $cacheManager->get($today, $cacheOptions);
if (!is_array($image)) {
    try {
        /** @var ClientInterface $client */
        $client = $modx->services->get(ClientInterface::class);
        /** @var RequestFactoryInterface $factory */
        $factory = $modx->services->get(RequestFactoryInterface::class);
        $clientId = $modx->getOption('daily_photo.access_key');

        $params = [
            'orientation' => 'landscape',
            'query' => $modx->getOption('daily_photo.query', null, 'creative', true),
            'content_filter' => 'high',
        ];

        $request = $factory->createRequest('GET', 'https://api.unsplash.com/photos/random?' . http_build_query($params))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Client-ID ' . $clientId)
            ->withHeader('User-Agent', 'MODX Daily Photo/1.0.0');

        $response = $client->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200) {
            $modx->log(xPDO::LOG_LEVEL_ERROR, 'Received error loading photo from Unsplash: ' . print_r($data, true));
            return;
        }

        $image = $data;

        // trigger a download event per API guidelines
        $downloadRequest = $factory->createRequest('GET', $data['links']['download_location'])
            ->withHeader('Authorization', 'Client-ID ' . $clientId)
            ->withHeader('User-Agent', 'MODX Daily Photo/1.0.0');
        $client->sendRequest($downloadRequest);

        $cacheManager->set($today, $image, 24 * 60 * 60, $cacheOptions);
    }
    catch (Exception $e) {
        $modx->log(xPDO::LOG_LEVEL_ERROR, 'Received error loading photo from Unsplash: ' . get_class($e) . ' ' . $e->getMessage());
    }
}

$hasImage = is_array($image) && !empty($image['urls']['regular']);

switch ($modx->event->name) {
    case 'OnManagerLoginFormRender':
        if ($hasImage) {
            $url = $image['urls']['regular'];
            $modx->controller->setPlaceholder('background', $url);
        }
        return;

    case 'OnManagerLoginFormPrerender':
        if ($hasImage) {
            $photoLink = htmlentities($image['links']['html'], ENT_QUOTES, 'UTF-8');
            $photographer = htmlentities($image['user']['name'], ENT_QUOTES, 'UTF-8');
            $userLink = htmlentities($image['user']['links']['html'], ENT_QUOTES, 'UTF-8');
            $attribution = $modx->lexicon('daily_photo.attribution', [
                'author' => "<a href=\"{$userLink}?utm_source=modx_daily_photo&utm_medium=referral\" target=\"_blank\" rel=\"noopener\">{$photographer}</a>",
                'unsplash' => "<a href=\"{$photoLink}?utm_source=modx_daily_photo&utm_medium=referral\" target=\"_blank\" rel=\"noopener\">Unsplash</a>"
            ]);
            $attribution = "<div class=\"unsplash-attribution\" style=\"
    position: fixed;
    bottom: 0;
    right: 0;
    background: rgba(255,255,255,0.7);
    padding: 0.5rem 1rem;
    border-radius: 3px 0 0 0;\">{$attribution}</div>";
            $modx->event->output($attribution);
        }
        return;

    case 'OnSiteRefresh':
        $modx->log(\xPDO\xPDO::LOG_LEVEL_INFO, 'Clearing today\'s daily photo');
        $cacheManager->delete($today, $cacheOptions);

        return;
}
