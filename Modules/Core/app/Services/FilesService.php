<?php

namespace Modules\Core\Services;

use Closure;

class FilesService
{
    final public function scanFiles(
        string $dir,
        ?Closure $filterCallback = null,
        ?Closure $callback = null,
    ): ?array {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->scanFiles($path, $callback));
            } else {
                if ($filterCallback && !$filterCallback($path)) {
                    continue;
                }

                if ($callback) {
                    $callback($path);
                }

                $files[] = $path;
            }
        }

        return $files;
    }
}
