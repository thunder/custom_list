<?php

namespace Drupal\Tests\custom_list\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Class CustomListJavascriptTestBase.
 *
 * Base class for custom_list Javascript tests.
 */
abstract class CustomListJavascriptTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'block',
    'views',
    'layout_builder',
    'entity_browser',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Custom list requires article content type.
    $this->drupalCreateContentType(['type' => 'article']);
    $this->container->get('module_installer')->install(['custom_list']);

    // The Layout Builder UI relies on local tasks.
    $this->drupalPlaceBlock('local_tasks_block');

    $user = $this->drupalCreateUser([
      'access content',
      'configure any layout',
      'administer node display',
      'add source list entities',
    ]);

    $this->drupalLogin($user);
  }

}
