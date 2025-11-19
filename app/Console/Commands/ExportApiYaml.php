<?php

namespace App\Console\Commands;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class ExportApiYaml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scramble:export-yaml
        {--path= : The path to save the exported YAML file}
        {--api=default : The API to export a documentation for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the OpenAPI document to a YAML file.';

    /**
     * Execute the console command.
     */
    public function handle(Generator $generator): int
    {
        $config = Scramble::getGeneratorConfig($api = $this->option('api'));

        $specification = $generator($config);

        /** @var string $filename */
        $filename = $this->option('path') ?? $config->get('export_path', 'api'.($api === 'default' ? '' : "-$api").'.yml');

        // JSONファイル名から拡張子を変更
        if (str_ends_with($filename, '.json')) {
            $filename = str_replace('.json', '.yml', $filename);
        } elseif (! str_ends_with($filename, '.yml') && ! str_ends_with($filename, '.yaml')) {
            $filename .= '.yml';
        }

        // 配列をYAML形式に変換
        $yamlContent = Yaml::dump($specification, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        File::put($filename, $yamlContent);

        $this->info("OpenAPI document exported to {$filename}.");

        return Command::SUCCESS;
    }
}
