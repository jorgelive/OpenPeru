<?php

/**
 * @file
 * Test case for Juicebox field formatter.
 */

namespace Drupal\juicebox\Tests;

use Drupal\Component\Utility\String;
use Drupal\Component\Serialization\Json;


/**
 * Tests the Juicebox field formatter.
 *
 * @group Juicebox
 */
class JuiceboxFieldFormatterCase extends JuiceboxBaseCase {

  public static $modules = array('node', 'field_ui', 'image', 'juicebox', 'search', 'contextual');


  /**
   * Define setup tasks.
   */
  public function setUp() {
    parent::setUp();
    // Create and login user.
    $this->webUser = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'administer node fields', 'administer node display', 'bypass node access', 'search content', 'access contextual links'));
    $this->drupalLogin($this->webUser);
    // Prep a node with an image/file field and create a test entity.
    $this->initNode();
    // Activte the field formatter for our new node instance.
    $this->activateJuiceboxFieldFormatter();
    // Create a test node.
    $this->createNodeWithFile();
  }

  /**
   * Test base logic for the Juicebox field formatter.
   */
  public function testFieldFormatter() {
    $node = $this->node;
    $xml_path = 'juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full';
    $xml_url = \Drupal::url('juicebox.xml_field', array('args' => str_replace('juicebox/xml/', '', $xml_path)));
    // Get the urls to the test image and thumb derivative used by default.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(trim(json_encode(array('configUrl' => $xml_url)), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('id="field--node--' . $node->id() . '--' . str_replace('_', '-', $this->instFieldName) . '--full"', 'Embed code wrapper found.');
    $this->assertRaw(String::checkPlain($test_image_url), 'Test image found in embed code');
    // Check for contextual links.
    $id = 'juicebox_xml_field:args=field/node/' . $node->id() . '/' . $this->instFieldName . '/full:|juicebox_conf_field_node:view_mode_name=default&node_type='. $this->instBundle . ':|juicebox_conf_global::';
    $this->assertRaw('<div data-contextual-id="' . String::checkPlain($id) . '"></div>', 'Correct contextual link placeholders found.');
    $json = Json::decode($this->renderContextualLinks(array($id), 'node/' . $node->id()));
    $this->assertResponse(200);
    $this->assertTrue(preg_match('|/juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full.*/admin/structure/types/manage/' . $this->instBundle . '/display/default.*/admin/config/media/juicebox|', $json[$id]), 'Correct contextual links found.');
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . String::checkPlain($test_image_url), 'Test image found in XML.');
    $this->assertRaw('thumbURL="' . String::checkPlain($test_thumb_url), 'Test thumbnail found in XML.');
  }

  /**
   * Test configuration options that are specific to the Juicebox field
   * formatter.
   */
  public function testFieldFormatterConf() {
    $node = $this->node;
    // Alter field formatter specific settings to contain custom values.
    $this->drupalPostAjaxForm('admin/structure/types/manage/' . $this->instBundle . '/display', array(), $this->instFieldName . '_settings_edit', NULL, array(), array(), 'entity-view-display-edit-form');
    $edit = array(
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][image_style]' => '',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][thumb_style]' => 'thumbnail',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][caption_source]' => 'alt',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][title_source]' => 'title',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    // Get the urls to the image and thumb derivatives expected.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_formatted_image_url = file_create_url($uri);
    $test_formatted_thumb_url = entity_load('image_style', 'thumbnail')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(String::checkPlain($test_formatted_image_url), 'Test styled image found in embed code');
    // Check for correct XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('imageURL="' . String::checkPlain($test_formatted_image_url), 'Test styled image found in XML.');
    $this->assertRaw('thumbURL="' . String::checkPlain($test_formatted_thumb_url), 'Test styled thumbnail found in XML.');
    // Note the intended title and caption text does not contain any block-level
    // tags as long as the global title and caption output filter is working.
    // So this acts as a test for that feature as well.
    $this->assertRaw('<title><![CDATA[Some title text for field ' . $this->instFieldName . ' on node ' . $node->id() . ']]></title>', 'Image title text found in XML');
    $this->assertRaw('<caption><![CDATA[Some alt text for field ' . $this->instFieldName . ' on node ' . $node->id() . ' &lt;strong&gt;with formatting&lt;/strong&gt;]]></caption>', 'Image caption text found in XML');
    // Now that we have title and caption data set, also ensure this text can
    // be found in search results. First we update the search index by marking
    // our test node as dirty and running cron.
    $this->drupalPostForm('node/' . $node->id() . '/edit', array(), t('Save and keep published'));
    $this->cronRun();
    $this->drupalPostForm('search', array('keys' => '"Some title text"'), t('Search'));
    $this->assertText('Test Juicebox Gallery Node', 'Juicebox node found in search for title text.');
    // The Juicebox javascript should have been excluded from the search results
    // page.
    $this->assertNoRaw('"configUrl":"', 'Juicebox Drupal.settings vars not included on search result page.');
  }

  /**
   * Test access to the Juicebox XML for the field formatter.
   */
  public function testFieldFormatterAccess() {
    $node = $this->node;
    // Begin testing as an anon user.
    $this->drupalLogout();
    // The XML should be initially accessible (control test).
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'XML access allowed to published node (valid XML detected).');
    // Now unpublish the node as a way of making it inaccessible to
    // non-privileged users. There are unlimited ways that access can be
    // restricted, such as other perm settings, contrb module controls for
    // entities (node_access, tac, etc.), contrb module controls for fields
    // (field_permissions), etc. We can't test them all here, but we can run
    // this basic check to ensure that XML access restrictions kick-in.
    $node->status = 0;
    $node->save();
    // Re-check access.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertResponse(403, 'XML access blocked for unpublished node.');
  }

}
