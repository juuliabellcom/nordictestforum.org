<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\builder\d5\TermNode.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\builder\d5;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateTemplateStorage;
use Drupal\migrate\Plugin\migrate\builder\BuilderBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @PluginID("d5_term_node")
 */
class TermNode extends BuilderBase implements ContainerFactoryPluginInterface {

  /**
   * The migration template storage service.
   *
   * @var \Drupal\migrate\MigrateTemplateStorage
   */
  protected $templateStorage;

  /**
   * Constructs a TermNode builder.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\MigrateTemplateStorage $template_storage
   *   The migration template storage handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateTemplateStorage $template_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->templateStorage = $template_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.template_storage')
    );
  }

  /**
   * Builds a map of source vocabulary IDs to expected destination IDs.
   *
   * @param array $source
   *   Additional configuration for the d5_taxonomy_vocabulary source.
   *
   * @return array
   *   The vid map. The keys are the source IDs and the values are the
   *   (expected) destination IDs.
   */
  protected function getVocabularyIdMap(array $source) {
    $map = [];

    $template = $this->templateStorage->getTemplateByName('d5_taxonomy_vocabulary');
    $template['source'] += $source;

    $migration = Migration::create($template);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    // Only process the destination ID properties.
    $process = array_intersect_key($template['process'], $migration->getDestinationPlugin()->getIds());

    foreach ($migration->getSourcePlugin()->getIterator() as $source_row) {
      $row = new Row($source_row, $source_row);
      // Process the row to generate the expected destination ID.
      $executable->processRow($row, $process);
      $map[$row->getSourceProperty('vid')] = $row->getDestinationProperty('vid');
    }

    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migrations = [];

    foreach ($this->getVocabularyIdMap($template['source']) as $source_vid => $destination_vid) {
      $values = $template;
      $values['id'] .= '__' . $source_vid;
      $values['source']['vid'] = $source_vid;
      $migration = Migration::create($values);
      $migration->setProcessOfProperty($destination_vid, 'tid');
      $migrations[] = $migration;
    }

    return $migrations;
  }

}
