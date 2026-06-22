<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use Google\Cloud\Storage\StorageClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register GCS filesystem driver
        Storage::extend('gcs', function ($app, $config) {
            $storageConfig = [
                'projectId' => $config['project_id'],
            ];

            // Chỉ thêm keyFilePath nếu có (cho local development)
            // Trên Cloud Run, sẽ dùng Application Default Credentials tự động
            if (!empty($config['key_file']) && is_array($config['key_file'])) {
                // Nếu key_file là array (từ JSON decode), cần lưu vào temp file
                $tempKeyFile = tempnam(sys_get_temp_dir(), 'gcs-key-');
                file_put_contents($tempKeyFile, json_encode($config['key_file']));
                $storageConfig['keyFilePath'] = $tempKeyFile;
            } elseif (!empty($config['key_file']) && is_string($config['key_file'])) {
                $storageConfig['keyFilePath'] = $config['key_file'];
            }

            $storageClient = new StorageClient($storageConfig);
            $bucket = $storageClient->bucket($config['bucket']);
            $adapter = new GoogleCloudStorageAdapter($bucket, $config['path_prefix'] ?? '');
            $driver = new Filesystem($adapter);

            // Trả về FilesystemAdapter của Laravel thay vì Filesystem trực tiếp
            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }
}
