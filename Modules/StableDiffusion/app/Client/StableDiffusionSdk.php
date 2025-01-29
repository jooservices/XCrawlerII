<?php

namespace Modules\StableDiffusion\Client;

use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;


class StableDiffusionSdk
{
    /**
     * @var Client
     */
    private IClient $client;

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    public function txt2img()
    {
        $this->client->post(
            '/sdapi/v1/txt2img',
            json_decode('{
  "prompt": "blonde hair,16 year old girl,seductive look,detailed facial features,perfect cute face,wearing see-through crop top shirt,white shirt top,see through wetting shirt and panty,sweaty skin,toned,full-length photo,tiara on head,70mm lens,complex braided hair,hourglass figure,slim body,aesthetic,symmetrical,posing,(small areolas:1.2),athletic,sharp,textured skin,perfect body,long hair,angular face,goosebumps,1girl,realistic,c cup breasts,detailed,slim,small waist,(perfect fingers:1.2),large colorful eyes,amused,photographed by a Nikon Z7 II Camera",
  "negative_prompt": "(worst quality:2),(low quality:2),(normal quality:2),lowres,bad anatomy,watermark",

  "seed": -1,

  "sampler_name": null,
  "scheduler": null,
  "batch_size": 1,
  "n_iter": 1,
  "steps": 160,
  "cfg_scale": 6,
  "width": 512,
  "height": 768,
  "restore_faces": true,

  "sampler_index": "Euler",
  "script_name": null,
  "script_args": [],
  "send_images": false,
  "save_images": true,
  "alwayson_scripts": {},
  "infotext": null
}', true)
        );
    }
}
