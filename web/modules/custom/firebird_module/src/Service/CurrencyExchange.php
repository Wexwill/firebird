<?php

namespace Drupal\firebird_module\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Site\Settings;

/**
 * Class CurrencyExchange.
 */
class CurrencyExchange {

  /**
   * List of all available currencies.
   */
  const CURRENCIES = ['USD', 'EUR', 'RUB'];

  /**
   * The currency to which other currencies are attached.
   */
  const BASE_CURRENCY = 'USD';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Freecurrency API key.
   */
  protected $apikey;

  /**
   * Constructs a CurrencyExchange object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Database\Connection $database
   *    The database connection.
   * @param \Drupal\Core\Site\Settings $settings
   *    The site settings.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger,
    Connection $database,
    Settings $settings
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->database = $database;
    $this->apikey = $settings->get('freecurrency_api_key');
  }

  /**
   * Method for currency conversion.
   *
   * @param $amount
   *    The amount of money to exchange.
   * @param $from
   *    Currency for sale.
   * @param $to
   *    Currency for purchase.
   *
   * @return float
   *    Amount of currency exchanged.
   */
  public function convert($amount, $from, $to) {
    $amount = abs(floatval($amount));
    $from = mb_strtoupper($from);
    $to = mb_strtoupper($to);

    foreach (self::CURRENCIES as $currency) {
      $currencies[$currency] = 1;
    }

    $exchange_rate = $this->getExchangeRate();
    $currencies = array_merge($currencies, $exchange_rate);
    $result = $amount * $currencies[$to] / $currencies[$from];

    return round($result, 4);
  }

  /**
   * Get exchange rates from the database.
   *
   * @return array|bool
   *    The exchange rate.
   */
  public function getExchangeRate() {
    $exchange_rate = [];
    $filtered_currencies = array_diff(self::CURRENCIES, [self::BASE_CURRENCY]);

    try {
      $result = $this->database
        ->select('currency_exchange', 'ce')
        ->fields('ce', $filtered_currencies)
        ->orderBy('id', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();

      if (!empty($result) && is_array($result)) {
        $exchange_rate = $result;
      }
    }
    catch (\Exception $e) {
      $this->logger->get('currency_exchange_service')->error($e->getMessage());
    }

    return $exchange_rate;
  }

  /**
   * Get exchange rates from the Freecurrencyapi API.
   *
   * @return array|void
   *    The exchange rate.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getCurrenciesFromAPI() {
    $filtered_currencies = implode(',', array_diff(self::CURRENCIES, [self::BASE_CURRENCY]));

    try {
      $responce = $this->httpClient->request('GET', 'https://api.freecurrencyapi.com/v1/latest', [
        'query' => [
          'apikey' => $this->apikey,
          'currencies' => $filtered_currencies,
          'base_currency' => self::BASE_CURRENCY
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->get('currency_exchange_service')->error($e->getMessage());
      return;
    }

    $content = $responce->getBody()->getContents();
    $normalization = Json::decode($content);

    return $normalization['data'];
  }

}
