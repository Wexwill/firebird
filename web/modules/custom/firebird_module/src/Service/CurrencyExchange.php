<?php

namespace Drupal\firebird_module\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Utility\Error;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Site\Settings;

class CurrencyExchange {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Freecurrency API key.
   */
  protected $apikey;

  /**
   * Constructs a CurrencyExchange object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Site\Settings $settings
   *    The site settings.
   */
  public function __construct(ClientInterface $http_client, Settings $settings) {
    $this->httpClient = $http_client;
    $this->apikey = $settings->get('freecurrency_api_key');
  }

  public function convertCurrency($amount, $from, $to) {
    $amount = abs($amount);
    $from = mb_strtoupper($from);
    $to = mb_strtoupper($to);

    try {
      $responce = $this->httpClient->request('GET', 'https://api.freecurrencyapi.com/v1/latest', [
        'query' => [
          'apikey' => $this->apikey,
          'currencies' => $to,
          'base_currency' => $from
        ],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('currency_exchange_service')->error($e->getMessage());
      return;
    }

    $content = $responce->getBody()->getContents();
    $normalization = Json::decode($content);
    $exchanged_value = $normalization['data'][$to];
    $result = $exchanged_value * $amount;

    return round($result, 4);
  }

  public function getCurrenciesFromAPI() {
    try {
      $responce = $this->httpClient->request('GET', 'https://api.freecurrencyapi.com/v1/latest', [
        'query' => [
          'apikey' => $this->apikey,
          'currencies' => 'EUR,RUB',
        ],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('currency_exchange_service')->error($e->getMessage());
      return;
    }

    $content = $responce->getBody()->getContents();
    $normalization = Json::decode($content);
    $exchange_values = $normalization['data'];

    return $exchange_values;
  }

}
