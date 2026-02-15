<?php

namespace App\Services\Curseforge;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Aternos\CurseForgeApi\Client\CurseForgeAPIClient;
use Aternos\CurseForgeApi\Client\Options\ModFiles\ModFilesOptions;


use App\Services\Log\Service as LogService;

class Service
{
    private LogService $logger;

    public function __construct(LogService $logger) {
        $this->logger = $logger;
    }

    public function client(): CurseForgeAPIClient
    {
        return new CurseForgeAPIClient(env('CURSE_API_KEY'));
    }

    public function getGame(int $id = 432) {
        $client = $this->client();

        return $client->getGame($id)->getData();
    }

    public function getVersions(int $id = 432) {
        $client = $this->client();

        return $client->getMinecraftVersions();
    }

    public function getMinecraftVersions() {
        $mcVersions = $this->getVersions(432);

        $versions = [];
        $ignoredVersions = [
            'java',
            'addons',
            'modloader',
            'client-side',
            'environment',
            'minecraft-beta',
            '[76853]grouped-mc-versions',
            'shader-loader',
            'server-side',
        ];

        foreach($mcVersions as $version) {
            $version = $version->getData();
            $versionData = [
                'slug' => $version->getSlug(),
                'name' => $version->getName(),
                'id' => $version->getId(),
            ];

            if(!in_array($versionData['slug'], $ignoredVersions)) {
                $versions[] = $versionData;
            }
            
        }

        return $versions;

    }

    public function getMods(int $gameId = 432) {
        $options = new ModSearchOptions($gameId); 

        $mods = $this->client()->searchMods($options);

        return $mods->getData();
    }

    public function getMod(int $modId) {
        $client = $this->client();
        $mod = $client->getMod($modId)->getData();

        return $mod;
    }

    public function getModFiles(int $modId) {
        $client = $this->client();
        // we may need to paginate
        $pageSize = 50;
        $offset = 0;
        $totalResults = 0;
        $filesList = [];

        // first get the first set
        $options = new ModFilesOptions($modId);
        $files = $client->getModFiles($options);

        $fileResults = $files->getResults();
        foreach ($fileResults as $file) {
            $filesList[] = $file;
        }

        while($files->hasNextPage()) {
            $files = $files->getNextPage();
            $fileResults = $files->getResults();
            foreach ($fileResults as $file) {
                $filesList[] = $file;
            }
        }

        return $filesList;
    }

    private function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }
}