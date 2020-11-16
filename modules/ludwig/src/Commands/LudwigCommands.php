<?php

namespace Drupal\ludwig\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ludwig\PackageDownloaderInterface;
use Drupal\ludwig\PackageManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Declares Ludwig module Drush commands.
 */
class LudwigCommands extends DrushCommands {

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * The download manager.
   *
   * @var \Drupal\ludwig\PackageDownloaderInterface
   */
  protected $packageDownloader;

  /**
   * LudwigCommands constructor.
   *
   * @param \Drupal\ludwig\PackageManagerInterface $package_manager
   *   The Ludwig package manager service.
   * @param \Drupal\ludwig\PackageDownloaderInterface $package_downloader
   *   The Ludwig package downloader service.
   */
  public function __construct(PackageManagerInterface $package_manager, PackageDownloaderInterface $package_downloader) {
    $this->packageManager = $package_manager;
    $this->packageDownloader = $package_downloader;
  }

  /**
   * Downloads Ludwig missing packages.
   *
   * @command ludwig:download
   * @aliases ludwig-download
   */
  public function download() {
    $packages = array_filter($this->packageManager->getPackages(), function ($package) {
      return empty($package['installed']);
    });
    foreach ($packages as $name => $package) {
      if (empty($package['download_url'])) {
        $this->logger()->error(sprintf('No download_url was provided for package "%s".', $name));
        continue;
      }

      try {
        $this->packageDownloader->download($package);
        $this->logger()->success(sprintf('Downloaded package "%s".', $name));
      }
      catch (FileTransferException $e) {
        $this->logger()->error(new TranslatableMarkup($e->getMessage(), $e->arguments));
        return;
      }
      catch (\Exception $e) {
        $this->logger()->error($e->getMessage());
        return;
      }
    }

    if (!empty($packages)) {
      drupal_flush_all_caches();
    }
    else {
      $this->logger()->success('All packages have already been downloaded.');
    }
  }

  /**
   * Lists packages managed by Ludwig.
   *
   * @command ludwig:list
   * @aliases ludwig-list
   * @field-labels
   *   name: Package
   *   paths: Paths
   *   resource: Resource
   *   version: Version
   *   provider: Required By
   *   status: Status
   * @default-fields name,paths,resource,version,provider,status
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Returns entire table
   */
  public function list() {
    $rows = [];
    foreach ($this->packageManager->getPackages() as $package) {
      $rows[] = [
        'name' => $package['name'],
        'paths' => implode(', ', $package['paths']),
        'resource' => $package['resource'],
        'version' => $package['version'],
        'provider' => $package['provider'],
        'status' => new TranslatableMarkup(($package['installed'] ? 'Installed' : 'Missing')),
      ];
    }
    return new RowsOfFields($rows);
  }

}
