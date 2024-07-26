<?php

namespace Drupal\address_static_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the module settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'address_static_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_static_map_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('address_static_map.settings');

    $form['premier'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Google Maps Premier support'),
      '#description' => $this->t('Use Google Maps Premier support (requires a client ID and crypto key instead of an API key.'),
      '#default_value' => $config->get('premier'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('API key for the service'),
      '#default_value' => $config->get('api_key'),
      '#states' => [
        'visible' => [
          ":input[name=premier]" => ['checked' => FALSE],
        ],
      ],
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL signing secret'),
      '#description' => $this->t('Secret key needed to add a signature'),
      '#default_value' => $config->get('secret'),
      '#states' => [
        'visible' => [
          ":input[name=premier]" => ['checked' => FALSE],
        ],
      ],
    ];

    $premium_states = [
      'visible' => [
        ":input[name=premier]" => ['checked' => TRUE],
      ],
    ];
    $form['premium_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Premium Client ID'),
      '#description' => $this->t('Client ID for Google Premium'),
      '#default_value' => $config->get('premium_client_id'),
      '#states' => $premium_states,
    ];

    $form['premium_crypto_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Premium Crypto Key'),
      '#description' => $this->t('Crypto Key for Google Premium'),
      '#default_value' => $config->get('premium_crypto_key'),
      '#states' => $premium_states,
    ];

    $form['icon_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom marker icon URL'),
      '#description' => $this->t('Optional URL for custom marker icon to use instead of the regular Google map marker icon. Must be smaller than 64x64.'),
      '#default_value' => $config->get('icon_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('address_static_map.settings')
      ->set('premier', $values['premier'])
      ->set('api_key', $values['api_key'])
      ->set('secret', $values['secret'])
      ->set('premium_client_id', $values['premium_client_id'])
      ->set('premium_crypto_key', $values['premium_crypto_key'])
      ->set('icon_url', $values['icon_url'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
