<?php

namespace Drupal\firebird_module\Form\CurrencyExchange;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\firebird_module\Service\CurrencyExchange;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Form API.
 */
class CurrencyExchangeForm extends ConfigFormBase {

  /**
   * The currency exchange service.
   *
   * @var \Drupal\firebird_module\Service\CurrencyExchange
   */
  protected $currencyExchange;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *    The config factory for the form.
   * @param \Drupal\firebird_module\Service\CurrencyExchange $currency_exchange
   *    The currency exchange service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CurrencyExchange $currency_exchange
  ) {
    parent::__construct($config_factory);
    $this->currencyExchange = $currency_exchange;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('firebird_module.currency_exchange')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'firebird.currency_exchange',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'currency_exchange';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->currencyExchange::CURRENCIES;
    $exchange_rate = $this->currencyExchange->getExchangeRate();

    // Make sure there is data on exchange rates in the database.
    if (empty($exchange_rate)) {
      \Drupal::messenger()->addMessage($this->t('No data available on exchange rates.'), 'error');
      return $form;
    }

    $form['from'] = [
      '#type' => 'select',
      '#title' => $this->t('From'),
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#step' => 0.01,
      '#suffix' => '<div class="error" id="amount-error"></div>'
    ];

    $form['to'] = [
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#default_value' => 'EUR',
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'to-container'
      ],
    ];

    $form['container']['result'] = [
      '#type' => 'number',
      '#title' => $this->t('Result'),
      '#attributes' => [
        'id' => 'result'
      ],
      '#disabled' => TRUE,
    ];

    foreach ($currencies as $currency) {
      $form['from']['#options'][$currency] = $currency;
      $form['to']['#options'][$currency] = $currency;
    }

    $form['convert'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'to-container'
      ]
    ];

    $form['#theme'][] = 'currency_conversion_form';
    $form['#attached']['library'][] = 'firebird_module/currency-exchange';

    return $form;
  }

  /**
   * AJAX callback to update the 'result' field.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $from = $form_state->getValue('from');
    $to = $form_state->getValue('to');

    $response = new AjaxResponse();

    // Clear the error message before validation.
    $response->addCommand(new ReplaceCommand('#amount-error', '<div class="error" id="amount-error"></div>'));

    // Simple validation for the 'amount' field.
    if (empty($amount)) {
      return $response->addCommand(new ReplaceCommand('#amount-error', '<div class="error" id="amount-error">Please fill the amount.</div>'));
    }

    $result = $this->currencyExchange->convert($amount, $from, $to);
    $form['container']['result']['#value'] = $result;

    $response->addCommand(new ReplaceCommand('#to-container', $form['container']));

    return $response;
  }

    /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    parent::submitForm($form, $form_state);
  }

}
