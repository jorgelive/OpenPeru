<?php

/**
 * @file
 * Contains \Drupal\layout_plugin\Plugin\LayoutPluginInterface.
 */

namespace Drupal\layout_plugin\Plugin\Layout;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for static Layout plugins.
 */
interface LayoutInterface extends PluginInspectionInterface {
  /**
   * Get human-readable list of regions keyed by machine name.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  public function getRegionNames();

  /**
   * Get information on regions keyed by machine name.
   *
   * @return array
   *   An array of information on regions keyed by machine name.
   */
  public function getRegionDefinitions();

  /**
   * Get the base path for all resources.
   *
   * @return string
   *   The full base to all resources.
   */
  public function getBasePath();

  /**
   * Get the full path to the icon image.
   *
   * This can optionally be used in the user interface to show the layout of
   * regions visually.
   *
   * @return string
   *   The full path to preview image file.
   */
  public function getIconFilename();

  /**
   * Get the asset library name.
   *
   * @return string
   *   The asset library.
   */
  public function getLibrary();

  /**
   * Get the theme function for rendering this layout.
   *
   * @return string
   *   Theme function name.
   */
  public function getTheme();

  /**
   * Build a render array for layout with regions.
   *
   * @param $regions array
   *   An associative array keyed by region name, containing render arrays
   *   reperesenting the content that should be placed in each region.
   *
   * @return array
   *   Render array for the layout with regions.
   */
  public function build(array $regions);

}
