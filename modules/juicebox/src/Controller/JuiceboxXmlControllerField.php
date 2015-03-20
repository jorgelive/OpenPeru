<?php

/**
 * @file
 * Controller routines for field-based XML.
 */

namespace Drupal\juicebox\Controller;

use Drupal\juicebox\JuiceboxGalleryInterface;


/**
 * Controller routines for field-based XML.
 */
class JuiceboxXmlControllerField extends JuiceboxXmlControllerBase {

  // Base properties that reference source data.
  protected $idArgs;
  protected $entityType;
  protected $entityId;
  protected $fieldName;
  protected $displayName;
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function init($args) {
    $id_args = explode('/', $args);
    // We need 5 data sources to build a field-based gallery (the type along
    // with 4 identifiers for the field data).
    if (empty($id_args) || count($id_args) < 5) {
      throw new \Exception(t('Cannot initiate field-based Juicebox XML due to insufficient ID args.'));
    }
    // Set data sources as properties.
    $this->idArgs = $id_args;
    $this->entityType = $id_args[1];
    $this->entityId = $id_args[2];
    $this->fieldName = $id_args[3];
    $this->displayName = $id_args[4];
    // Grab the loaded entity as well.
    $this->entity = entity_load($this->entityType, $this->entityId);
  }

  /**
   * {@inheritdoc}
   */
  protected function access() {
    // Drupal 8 has unified APIs for access checks so this is pretty easy.
    if (is_object($this->entity)) {
      $entity_access = $this->entity->access('view');
      $field_access = $this->entity->{$this->fieldName}->access('view');
      return ($entity_access && $field_access);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getGallery() {
    // Build the field and gallery.
    $field = $this->entity->{$this->fieldName}->view($this->displayName);
    // Make sure that the Juicebox is actually built.
    if (!empty($field[0]['#gallery']) && $field[0]['#gallery'] instanceof JuiceboxGalleryInterface && $field[0]['#gallery']->getId()) {
      return $field[0]['#gallery'];
    }
    throw new \Exception(t('Cannot build Juicebox XML for field-based gallery.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function calculateXmlCacheTags() {
    // Add a tag for the entity that this XML comes from.
    return array_merge(array($this->entityType . ':' . $this->entityId), parent::calculateXmlCacheTags());
  }

}
