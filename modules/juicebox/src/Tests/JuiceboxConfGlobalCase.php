<?php

/**
 * @file
 * Test case for Juicebox global configuration options.
 */

namespace Drupal\juicebox\Tests;

use Drupal\Component\Utility\String;

/**
 * Tests global configuration logic for Juicebox galleries.
 *
 * @group Juicebox
 */
class JuiceboxConfGlobalCase extends JuiceboxBaseCase {

  public static $modules = array('node', 'field_ui', 'image', 'juicebox', 'config_translation');


  /**
   * Define setup tasks.
   */
  public function setUp() {
    parent::setUp();
    // Create and login user.
    $this->webUser = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'administer node fields', 'administer node display', 'bypass node access', 'administer languages', 'translate interface'));
    $this->drupalLogin($this->webUser);
    // Prep a node with an image/file field and create a test entity.
    $this->initNode();
    // Activte the field formatter for our new node instance.
    $this->activateJuiceboxFieldFormatter();
    // Create a test node.
    $this->createNodeWithFile();
  }

  /**
   * Test general global options.
   *
   * This methods tests multiple global options at once, but these could later
   * be broken out into different test methods if needed.
   */
  public function testGlobalConfig() {
    $node = $this->node;
    // We want to be able to set translations.
    $edit = array(
      'locale_translate_english' => TRUE,
    );
    $this->drupalPostForm('admin/config/regional/language/edit/en', $edit, t('Save language'));
    // Enable some optional global settings.
    $edit = array(
      'enable_cors' => TRUE,
      'translate_interface' => TRUE,
      'base_languagelist' => 'Show Thumbnails|Hide Thumbnails|Expand Gallery|Close Gallery|Open Image in New Window',
    );
    $this->drupalPostForm('admin/config/media/juicebox', $edit, t('Save configuration'));
    $this->assertText(t('The Juicebox configuration options have been saved'), 'Custom global options saved.');
    // We need to set a translation for our languagelist string. There is
    // probably a good way to do this directly in code, but for now it's fairly
    // easy to just brute-force it via the UI. First we need to visit the
    // gallery to allow Drupal to detect our Juicebox languagelist translatable
    // string.
    $this->drupalGet('node/' . $node->id());
    // Then we set the translation by searching for the base string and then
    // inputting an english translation for it.
    $edit = array(
      'string' => 'Show Thumbnails|Hide Thumbnails|Expand Gallery|Close Gallery|Open Image in New Window',
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Filter'));
    $matches = array();
    $this->assertTrue(preg_match('/name="strings\[([0-9]+)\]\[translations\]\[0\]"/', $this->getRawContent(), $matches), 'Languagelist base string is available for translation.');
    $edit = array(
      'strings[' . $matches[1] . '][translations][0]' => 'Translated|Lang|List',
    );
    $this->drupalPostForm(NULL, $edit, t('Save translations'));
    $this->assertText(t('The strings have been saved'), 'Languagelist translation saved.');
    // Now check the resulting XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    // Check that the languagelist configuration option was both included and
    // translated in the XML.
    $this->assertRaw('languagelist="Translated|Lang|List"', 'Translated languagelist value found in XML.');
    // Check the the XML now returns an 'Access-Control-Allow-Origin' header
    // for CORS support.
    $this->assertEqual($this->drupalGetHeader('Access-Control-Allow-Origin'), '*', 'Expected CORS header found.');
  }

  /**
   * Test multi-size integration.
   */
  public function testGlobalMultisize() {
    $node = $this->node;
    // Customize one of our global multi-size settings from the default for a
    // true end-to-end test.
    $edit = array(
      'juicebox_multisize_large' => 'large',
    );
    $this->drupalPostForm('admin/config/media/juicebox', $edit, t('Save configuration'));
    $this->assertText(t('The Juicebox configuration options have been saved'), 'Custom global options saved.');
    // Alter field formatter specific settings to use multi-size style.
    $this->drupalPostAjaxForm('admin/structure/types/manage/' . $this->instBundle . '/display', array(), $this->instFieldName . '_settings_edit', NULL, array(), array(), 'entity-view-display-edit-form');
    $edit = array(
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][image_style]' => 'juicebox_multisize',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    // Calculate the multi-size styles that should be found in the XML.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $formatted_image_small = entity_load('image_style', 'juicebox_small')->buildUrl($uri);
    $formatted_image_medium = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $formatted_image_large = entity_load('image_style', 'large')->buildUrl($uri);
    // Get resulting XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    // Check that the expected images are found in the XML.
    $this->assertRaw('smallImageURL="' . String::checkPlain($formatted_image_small), 'Test small image found in XML.');
    $this->assertRaw('imageURL="' . String::checkPlain($formatted_image_medium), 'Test medium image found in XML.');
    $this->assertRaw('largeImageURL="' . String::checkPlain($formatted_image_large), 'Test large image found in XML.');
  }

}
