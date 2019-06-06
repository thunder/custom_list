<?php

namespace Drupal\Tests\custom_list_default\FunctionalJavascript;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\custom_list\FunctionalJavascript\CustomListJavascriptTestBase;

/**
 * Testing of Default Source List entity type plugin.
 *
 * @group custom_list_default
 */
class CustomListSourceListTest extends CustomListJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $page = $this->getSession()->getPage();

    // Install module after parent setUp is finished.
    $this->container->get('module_installer')->install(['custom_list_default']);

    // Create new content type with overrides for the layout.
    NodeType::create([
      'type' => 'custom_list_landing_page',
      'name' => 'Landing Page',
    ])->save();

    // Allow overrides for the layout.
    $this->drupalGet('admin/structure/types/manage/custom_list_landing_page/display/default');
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    // Need to rebuild everything so that layout builder field is available.
    $this->rebuildAll();
  }

  /**
   * Testing creation of source list.
   */
  public function testCreate() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createNode([
      'type' => 'article',
      'title' => 'Article 1',
    ]);

    $this->createNode([
      'type' => 'article',
      'title' => 'Article 2',
    ]);

    $this->createNode([
      'type' => 'custom_list_landing_page',
      'title' => 'The first landing page title',
    ]);

    // Open single item layout page.
    $this->drupalGet('node/3/layout');

    // Add a new block.
    $this->clickLink('Add Block');
    $assert_session->assertWaitOnAjaxRequest();

    // Add a custom list block.
    $this->clickLink('Custom list');
    $assert_session->assertWaitOnAjaxRequest();

    // Add a new source list.
    $this->clickLink('Add source list');
    $assert_session->assertWaitOnAjaxRequest();

    // Create the source list.
    $page->fillField('name[0][value]', 'List No. 1');
    $page->find('css', '.ui-dialog-buttonpane button')->press();
    $assert_session->assertWaitOnAjaxRequest();

    // Select new created source list for use in custom list block.
    $page->selectFieldOption('settings[custom_list_config_form][source_list]', '1');
    $assert_session->assertWaitOnAjaxRequest();

    // Add block to layout of landing page.
    $page->pressButton('Add Block');
    $assert_session->assertWaitOnAjaxRequest();

    // Save new layout.
    $page->pressButton('Save layout');
    $assert_session->assertWaitOnAjaxRequest();

    // Check that articles are listed on landing page.
    $page->hasLink('Article 1');
    $page->hasLink('Article 2');
  }

}
