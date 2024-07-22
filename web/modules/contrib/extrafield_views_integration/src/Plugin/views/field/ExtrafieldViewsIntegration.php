<?php

namespace Drupal\extrafield_views_integration\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("extrafield_views_integration")
 */
class ExtrafieldViewsIntegration extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    if (class_exists($this->definition['render_class'])) {
      /** @var \Drupal\extrafield_views_integration\lib\ExtrafieldRenderClassInterface $class */
      $class = $this->definition['render_class'];
      return $class::render($values->_entity);
    }
    else {
      $this->messenger()->addWarning(
      $this->t(
        'An error occurred render_class: @render_class doesn´t exists.',
        ['@render_class' => $this->definition['render_class']]
      )
      );
    }
  }

}
