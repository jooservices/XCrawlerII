<?php

namespace Modules\Core\Services;

use Illuminate\Support\Str;
use Modules\Jav\Services\ServerService;
use Symfony\Component\Process\Process;

readonly class MediaService
{
    final public function __construct(
        private FilesService $filesService,
        private ServerService $serverService
    ) {
    }

    final public function mediaScan(
        string $serverName,
        string $ip,
        string $dir
    ): ?array {

        $server = $this
            ->serverService
            ->register(
                $ip,
                $serverName
            );

        return $this->filesService->scanFiles(
            $dir,
            function ($file) {
                $fileExt = Str::lower(pathinfo($file, PATHINFO_EXTENSION));

                return in_array($fileExt, config('core.video_extensions'));
            },
            function ($file) use ($server) {
                $fileModel = $this->serverService->addFile(
                    $server,
                    dirname($file),
                    basename($file),
                    filesize($file),
                    mime_content_type($file),
                    pathinfo($file, PATHINFO_EXTENSION)
                );

                if (!$media = $this->mediaCheck($file)) {
                    return;
                }

                $fileModel->encoder = $media->Encoder ?? null;
                $fileModel->frame_rate = $media->VideoFrameRate ?? null;
                $fileModel->width = $media->ImageWidth ?? null;
                $fileModel->height = $media->ImageHeight ?? null;
                $fileModel->metadata = $media;
                $fileModel->save();
            }
        );
    }

    private function mediaCheck(string $filePath): ?object
    {
        $process = new Process(['exiftool', '-json', $filePath]);
        $process->run();
        $output = $process->getOutput();

        if (!empty($output)) {
            return json_decode($output, false)[0];
        }

        return null;
    }
}
