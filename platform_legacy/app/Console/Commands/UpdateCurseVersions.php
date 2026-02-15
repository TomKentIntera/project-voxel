<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Curseforge\Service as CurseService;

use App\Models\CurseGame;
use App\Models\CurseGameMod;
use App\Models\CurseGameVersion;
use App\Models\CurseModVersion;

class UpdateCurseVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intera:curseforge:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $curseService = \App::make(CurseService::class);

        //dd($curseService->getMods());

        // First, make sure the games exist
        $games = [
            432, // minecraft
        ];

        $mods = [
            432 => [ // minecraft mods
                711537
            ],
        ];

        

        foreach ($games as $gameId) {
            // save the game
            $gameData = $curseService->getGame($gameId);
            $gameModel = CurseGame::updateOrCreate([
                'curseId' => $gameData['id']
            ], [
                'name' => $gameData['name'],
                'curseSlug' => $gameData['slug']
            ]);

            $this->info('> Getting information for game: '.$gameData['name']);

            $gameVersionsData = $curseService->getVersions($gameId);
            $this->info(' > Getting game versions...');
            
            foreach ($gameVersionsData as $version) {
                $versionSlug = $version['version_string'];
                $versionSegments = explode('.', $versionSlug);
                $versionSlug = implode('.', array_map(
                    function($item) {
                        return str_pad($item, 3, "0", STR_PAD_LEFT);
                    }, $versionSegments)
                );


                CurseGameVersion::updateOrCreate([
                    'curseId' => $version['id']
                ], [
                    'slug' => $versionSlug,
                    'name' => $version['version_string'],
                    'gameId' => $gameModel->id
                ]);

                $this->line('  > Updated Version: '.$version['version_string'].' slug: '.$versionSlug);
            }

            $this->info(' > Finished getting game versions...');

            $modsToSearch = $mods[$gameId];

            $this->info(' > Getting mods...'); 
            foreach ($modsToSearch as $modId) {
                $modData = $curseService->getMod($modId);
                

                $modModel = CurseGameMod::updateOrCreate([
                    'curseModId' => $modData['id']
                ], [
                    'gameId' => $gameModel->id,
                    'name' => $modData['name'],
                    'slug' => $modData['slug'],
                    'summary' => $modData['summary'],
                    'curseLink' => $modData['links']['website_url'],
                    'available' => $modData['is_available'],
                ]);

                $this->warn(' > Updated Mod: '.$modData['name']);

                
                $this->line('  > Getting mod files...');
                $modFiles = $curseService->getModFiles($modId);

                foreach($modFiles as $modFile) {
                    $fileData = $modFile->getData();

                    $this->line('   > Getting version: '.$fileData['display_name']);

                    CurseModVersion::updateOrCreate([
                        'curseId' => $fileData['id']
                    ], [
                        'modId' => $modModel->id,
                        'gameId' => $gameModel->id,
                        'gameVersion' => implode(', ', $fileData['game_versions']),
                        'available' => $fileData['is_available'],
                        'name' => str_replace('.zip', '', $fileData['display_name']),
                        'fileName' => $fileData['file_name']
                    ]);
                }

                $this->line('  > Finished getting mod files...');
            }
            
            $this->info(' > Finished getting mods mods...'); 
        }

        $this->info('> Done!');

        return Command::SUCCESS;
    }
}
