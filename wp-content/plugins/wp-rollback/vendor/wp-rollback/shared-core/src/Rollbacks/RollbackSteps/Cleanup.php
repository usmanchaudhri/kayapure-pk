<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Rollbacks\Services\BackupService;
use WpRollback\SharedCore\Rollbacks\Services\MaintenanceService;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;

/**
 */
class Cleanup implements RollbackStep
{
    /**
     * Maintenance service instance
     *
     * @var MaintenanceService
     */
    private MaintenanceService $maintenanceService;

    /**
     * Backup service instance, used to recognise a staged copy of a local backup
     *
     * @var BackupService
     */
    private BackupService $backupService;

    /**
     * Constructor
     *
     * @param MaintenanceService $maintenanceService The maintenance service
     * @param BackupService      $backupService      The backup service
     */
    public function __construct(MaintenanceService $maintenanceService, BackupService $backupService)
    {
        $this->maintenanceService = $maintenanceService;
        $this->backupService = $backupService;
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'cleanup';
    }

    /**
     * @inheritdoc
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult
    {
        $assetType = $rollbackApiRequestDTO->getType();
        $assetSlug = $rollbackApiRequestDTO->getSlug();
        $assetVersion = $rollbackApiRequestDTO->getVersion();

        $errors = [];

        // CRITICAL: Always disable maintenance mode first
        // This ensures site is accessible even if other cleanup fails
        $maintenanceDisabled = $this->maintenanceService->disableMaintenanceMode();
        if (!$maintenanceDisabled) {
            // Force disable if standard method fails
            $this->maintenanceService->forceDisableMaintenanceMode();
            $errors[] = __('Maintenance mode required forced cleanup.', 'wp-rollback');
        }

        // Clean up maintenance mode transient
        delete_transient("wpr_maintenance_mode_{$assetType}_{$assetSlug}");

        // Clean up package file. The transient can hold anything, so only trust a non-empty string.
        $packageTransient = get_transient("wpr_{$assetType}_{$assetSlug}_package");
        // A NUL byte makes PHP's filesystem functions warn on older versions, so treat it as no path.
        $package = is_string($packageTransient) && false === strpos($packageTransient, "\0") ? $packageTransient : '';
        $packageExists = '' !== $package && file_exists($package);

        if ($packageExists) {
            $safePackage = $this->resolveDeletablePackage($package);

            if (null === $safePackage) {
                // Never delete anything that isn't a download in the WordPress temp dir or a staged backup copy.
                $errors[] = __('Skipped deleting the package file because it is not a temporary rollback file.', 'wp-rollback');
            } elseif (!@unlink($safePackage)) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
                $errors[] = sprintf(
                    /* translators: %s: Package file path */
                    __('Could not delete package file: %s', 'wp-rollback'),
                    basename($package)
                );
            }
        }

        // Delete the package transient
        delete_transient("wpr_{$assetType}_{$assetSlug}_package");

        // Prepare cleanup summary
        $message = empty($errors) 
            ? __('Cleanup completed successfully.', 'wp-rollback')
            : __('Cleanup completed with warnings.', 'wp-rollback');

        return new RollbackStepResult(
            true, // Always return success to not block the process
            $rollbackApiRequestDTO,
            $message,
            null,
            [
                'maintenance_disabled' => $maintenanceDisabled,
                'warnings' => $errors,
                'cleaned_items' => [
                    'maintenance_file' => !$this->maintenanceService->isMaintenanceModeActive(),
                    'package_file' => !($packageExists && file_exists($package)),
                    'transients' => true
                ]
            ]
        );
    }

    /**
     * Resolve a package path and return it only when it's safe to delete.
     *
     * Safe means a file this plugin created for the rollback: either a download inside the
     * WordPress temp dir, or the staged copy of a local backup in the backup folder.
     *
     * @param string $package Path read from the package transient
     *
     * @return string|null The resolved path, or null when it must not be deleted
     */
    private function resolveDeletablePackage(string $package): ?string
    {
        $realPackage = realpath($package);
        if (false === $realPackage || !is_file($realPackage)) {
            return null;
        }

        if ($this->backupService->isStagedCopy($realPackage)) {
            return $realPackage;
        }

        return $this->isDownloadInTempDir($realPackage) ? $realPackage : null;
    }

    /**
     * Whether a resolved file is a download strictly inside the WordPress temp dir.
     *
     * The temp dir gets a trailing separator so "/tmp-evil" doesn't match "/tmp". WordPress can
     * fall back to WP_CONTENT_DIR as the temp dir, so the file must also look like a download
     * (.tmp from download_url(), or .zip).
     *
     * @param string $realPackage Path already resolved with realpath()
     */
    private function isDownloadInTempDir(string $realPackage): bool
    {
        $tempDir = get_temp_dir();
        if (!is_string($tempDir) || '' === $tempDir || false !== strpos($tempDir, "\0")) {
            return false;
        }

        $realTempDir = realpath($tempDir);
        if (false === $realTempDir) {
            return false;
        }

        $extension = strtolower(pathinfo($realPackage, PATHINFO_EXTENSION));
        if (!in_array($extension, ['tmp', 'zip'], true)) {
            return false;
        }

        $tempDirPrefix = rtrim($realTempDir, '/\\') . DIRECTORY_SEPARATOR;

        return 0 === strpos($realPackage, $tempDirPrefix);
    }

    /**
     * @inheritdoc
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Cleaning up temporary files…', 'wp-rollback');
    }
} 