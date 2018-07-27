<?php

namespace Drupal\custom_list\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\user\UserInterface;

/**
 * Defines the Source List entity.
 *
 * @ingroup custom_list
 *
 * @ContentEntityType(
 *   id = "source_list",
 *   label = @Translation("Source List"),
 *   handlers = {
 *     "list_builder" = "Drupal\custom_list\SourceListEntityListBuilder",
 *
 *     "form" = {
 *       "default" = "Drupal\custom_list\Form\SourceListEntityForm",
 *       "add" = "Drupal\custom_list\Form\SourceListEntityForm",
 *       "edit" = "Drupal\custom_list\Form\SourceListEntityForm",
 *       "delete" = "Drupal\custom_list\Form\SourceListEntityDeleteForm",
 *     },
 *     "access" = "Drupal\custom_list\SourceListEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\custom_list\SourceListEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "source_list",
 *   admin_permission = "administer source list entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "plugin_id" = "plugin_id",
 *     "config" = "config",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/source_list/{source_list}",
 *     "add-form" = "/admin/structure/source_list/add",
 *     "edit-form" = "/admin/structure/source_list/{source_list}/edit",
 *     "delete-form" = "/admin/structure/source_list/{source_list}/delete",
 *     "collection" = "/admin/structure/source_list",
 *   }
 * )
 */
class SourceListEntity extends ContentEntityBase implements SourceListEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->get('plugin_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->set('plugin_id', $plugin_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    $config_item = $this->get('config');

    if ($config_item->isEmpty()) {
      return [];
    }

    try {
      $first_list_element = $config_item->first();
      if (empty($first_list_element)) {
        return [];
      }

      return $first_list_element->getValue();
    }
    catch (MissingDataException $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(array $config) {
    $this->set('config', $config);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Source List entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Source List entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['plugin_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PlugIn ID'))
      ->setDescription(t('PlugIn ID that provides source list.'))
      ->setRequired(TRUE);

    $fields['config'] = BaseFieldDefinition::create('map')
      ->setLabel(t('JSON Config'))
      ->setDescription(t('JSON configuration for source list.'))
      ->setRequired(TRUE)
      ->setCardinality(1);

    return $fields;
  }

}
