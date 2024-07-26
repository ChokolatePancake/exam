<?php

namespace Drupal\address_static_map\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'address_static_map' formatter.
 *
 * @FieldFormatter(
 *   id = "address_static_map",
 *   label = @Translation("Address Static Map"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressStaticMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'zoom_level' => 'auto',
      'map_size' => '400x400',
      'additional' => '',
      'map_style' => 'roadmap',
      'scale' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['zoom_level'] = [
      '#title' => $this->t('Zoom level'),
      '#description' => t('The zoom level to use on the map. Must be between 1 and 16 (inclusive) for Mapquest, or any of the options for Google Maps.'),
      '#type' => 'select',
      '#options' => ['auto' => $this->t('Auto')] + range(0, 21),
      '#default_value' => $this->getSetting('zoom_level'),
      '#required' => TRUE,
    ];

    $form['map_size'] = [
      '#title' => $this->t('Map size'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('map_size'),
      '#required' => TRUE,
    ];

    $form['additional'] = [
      '#title' => $this->t('Additional parameters to use in the map URL (i.e. styling a map)'),
      '#type' => 'textfield',
      '#size' => 2048,
      '#default_value' => $this->getSetting('additional'),
    ];

    $form['map_style'] = [
      '#type' => 'select',
      '#title' => t('Map style'),
      '#description' => t('The format to use for the rendered map. Hybrid blends, satellite and roadmap'),
      '#default_value' => $this->getSetting('map_style'),
      '#options' => [
        'roadmap' => $this->t('Roadmap'),
        'satellite' => $this->t('Satellite'),
        'terrain' => $this->t('Terrain'),
        'hybrid' => $this->t('Hybrid'),
      ],
    ];

    $form['scale'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale'),
      '#description' => $this->t('The scale parameter for the image (retina). 4 will only work on Google if you have a premium subscription.'),
      '#default_value' => $this->getSetting('scale'),
      '#options' => [
        1 => t('1x'),
        2 => t('2x'),
        4 => t('4x'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Zoom level: @zoom_level', ['@zoom_level' => $this->getSetting('zoom_level')]);
    $summary[] = $this->t('Map size: @map_size', ['@map_size' => $this->getSetting('map_size')]);
    if (!empty($this->getSetting('additional'))) {
      $summary[] = $this->t('Additional parameters: @additional', ['@additional' => $this->getSetting('additional')]);
    }
    if (!empty($this->getSetting('scale'))) {
      $summary[] = $this->t('Scale: @scale', ['@scale' => $this->getSetting('scale')]);
    }
    if (!empty($this->getSetting('map_style'))) {
      // Show the type name and not only the key.
      $map_style = [
        'roadmap' => $this->t('Roadmap'),
        'satellite' => $this->t('Satellite'),
        'terrain' => $this->t('Terrain'),
        'hybrid' => $this->t('Hybrid'),
      ];
      $summary[] = $this->t('Map style: @map_style', ['@map_style' => $map_style[$this->getSetting('map_style')]]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $address_plain = $item->view(['type' => 'address_plain']);
      $address = $this->formatPlainAddress($address_plain);
      $settings = $this->getSettings();
      $config = \Drupal::config('address_static_map.settings');

      $settings['premier'] = $config->get('premier');
      if ($settings['premier']) {
        $settings['client_id'] = $config->get('premium_client_id');
        $settings['crypto_key'] = $config->get('premium_crypto_key');
      }
      else {
        $settings['api_key'] = $config->get('api_key');
        $settings['secret'] = $config->get('secret');
      }

      $settings['icon_url'] = $config->get('icon_url');
      $settings['icon_url'] = empty($settings['icon_url']) ? 'color:green' : 'icon:' . Url::fromUri($settings['icon_url'])->toString();
      $elements[$delta] = $this->renderGoogleMapsImage($address, $settings);
    }

    return $elements;
  }

  /**
   * Render static Google Map image for a specific address.
   *
   * @param string $address
   *   The address being displayed.
   * @param array $settings
   *   An array of settings related to the map to be displayed.
   *
   * @return array
   *   Renderable array to render a Google map image.
   */
  protected function renderGoogleMapsImage(string $address, array $settings): array {
    $url_args = [
      'query' => [
        'center' => $address,
        'zoom' => $settings['zoom_level'],
        'size' => $settings['map_size'],
        'scale' => $settings['scale'],
        'maptype' => $settings['map_style'],
        'markers' => implode('|',
          [
            $settings['icon_url'],
            $address,
          ]
        ),
      ],
    ];

    if ($url_args['query']['zoom'] == 'auto') {
      unset($url_args['query']['zoom']);
    }

    // Check for Google Maps API key vs Premium Plan via Client ID.
    if (isset($settings['premier']) && $settings['premier']) {
      $url_args['query']['client'] = $settings['client_id'];
    }
    else {
      $url_args['query']['key'] = $settings['api_key'];
    }

    $settings['staticmap_url'] = Url::fromUri('https://maps.googleapis.com/maps/api/staticmap', $url_args)->toString();

    if (!empty($settings['additional'])) {
      $settings['staticmap_url'] .= '&' . $settings['additional'];
    }

    // Generate signature from premium crypto key or APY key signing secret.
    $data = str_replace('https://maps.googleapis.com', '', $settings['staticmap_url']);
    if (isset($settings['premier']) && $settings['premier']) {
      $hash_key = $settings['crypto_key'];
    }
    else {
      $hash_key = $settings['secret'];
    }
    if ($hash_key) {
      $signature = hash_hmac('sha1', $data, base64_decode(strtr($hash_key, '-_', '+/')), TRUE);
      $signature = strtr(base64_encode($signature), '+/', '-_');
      $settings['staticmap_url'] .= '&signature=' . $signature;
    }

    return [
      '#theme' => 'image',
      '#uri' => $settings['staticmap_url'],
      '#alt' => $address,
    ];
  }

  /**
   * Returns a Google Maps-friendly address from a renderable plain address.
   *
   * @param array $address_plain
   *   Renderable address in address_plain format.
   *
   * @return string
   *   String containing the address, formatted for Google / Mapquest.
   */
  protected function formatPlainAddress(array $address_plain) {
    $address = [];
    $address_first = [
      'address_line1',
      'address_line2',
      'locality',
    ];
    foreach ($address_first as $part) {
      if (!empty($address_plain["#$part"])) {
        $address[$part] = $address_plain["#$part"];
      }
    }

    $area = [];
    if (!empty($address_plain['#administrative_area'])) {
      if (!empty($address_plain['#administrative_area']['code'])) {
        $area[] = $address_plain['#administrative_area']['code'];
      }
      elseif (!empty($address_plain['#administrative_area']['name'])) {
        $area[] = $address_plain['#administrative_area']['name'];
      }
    }
    if (!empty($address_plain['#postal_code'])) {
      $area[] = $address_plain['#postal_code'];
    }
    $area = implode(' ', $area);
    if (!empty($area)) {
      $address[] = $area;
    }

    if (!empty($address_plain['#country'])) {
      if (!empty($address_plain['#country']['name'])) {
        $address[] = $address_plain['#country']['name'];
      }
      elseif (!empty($address_plain['#country']['code'])) {
        $address[] = $address_plain['#country']['code'];
      }
    }

    return implode(', ', $address);
  }

}
