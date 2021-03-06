<?php

/**
 * @file
 * Contains \Drupal\captcha\Entity\CaptchaPoint.
 */

namespace Drupal\captcha\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\captcha\CaptchaPointInterface;

/**
 * Defines the CaptchaPoint entity.
 *
 * @ConfigEntityType(
 *   id = "captcha_point",
 *   label = @Translation("Captcha Point"),
 *   handlers = {
 *     "list_builder" = "Drupal\captcha\Controller\CaptchaPointListBuilder",
 *     "form" = {
 *       "add" = "Drupal\captcha\Form\CaptchaPointForm",
 *       "edit" = "Drupal\captcha\Form\CaptchaPointForm",
 *       "delete" = "Drupal\captcha\Form\CaptchaPointDeleteForm"
 *     }
 *   },
 *   config_prefix = "captcha_point",
 *   admin_permission = "administer CAPTCHA settings",
 *   entity_keys = {
 *     "id" = "formId",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/captcha/captcha-points/{captcha_point}",
 *     "delete-form" = "/admin/config/people/captcha/captcha-points/{captcha_point}/delete",
 *   }
 * )
 */
class CaptchaPoint extends ConfigEntityBase implements CaptchaPointInterface {
  public $captchaType;

  protected $label;

  public $formId;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->formId;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormId($form_id) {
    $this->formId = $form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCaptchaType() {
    if (isset($this->captchaType)) {
      return $this->captchaType;
    }
    else {
      // @Todo inject config via DI.
      return \Drupal::config('captcha.settings')->get('default_challenge');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCaptchaType($captcha_type) {
    $this->captchaType = $captcha_type != 'default' ? $captcha_type : NULL;
  }
}
