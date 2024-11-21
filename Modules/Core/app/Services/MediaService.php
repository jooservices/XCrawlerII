<?php

namespace Modules\Core\Services;

use Illuminate\Support\Str;
use Modules\Core\Models\File;
use Modules\Core\Models\Server;
use Symfony\Component\Process\Process;

readonly class MediaService
{
    public function __construct(private FilesService $filesService)
    {
    }

    public function mediaScan(
        string $serverName,
        string $ip,
        string $dir
    ): ?array {
        $server = Server::updateOrCreate([
            'name' => $serverName,
            'ip' => $ip,
        ]);

        return $this->filesService->scanFiles(
            $dir,
            function ($file) {
                $fileExt = Str::lower(pathinfo($file, PATHINFO_EXTENSION));

                return in_array($fileExt, config('core.video_extensions'));
            },
            function ($file) use ($server) {
                $model = File::updateOrCreate([
                    'server_id' => $server->id,
                    'path' => dirname($file),
                    'filename' => basename($file),
                    'size' => filesize($file),
                ], [
                    'mime_type' => mime_content_type($file),
                    'extension' => pathinfo($file, PATHINFO_EXTENSION),
                ]);

                $media = $this->mediaCheck($file);

                if (!$media) {
                    return;
                }

                $model->encoder = $media->Encoder ?? null;
                $model->frame_rate = $media->VideoFrameRate ?? null;
                $model->width = $media->ImageWidth ?? null;
                $model->height = $media->ImageHeight ?? null;
                $model->metadata = $media;
                $model->save();
            }
        );
    }

    private function mediaCheck(string $filePath)
    {
        $process = new Process(['exiftool', '-json', $filePath]);
        $process->run();
        $output = $process->getOutput();

        if (!empty($output)) {
            return json_decode($output)[0];
        }

        return null;
    }
}
