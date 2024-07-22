<?php

declare(strict_types=1);

namespace Drupal\Tests\cache_control_override\Kernel;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that cache_control_override schema is correct.
 *
 * @group cache_control_override
 */
final class CacheControlOverrideSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {inheritdoc}
   */
  public static $modules = ['cache_control_override'];

  /**
   * Tests schema.
   */
  public function testSchema() {
    $this->assertConfigSchemaByName('cache_control_override.settings');
  }

}
