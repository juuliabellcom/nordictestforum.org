<?php

namespace Drupal\ludwig\Controller;

use Drupal\Core\Link;
use Drupal\ludwig\PackageManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the Packages report.
 */
class PackageController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new PackageController object.
   *
   * @param \Drupal\ludwig\PackageManagerInterface $package_manager
   *   The package manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(PackageManagerInterface $package_manager, TranslationInterface $string_translation, ModuleExtensionList $module_extension_list) {
    $this->packageManager = $package_manager;
    $this->setStringTranslation($string_translation);
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ludwig.package_manager'),
      $container->get('string_translation'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Shows the status of all required packages.
   *
   * @return array
   *   Returns a render array as expected by drupal_render().
   */
  public function page() {
    $info = $this->moduleExtensionList->getAllInstalledInfo();
    $build = [];
    $build['packages'] = [
      '#theme' => 'table',
      '#header' => [
        'package' => $this->t('Package'),
        'paths' => $this->t('Paths'),
        'resource' => $this->t('Resource'),
        'version' => $this->t('Version'),
        'required_by' => $this->t('Required by'),
        'status' => $this->t('Status'),
      ],
      '#attributes' => [
        'class' => ['system-status-report'],
      ],
    ];
    foreach ($this->packageManager->getPackages() as $package_name => $package) {
      if (($package['resource'] == 'classmap' || $package['resource'] == 'files') && empty($package['disable_warnings'])) {
        $package['description'] = $this->t('<strong>Warning! The @resource autoload type libraries are not supported by Ludwig yet.</strong>', [
          '@resource' => strtoupper($package['resource']),
        ]);
      }
      elseif (($package['resource'] == 'exclude-from-classmap' || $package['resource'] == 'target-dir') && empty($package['disable_warnings'])) {
        $package['description'] = $this->t('<strong>Warning! The @resource autoload property is not supported by Ludwig yet.</strong>', [
          '@resource' => strtoupper($package['resource']),
        ]);
      }
      elseif (($package['resource'] == 'legacy' || $package['resource'] == 'unknown') && empty($package['disable_warnings'])) {
        $package['description'] = $this->t('<strong>Warning! The @resource library type. Not supported by Ludwig.</strong>', [
          '@resource' => strtoupper($package['resource']),
        ]);
      }
      elseif (!$package['installed']) {
        $package['description'] = $this->t('@download the library and place it in @path', [
          '@download' => Link::fromTextAndUrl($this->t('Download'), Url::fromUri($package['download_url']))->toString(),
          '@path' => $package['path'],
        ]);
      }

      $package_column = [];
      if (!empty($package['homepage'])) {
        $package_column[] = [
          '#type' => 'link',
          '#title' => $package['name'],
          '#url' => Url::fromUri($package['homepage']),
          '#options' => [
            'attributes' => ['target' => '_blank'],
          ],
        ];
      }
      else {
        $package_column[] = [
          '#plain_text' => $package['name'],
        ];
      }
      if (!empty($package['description'])) {
        $package_column[] = [
          '#prefix' => '<div class="description">',
          '#markup' => $package['description'],
          '#suffix' => '</div>',
        ];
      }
      $required_by = $package['provider'];
      if (isset($info[$package['provider']])) {
        $required_by = $info[$package['provider']]['name'];
      }

      $build['packages']['#rows'][$package_name] = [
        'class' => $package['installed'] ? [] : ['error'],
        'data' => [
          'package' => [
            'data' => $package_column,
          ],
          'paths' => implode(', ', $package['paths']),
          'resource' => $package['resource'],
          'version' => $package['version'],
          'required_by' => $required_by,
          'status' => $package['installed'] ? $this->t('Installed') : $this->t('Missing'),
        ],
      ];
    }

    return $build;
  }

}
