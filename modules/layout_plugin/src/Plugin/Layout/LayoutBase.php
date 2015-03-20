<?php

/**
 * @file
 * Contains \Drupal\layout_plugin\Plugin\Layout\LayoutBase.
 */

namespace Drupal\layout_plugin\Plugin\Layout;

use Drupal\Core\Plugin\PluginBase;
use Drupal\layout_plugin\Layout;

/**
 * Provides a base class for Layout plugins.
 */
abstract class LayoutBase extends PluginBase implements LayoutInterface {
  /**
   * {@inheritdoc}
   */
  function getRegionNames() {
    $regions = $this->getRegionDefinitions();
    foreach ($regions as $region_id => $region_definition) {
      $return[$region_id] = $region_definition['label'];
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  function getRegionDefinitions() {
    return $this->pluginDefinition['regions'];
  }

  /**
   * {@inheritdoc}
   */
  function getBasePath() {
    $module_path = drupal_get_path('module', $this->pluginDefinition['provider']);
    $path = isset($this->pluginDefinition['path']) && $this->pluginDefinition['path'] ? $this->pluginDefinition['path'] : FALSE;
    return $path ? ($module_path . '/' . $path) : $module_path;
  }

  /**
   * {@inheritdoc}
   */
  function getIconFilename() {
    return isset($this->pluginDefinition['icon']) && $this->pluginDefinition['icon'] ? $this->getBasePath() . '/' . $this->pluginDefinition['icon'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getLibrary() {
    return isset($this->pluginDefinition['library']) && $this->pluginDefinition['library'] ? $this->pluginDefinition['library'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getTheme() {
    return isset($this->pluginDefinition['theme']) && $this->pluginDefinition['theme'] ? $this->pluginDefinition['theme'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function build(array $regions) {
    $build = $regions;
    if ($theme = $this->getTheme()) {
      $build['#theme'] = $theme;
    }
    if ($library = $this->getLibrary()) {
      $build['#attached']['library'][] = $library;
    }
    return $build;
  }

}
