<?php

/**
 * @file
 * Contains Drupal\layout_plugin\Annotation\Layout.
 */

namespace Drupal\layout_plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Layout annotation object.
 *
 * Layouts are used to define a list of regions and then output render arrays
 * in each of the regions, usually using a theme function.
 *
 * Plugin namespace: Plugin\Layout
 *
 * @see \Drupal\layout_plugin\Plugin\Layout\LayoutInterface
 * @see \Drupal\layout_plugin\Plugin\Layout\LayoutBase
 * @see \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Layout extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The layout type.
   *
   *  - full: Layout for the whole page.
   *  - page: Layout for the main page response.
   *  - partial: A partial layout that is typically used for sub-regions.
   *
   * @var string
   */
  public $type = 'page';

  /**
   * The human-readable name.
   *
   * @war \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The human-readable category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The theme function to render this layout.
   *
   * @var string optional
   */
  public $theme;

  /**
   * The base path (relative to current module) to all resources (like the icon).
   *
   * @var string optional
   */
  public $path;

  /**
   * The asset library.
   *
   * @var string optional
   */
  public $library;

  /**
   * The path to the preview image (relative to the base path).
   *
   * @var string optional
   */
  public $icon;

  /**
   * An associative array of regions in this layout.
   *
   * The key of the array is the machine name of the region, and the value is
   * an associative array with the following keys:
   * - label: (string) The human-readable name of the region.
   *
   * An remaining keys may have special meaning for the given layout plugin, but
   * are undefined here.
   *
   * @var array
   */
  public $regions = array();

}
