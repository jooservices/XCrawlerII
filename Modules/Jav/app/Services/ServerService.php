<?php

namespace Modules\Jav\Services;

use Modules\Core\Models\File;
use Modules\Core\Models\Server;

class ServerService
{
    final public function register(string $ip, string $name): Server
    {
        return Server::updateOrCreate([
            'name' => $name,
            'ip' => $ip,
        ]);
    }

    final public function addFile(
        Server $server,
        string $path,
        string $filename,
        int $size,
        string $mimeType,
        string $extension
    ): File {
        return File::updateOrCreate([
            'server_id' => $server->id,
            'path' => $path,
            'filename' => $filename,
            'size' => $size,
        ], [
            'mime_type' => $mimeType,
            'extension' => $extension,
        ]);
    }
}
